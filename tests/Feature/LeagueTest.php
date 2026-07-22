<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
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
            'start_at' => '2026-08-01 18:00',
            'end_at' => '2026-09-01 22:00',
            'status' => League::STATUS_YET_TO_START,
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseCount('leagues', 1);
        $league = League::firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}$/', $league->code);
        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $user->id]);
    }

    public function test_create_rejects_a_start_time_within_five_minutes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('dashboard.index'))->post(route('leagues.store'), [
            'name' => 'Too Soon League',
            'max_users' => 10,
            'icon' => 'bx bx-football',
            'start_at' => now()->addMinutes(4)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHasErrors('start_at');
        $this->assertDatabaseCount('leagues', 0);
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
            'start_at' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
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

        $response->assertRedirect(route('dashboard.index'));
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
}
