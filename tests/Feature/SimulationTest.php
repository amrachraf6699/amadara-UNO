<?php

namespace Tests\Feature;

use Amrachraf6699\LaravelGeminiAi\Facades\GeminiAi;
use App\Models\League;
use App\Models\LeagueSimulation;
use App\Models\User;
use App\Services\LeagueSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(int $offset = 0): array
    {
        $slots = ['goalkeeper', 'defender_1', 'defender_2', 'defender_3', 'defender_4', 'midfielder_1', 'midfielder_2', 'midfielder_3', 'forward_1', 'forward_2', 'forward_3'];
        return ['formation' => '4-3-3', 'players' => collect($slots)->map(fn ($slot, $index) => ['slot' => $slot, 'player_id' => $offset + $index + 1])->all(), 'coach_player_id' => $offset + 12];
    }

    private function leagueWithSquads(): array
    {
        $home = User::factory()->create(); $away = User::factory()->create();
        $league = League::factory()->create(['owner_id' => $home->id]); $league->users()->attach([$home->id, $away->id]);
        $this->actingAs($home)->postJson(route('squads.store', $league), $this->payload())->assertOk();
        $this->actingAs($away)->postJson(route('squads.store', $league), $this->payload(12))->assertOk();
        return [$league->fresh(), $home, $away];
    }

    public function test_double_round_robin_simulation_publishes_results_and_local_points(): void
    {
        [$league, $home, $away] = $this->leagueWithSquads();
        $simulation = app(LeagueSimulationService::class)->prepare($league);
        $matches = $simulation->matches()->get();
        $output = ['simulation_version' => 'amadara-v1', 'league_id' => $league->id, 'assumptions' => [], 'player_evaluations' => [], 'matches' => $matches->map(fn ($match) => [
            'fixture_id' => $match->fixture_id, 'home_user_id' => $match->home_user_id, 'away_user_id' => $match->away_user_id,
            'home_score' => $match->home_user_id === $home->id ? 2 : 0, 'away_score' => $match->home_user_id === $home->id ? 0 : 2,
            'result' => $match->home_user_id === $home->id ? 'HOME_WIN' : 'AWAY_WIN', 'home_performance_rating' => 80, 'away_performance_rating' => 70,
            'decisive_factors' => ['formation balance'], 'player_impacts' => [], 'narrative' => 'A fictional test match.',
        ])->all(), 'standings_projection' => [['user_id' => $home->id], ['user_id' => $away->id]]];
        GeminiAi::shouldReceive('generateText')->once()->andReturn(json_encode($output));

        app(LeagueSimulationService::class)->run($simulation->fresh());
        $this->assertDatabaseHas('league_simulations', ['id' => $simulation->id, 'status' => LeagueSimulation::COMPLETED]);
        $this->assertDatabaseHas('leagues', ['id' => $league->id, 'status' => League::STATUS_RUNNING]);
        $this->assertDatabaseCount('league_matches', 2);
        $this->assertDatabaseHas('league_standings', ['simulation_id' => $simulation->id, 'user_id' => $home->id, 'points' => 6]);
    }

    public function test_invalid_gemini_json_fails_without_publishing_results(): void
    {
        [$league] = $this->leagueWithSquads();
        $simulation = app(LeagueSimulationService::class)->prepare($league);
        GeminiAi::shouldReceive('generateText')->once()->andReturn('{not-json');

        $this->expectException(\App\Exceptions\InvalidSimulationResult::class);
        app(LeagueSimulationService::class)->run($simulation->fresh());
        $this->assertDatabaseHas('league_simulations', ['id' => $simulation->id, 'status' => LeagueSimulation::FAILED]);
        $this->assertDatabaseHas('league_matches', ['simulation_id' => $simulation->id, 'status' => 'pending']);
        $this->assertDatabaseHas('leagues', ['id' => $league->id, 'status' => League::STATUS_YET_TO_START]);
    }
}
