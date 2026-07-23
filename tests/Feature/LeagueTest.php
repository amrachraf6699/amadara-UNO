<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\RunLeagueSimulation;
use Tests\TestCase;

class LeagueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function squadPayload(int $offset = 0): array
    {
        $slots = ['goalkeeper', 'defender_1', 'defender_2', 'defender_3', 'defender_4', 'midfielder_1', 'midfielder_2', 'midfielder_3', 'forward_1', 'forward_2', 'forward_3'];
        return ['formation' => '4-3-3', 'players' => collect($slots)->map(fn ($slot, $index) => ['slot' => $slot, 'player_id' => $offset + $index + 1])->all(), 'coach_player_id' => $offset + 12];
    }

    public function test_guests_cannot_access_the_dashboard_or_league_actions(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->post(route('leagues.store'))->assertRedirect(route('login'));
        $this->post(route('leagues.join'))->assertRedirect(route('login'));
    }

    public function test_dashboard_lists_only_the_authenticated_users_leagues(): void
    {
        $user = User::factory()->create();
        $memberLeague = League::factory()->create(['name' => 'My League']);
        $otherLeague = League::factory()->create(['name' => 'Other League']);
        $user->leagues()->attach($memberLeague);

        $response = $this->actingAs($user)->get(route('dashboard.index'));

        $response->assertOk();
        $response->assertSee('My League');
        $response->assertDontSee('Other League');
    }

    public function test_user_can_create_a_league_and_is_added_as_a_member(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('leagues.store'), [
            'name' => 'Friday Night League',
            'max_users' => 12,
            'icon' => 'bx bx-trophy',
        ]);

        $response->assertRedirect(route('squads.show', $league = League::firstOrFail()));
        $this->assertDatabaseCount('leagues', 1);
        $league ??= League::firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}$/', $league->code);
        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $user->id]);
        $this->assertSame($user->id, $league->owner_id);
    }

    public function test_create_does_not_require_dates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('leagues.store'), [
            'name' => 'Too Soon League',
            'max_users' => 10,
            'icon' => 'bx bx-football',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leagues', ['name' => 'Too Soon League']);
    }

    public function test_create_and_join_return_json_for_ajax_requests(): void
    {
        $captain = User::factory()->create();
        $player = User::factory()->create();
        $headers = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

        $createResponse = $this->actingAs($captain)->withHeaders($headers)->post(route('leagues.store'), [
            'name' => 'Ajax League',
            'max_users' => 10,
            'icon' => 'bx bx-star',
        ]);

        $createResponse->assertCreated()->assertJsonPath('league.name', 'Ajax League');
        $league = League::where('name', 'Ajax League')->firstOrFail();

        $joinResponse = $this->actingAs($player)->withHeaders($headers)->post(route('leagues.join'), ['code' => $league->code]);

        $joinResponse->assertOk()->assertJsonPath('league.id', $league->id);
        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $player->id]);
    }

    public function test_user_can_join_an_available_league_by_code(): void
    {
        $captain = User::factory()->create();
        $player = User::factory()->create();
        $league = League::factory()->create(['max_users' => 2]);
        $league->users()->attach($captain);

        $response = $this->actingAs($player)->post(route('leagues.join'), ['code' => strtolower($league->code)]);

        $response->assertRedirect(route('squads.show', $league));
        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $player->id]);
    }

    public function test_user_cannot_join_an_archived_or_full_league(): void
    {
        $captain = User::factory()->create();
        $player = User::factory()->create();
        $archived = League::factory()->create(['status' => League::STATUS_ARCHIVED]);
        $full = League::factory()->create(['max_users' => 1]);
        $full->users()->attach($captain);

        $this->actingAs($player)->from(route('dashboard.index'))->post(route('leagues.join'), ['code' => $archived->code])->assertSessionHasErrors('code');
        $this->actingAs($player)->from(route('dashboard.index'))->post(route('leagues.join'), ['code' => $full->code])->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('league_user', ['league_id' => $archived->id, 'user_id' => $player->id]);
        $this->assertDatabaseMissing('league_user', ['league_id' => $full->id, 'user_id' => $player->id]);
    }

    public function test_only_ready_members_allow_the_owner_to_start_the_league(): void
    {
        $owner = User::factory()->create(); $player = User::factory()->create();
        $league = League::factory()->create(['owner_id' => $owner->id]);
        $league->users()->attach([$owner->id, $player->id]);
        $this->actingAs($owner)->postJson(route('squads.store', $league), $this->squadPayload())->assertOk();
        $this->actingAs($player)->postJson(route('squads.store', $league), $this->squadPayload(12))->assertOk();

        $this->actingAs($owner)->post(route('leagues.start', $league))->assertSessionHasErrors('league');
        $league->users()->updateExistingPivot($owner->id, ['ready_at' => now()]);
        $this->actingAs($owner)->post(route('leagues.start', $league))->assertSessionHasErrors('league');
        $league->users()->updateExistingPivot($player->id, ['ready_at' => now()]);
        $this->actingAs($player)->post(route('leagues.start', $league))->assertSessionHasErrors('league');

        Queue::fake();
        $this->actingAs($owner)->post(route('leagues.start', $league))->assertRedirect(route('leagues.show', $league));
        Queue::assertPushed(RunLeagueSimulation::class);
        $this->assertDatabaseHas('league_simulations', ['league_id' => $league->id, 'status' => 'pending']);
    }
}
