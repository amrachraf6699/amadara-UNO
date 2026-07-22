<?php

namespace Tests\Feature;

use App\Models\FootballPlayer;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function test_provider_players_are_unwrapped_cached_and_logged(): void
    {
        config(['services.footballdata.api_key' => 'test-key']);
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        Http::fake(['https://footballdata.io/*' => Http::response(['success' => true, 'data' => ['players' => [
            ['player_id' => 5001, 'player_name' => 'Lionel Andrés Messi Cuccittini', 'known_name' => 'Lionel Messi', 'first_name' => 'Lionel Andrés', 'last_name' => 'Messi Cuccittini', 'nationality' => 'Argentina', 'age' => 39, 'height_cm' => 170, 'position' => 'Forward', 'player_image' => 'https://example.test/messi.png', 'team' => ['team_name' => 'Inter Miami']],
        ]]], 200)]);
        Log::spy();

        $response = $this->actingAs($user)->getJson(route('squads.players.search', ['league' => $league, 'q' => 'messi']));
        $response->assertOk()->assertJsonPath('results.0.id', 5001)->assertJsonPath('results.0.known_name', 'Lionel Messi')->assertJsonPath('results.0.nationality', 'Argentina')->assertJsonPath('results.0.age', 39)->assertJsonPath('results.0.height_cm', 170)->assertJsonPath('results.0.position', 'Forward')->assertJsonPath('results.0.image_url', 'https://example.test/messi.png');
        $this->assertDatabaseHas('football_players', ['provider_id' => 5001, 'known_name' => 'Lionel Messi', 'height_cm' => 170]);
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'Footballdata players search response' && $context['response']['data']['players'][0]['player_id'] === 5001);
    }

    public function test_provider_search_sends_q_and_search_filters(): void
    {
        config(['services.footballdata.api_key' => 'test-key']);
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        Http::fake(['https://footballdata.io/*' => Http::response(['success' => true, 'data' => [], 'meta' => ['filters' => ['search' => 'messi']]], 200)]);

        $this->actingAs($user)->getJson(route('squads.players.search', ['league' => $league, 'q' => 'messi']));

        Http::assertSent(fn ($request) => $request->url() === 'https://footballdata.io/api/v1/players?q=messi&page=1&limit=10');
    }

    public function test_provider_errors_are_returned_as_a_controlled_search_error(): void
    {
        config(['services.footballdata.api_key' => 'test-key']);
        $user = User::factory()->create(); $league = League::factory()->create(); $league->users()->attach($user);
        Http::fake(['https://footballdata.io/*' => Http::response(['success' => false], 500)]);

        $this->actingAs($user)->getJson(route('squads.players.search', ['league' => $league, 'q' => 'suarez']))->assertStatus(503);
    }
}
