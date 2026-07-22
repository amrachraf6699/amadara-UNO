<?php

namespace Tests\Feature;

use App\Models\FootballPlayer;
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

    private function players(int $count = 12): void
    {
        for ($id = 1; $id <= $count; $id++) {
            FootballPlayer::create(['provider_id' => $id, 'name' => "Player {$id}", 'normalized_name' => "player {$id}", 'position' => 'Forward']);
        }
    }

    private function payload(string $formation = '4-3-3', int $offset = 0): array
    {
        $roles = ['goalkeeper' => 1, 'defender' => 4, 'midfielder' => 3, 'forward' => 3];
        $players = [];
        $id = $offset + 1;
        foreach ($roles as $role => $count) for ($i = 1; $i <= $count; $i++) $players[] = ['slot' => $role === 'goalkeeper' ? $role : "{$role}_{$i}", 'provider_id' => $id++];
        return ['formation' => $formation, 'players' => $players, 'coach_provider_id' => $id];
    }

    public function test_member_can_save_a_locked_squad(): void
    {
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user); $this->players();
        $response = $this->actingAs($user)->postJson(route('squads.store', $league), $this->payload());
        $response->assertOk()->assertJsonPath('message', 'Your squad is saved and locked.');
        $this->assertDatabaseCount('squad_selections', 12);
        $this->assertDatabaseHas('squads', ['league_id' => $league->id, 'user_id' => $user->id, 'formation' => '4-3-3']);
    }

    public function test_a_player_cannot_be_reserved_twice_in_one_league(): void
    {
        $first = User::factory()->create(); $second = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach([$first->id, $second->id]); $this->players(24);
        $this->actingAs($first)->postJson(route('squads.store', $league), $this->payload())->assertOk();
        $secondPayload = $this->payload(offset: 12); $secondPayload['players'][0]['provider_id'] = 1;
        $this->actingAs($second)->postJson(route('squads.store', $league), $secondPayload)->assertStatus(422);
    }

    public function test_search_uses_cached_players_before_provider_when_three_matches_exist(): void
    {
        config(['services.footballdata.api_key' => 'test-key']);
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        foreach (range(1, 3) as $id) FootballPlayer::create(['provider_id' => $id, 'name' => "Mohamed {$id}", 'normalized_name' => "mohamed {$id}"]);
        Http::fake();
        $response = $this->actingAs($user)->getJson(route('squads.players.search', ['league' => $league, 'q' => 'moh']));
        $response->assertOk()->assertJsonPath('source', 'database');
        Http::assertNothingSent();
    }
}
