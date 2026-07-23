<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class SquadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function payload(string $formation = '4-3-3', int $offset = 0): array
    {
        $roles = ['goalkeeper' => 1, 'defender' => 4, 'midfielder' => 3, 'forward' => 3];
        $players = [];
        $id = $offset + 1;
        foreach ($roles as $role => $count) for ($i = 1; $i <= $count; $i++) $players[] = ['slot' => $role === 'goalkeeper' ? $role : "{$role}_{$i}", 'player_id' => $id++];
        return ['formation' => $formation, 'players' => $players, 'coach_player_id' => $id];
    }

    public function test_member_can_save_a_locked_squad(): void
    {
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        $response = $this->actingAs($user)->postJson(route('squads.store', $league), $this->payload());
        $response->assertOk()->assertJsonPath('message', 'Your squad is saved and locked.');
        $this->assertDatabaseCount('squad_selections', 12);
        $this->assertDatabaseHas('squad_selections', ['player_id' => 1, 'slot_key' => 'goalkeeper']);
    }

    public function test_locked_squad_displays_formation_players_and_coach(): void
    {
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        $this->actingAs($user)->postJson(route('squads.store', $league), $this->payload());
        $this->actingAs($user)->get(route('squads.show', $league))->assertOk()->assertSee('Your locked squad.')->assertSee('Locked 4-3-3')->assertSee('Marc-André ter Stegen');
    }

    public function test_player_can_mark_their_locked_squad_ready(): void
    {
        $user = User::factory()->create(); $league = League::factory()->create(['owner_id' => $user->id]); $league->users()->attach($user);
        $this->actingAs($user)->postJson(route('squads.store', $league), $this->payload())->assertOk();
        $this->actingAs($user)->post(route('leagues.ready', $league))->assertRedirect(route('squads.show', $league));
        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $user->id]);
        $this->assertNotNull($league->users()->whereKey($user->id)->first()->pivot->ready_at);
    }

    public function test_a_player_cannot_be_reserved_twice_in_one_league(): void
    {
        $first = User::factory()->create(); $second = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach([$first->id, $second->id]);
        $this->actingAs($first)->postJson(route('squads.store', $league), $this->payload())->assertOk();
        $secondPayload = $this->payload(offset: 12); $secondPayload['players'][0]['player_id'] = 1;
        $this->actingAs($second)->postJson(route('squads.store', $league), $secondPayload)->assertStatus(422);
    }

    public function test_search_reads_from_teams_json_without_http_requests(): void
    {
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        Http::fake();
        $response = $this->actingAs($user)->getJson(route('squads.players.search', ['league' => $league, 'q' => 'messi']));
        $response->assertOk()->assertJsonPath('source', 'teams.json')->assertJsonStructure(['results', 'has_more', 'source']);
        Http::assertNothingSent();
    }

    public function test_unknown_local_player_cannot_be_saved(): void
    {
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        $payload = $this->payload(); $payload['players'][0]['player_id'] = 99999999;
        $this->actingAs($user)->postJson(route('squads.store', $league), $payload)->assertStatus(422);
    }
}
