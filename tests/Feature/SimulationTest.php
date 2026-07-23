<?php

namespace Tests\Feature;

use Amrachraf6699\LaravelGeminiAi\Facades\GeminiAi;
use App\Models\League;
use App\Models\LeagueSimulation;
use App\Models\User;
use App\Services\LeagueSimulationService;
use App\Services\SimulationPromptBuilder;
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

    private function richMatch($match, User $home, User $away): array
    {
        $homeIsOwner = (int) $match->home_user_id === (int) $home->id;
        $homeScore = $homeIsOwner ? 2 : 0;
        $awayScore = $homeIsOwner ? 0 : 2;
        $homePlayer = $homeIsOwner ? 1 : 13;
        $awayPlayer = $homeIsOwner ? 13 : 1;
        $homeScorers = $homeScore ? [['user_id' => $match->home_user_id, 'player_id' => $homePlayer, 'minute' => 12, 'description' => 'A composed finish.'], ['user_id' => $match->home_user_id, 'player_id' => $homePlayer + 1, 'minute' => 67, 'description' => 'A late run is rewarded.']] : [];
        $awayScorers = $awayScore ? [['user_id' => $match->away_user_id, 'player_id' => $awayPlayer, 'minute' => 34, 'description' => 'A quick transition ends in a goal.'], ['user_id' => $match->away_user_id, 'player_id' => $awayPlayer + 1, 'minute' => 81, 'description' => 'A precise finish from the edge.']] : [];
        $winnerUser = $homeScore > $awayScore ? $match->home_user_id : $match->away_user_id;
        $winnerPlayer = $homeScore > $awayScore ? $homePlayer : $awayPlayer;
        $events = collect(range(1, 10))->map(fn ($index) => [
            'minute' => $index * 7,
            'type' => $index === 2 || $index === 8 ? 'goal' : 'tactical',
            'team_user_id' => $index === 2 || $index === 8 ? $winnerUser : ($index % 2 ? $match->home_user_id : $match->away_user_id),
            'player_id' => $index === 2 || $index === 8 ? $winnerPlayer : ($index % 2 ? $homePlayer : $awayPlayer),
            'description' => 'The teams adjust their shape and tempo.',
        ])->all();

        return [
            'fixture_id' => $match->fixture_id, 'home_user_id' => $match->home_user_id, 'away_user_id' => $match->away_user_id,
            'home_score' => $homeScore, 'away_score' => $awayScore, 'result' => $homeScore > $awayScore ? 'HOME_WIN' : 'AWAY_WIN',
            'home_goal_scorers' => $homeScorers, 'away_goal_scorers' => $awayScorers,
            'tactical_analysis' => [
                'home_plan' => ['approach' => 'balanced', 'build_up' => 'short', 'pressing' => 'medium', 'defensive_line' => 'medium', 'width' => 'wide', 'transition' => 'quick', 'set_piece_plan' => 'near-post', 'coach_intent' => 'control'],
                'away_plan' => ['approach' => 'direct', 'build_up' => 'mixed', 'pressing' => 'medium', 'defensive_line' => 'medium', 'width' => 'narrow', 'transition' => 'counter', 'set_piece_plan' => 'deep runs', 'coach_intent' => 'stay compact'],
                'chemistry' => ['home' => 75, 'away' => 72, 'home_links' => [], 'away_links' => []],
                'key_battles' => [['home_player_id' => $homePlayer, 'away_player_id' => $awayPlayer, 'area' => 'central transition', 'edge' => 'HOME', 'reason' => 'Better spacing.']],
                'phases' => [['label' => 'opening', 'start_minute' => 1, 'end_minute' => 30, 'dominant_user_id' => $match->home_user_id, 'momentum' => 'home pressure', 'tactical_note' => 'The home side builds patiently.'], ['label' => 'middle', 'start_minute' => 31, 'end_minute' => 65, 'dominant_user_id' => $match->away_user_id, 'momentum' => 'away response', 'tactical_note' => 'Transitions become more important.'], ['label' => 'closing', 'start_minute' => 66, 'end_minute' => 90, 'dominant_user_id' => $match->home_user_id, 'momentum' => 'home control', 'tactical_note' => 'The lead is protected.']],
                'coach_decisions' => [['minute' => 55, 'user_id' => $match->home_user_id, 'decision' => 'Raise the press', 'reason' => 'Recover possession higher.'], ['minute' => 70, 'user_id' => $match->away_user_id, 'decision' => 'Add a forward', 'reason' => 'Chase the result.']],
            ],
            'match_stats' => ['home' => ['possession' => 56, 'shots' => 14, 'shots_on_target' => 6, 'expected_goals' => 1.8, 'corners' => 6, 'fouls' => 10, 'offsides' => 2, 'saves' => 3, 'big_chances' => 3], 'away' => ['possession' => 44, 'shots' => 9, 'shots_on_target' => 3, 'expected_goals' => 0.8, 'corners' => 4, 'fouls' => 12, 'offsides' => 1, 'saves' => 4, 'big_chances' => 1]],
            'events' => $events, 'home_performance_rating' => 80, 'away_performance_rating' => 70,
            'decisive_factors' => ['formation balance'], 'player_impacts' => [['user_id' => $match->home_user_id, 'player_id' => $homePlayer, 'impact' => 25, 'reason' => 'Created space between the lines.']],
            'narrative' => 'A detailed fictional test match report with tactical adjustments, momentum changes, and decisive finishing.', 'display_narrative' => 'A tactical test match decided by better spacing and finishing.',
        ];
    }

    public function test_v2_payload_contains_team_formation_coach_players_and_tactical_inputs(): void
    {
        [$league] = $this->leagueWithSquads();
        $simulation = app(LeagueSimulationService::class)->prepare($league);
        $payload = app(SimulationPromptBuilder::class)->payload($simulation);

        $this->assertSame(SimulationPromptBuilder::VERSION, 'amadara-v2');
        $this->assertCount(2, $payload['squads']);
        $this->assertArrayHasKey('team_name', $payload['squads'][0]);
        $this->assertArrayHasKey('formation', $payload['squads'][0]);
        $this->assertNotEmpty($payload['squads'][0]['coach']);
        $this->assertNotEmpty($payload['squads'][0]['players']);
        $this->assertArrayHasKey('tactical_inputs', $payload['squads'][0]);
        $this->assertStringContainsString('chemistry', app(SimulationPromptBuilder::class)->build($payload));
        $this->assertStringContainsString('tactical_analysis', app(SimulationPromptBuilder::class)->build($payload));
    }

    public function test_double_round_robin_simulation_publishes_results_and_local_points(): void
    {
        [$league, $home, $away] = $this->leagueWithSquads();
        $simulation = app(LeagueSimulationService::class)->prepare($league);
        $matches = $simulation->matches()->get();
        $output = ['simulation_version' => 'amadara-v2', 'league_id' => $league->id, 'assumptions' => [], 'player_evaluations' => [], 'matches' => $matches->map(fn ($match) => $this->richMatch($match, $home, $away))->all(), 'standings_projection' => [['user_id' => $home->id], ['user_id' => $away->id]]];
        GeminiAi::shouldReceive('generateText')->once()->andReturn(json_encode($output));

        app(LeagueSimulationService::class)->run($simulation->fresh());
        $this->assertDatabaseHas('league_simulations', ['id' => $simulation->id, 'status' => LeagueSimulation::COMPLETED]);
        $this->assertDatabaseHas('leagues', ['id' => $league->id, 'status' => League::STATUS_FINISHED]);
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

    public function test_event_zero_player_sentinels_are_accepted(): void
    {
        [$league, $home, $away] = $this->leagueWithSquads();
        $simulation = app(LeagueSimulationService::class)->prepare($league);
        $output = ['simulation_version' => 'amadara-v2', 'league_id' => $league->id, 'assumptions' => [], 'player_evaluations' => [], 'matches' => collect($simulation->matches)->map(function ($match) use ($home, $away) {
            $result = $this->richMatch($match, $home, $away);
            $result['events'][0]['player_id'] = 0;
            $result['events'][0]['related_player_id'] = 0;
            return $result;
        })->all(), 'standings_projection' => [['user_id' => $home->id], ['user_id' => $away->id]]];
        GeminiAi::shouldReceive('generateText')->once()->andReturn(json_encode($output));

        app(LeagueSimulationService::class)->run($simulation->fresh());

        $this->assertDatabaseHas('league_simulations', ['id' => $simulation->id, 'status' => LeagueSimulation::COMPLETED]);
    }

    public function test_model_ten_point_ratings_and_impacts_are_normalized(): void
    {
        [$league, $home, $away] = $this->leagueWithSquads();
        $simulation = app(LeagueSimulationService::class)->prepare($league);
        $output = ['simulation_version' => 'amadara-v2', 'league_id' => $league->id, 'assumptions' => [], 'player_evaluations' => [], 'matches' => collect($simulation->matches)->map(function ($match) use ($home, $away) {
            $result = $this->richMatch($match, $home, $away);
            $result['home_performance_rating'] = 8.8;
            $result['away_performance_rating'] = 6.9;
            $result['player_impacts'][0]['impact'] = 9.2;
            return $result;
        })->all(), 'standings_projection' => [['user_id' => $home->id], ['user_id' => $away->id]]];
        GeminiAi::shouldReceive('generateText')->once()->andReturn(json_encode($output));

        app(LeagueSimulationService::class)->run($simulation->fresh());

        $this->assertDatabaseHas('league_simulations', ['id' => $simulation->id, 'status' => LeagueSimulation::COMPLETED]);
    }

    public function test_a_failed_simulation_can_be_prepared_again_without_duplicate_fixtures(): void
    {
        [$league] = $this->leagueWithSquads();
        $first = app(LeagueSimulationService::class)->prepare($league);
        $first->update(['status' => LeagueSimulation::FAILED]);

        $second = app(LeagueSimulationService::class)->prepare($league->fresh());

        $this->assertNotSame($first->id, $second->id);
        $this->assertDatabaseCount('league_matches', 4);
        $this->assertDatabaseHas('league_matches', [
            'simulation_id' => $second->id,
            'fixture_id' => "league:{$league->id}:1:2:leg1",
        ]);
    }
}
