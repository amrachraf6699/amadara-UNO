<?php

namespace App\Services;

use Amrachraf6699\LaravelGeminiAi\Facades\GeminiAi;
use App\Exceptions\InvalidSimulationResult;
use App\Models\League;
use App\Models\LeaguePowerCard;
use App\Models\LeagueSimulation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LeagueSimulationService
{
    public function __construct(
        private readonly PowerCardResolver $cards,
        private readonly SimulationPromptBuilder $prompts,
        private readonly SimulationResultValidator $validator,
    ) {}

    public function prepare(League $league): LeagueSimulation
    {
        $this->cards->resolve($league);
        return DB::transaction(function () use ($league): LeagueSimulation {
            $simulation = $league->simulations()->create(['status' => LeagueSimulation::PENDING, 'prompt_version' => SimulationPromptBuilder::VERSION]);
            $members = $league->users()->orderBy('users.id')->get();
            foreach ($members as $leftIndex => $left) {
                for ($rightIndex = $leftIndex + 1; $rightIndex < $members->count(); $rightIndex++) {
                    $right = $members[$rightIndex];
                    $this->createMatch($simulation, $left->id, $right->id, 1);
                    $this->createMatch($simulation, $right->id, $left->id, 2);
                }
            }
            return $simulation;
        });
    }

    public function run(LeagueSimulation $simulation): void
    {
        $simulation->update([
            'status' => LeagueSimulation::RUNNING,
            'attempt_count' => $simulation->attempt_count + 1,
            'started_at' => now(),
        ]);
        $payload = $this->prompts->payload($simulation);
        $prompt = $this->prompts->build($payload);
        $model = config('gemini.models.text', env('GEMINI_TEXT_MODEL', 'gemini-2.0-flash'));
        $options = ['model' => $model, 'raw' => false, 'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 50000,
            'responseMimeType' => 'application/json',
        ]];
        $simulation->update([
            'prompt_hash' => hash('sha256', $prompt),
            'model' => $model,
            'generation_options' => $options['generationConfig'],
            'request_payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ]);

        try {
            $raw = GeminiAi::generateText($prompt, $options);
            $simulation->update(['raw_response' => is_string($raw) ? $raw : json_encode($raw, JSON_THROW_ON_ERROR)]);
            $decoded = $this->decode($raw);
            $errors = $this->validator->validate($decoded, $payload);
            if ($errors) throw new InvalidSimulationResult($errors);
            $this->publish($simulation, $decoded);
        } catch (Throwable $exception) {
            $errors = $exception instanceof InvalidSimulationResult ? $exception->errors : [$exception->getMessage()];
            $simulation->update(['status' => LeagueSimulation::FAILED, 'validation_errors' => $errors, 'failed_at' => now()]);
            throw $exception;
        }
    }

    private function createMatch(LeagueSimulation $simulation, int $home, int $away, int $leg): void
    {
        $boost = $simulation->league->powerCards()->where('card_type', LeaguePowerCard::BOOST)->where('user_id', $home)->where('target_user_id', $away)->where('resolution_status', 'applied')->first();
        $simulation->matches()->create([
            'league_id' => $simulation->league_id,
            'fixture_id' => "league:{$simulation->league_id}:{$home}:{$away}:leg{$leg}",
            'home_user_id' => $home,
            'away_user_id' => $away,
            'leg' => $leg,
            'boost_user_id' => $boost?->user_id,
        ]);
    }

    private function decode(mixed $raw): array
    {
        $text = is_array($raw) ? json_encode($raw, JSON_THROW_ON_ERROR) : trim((string) $raw);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        $result = json_decode($text, true);
        if (! is_array($result)) throw new InvalidSimulationResult(['Response was not valid JSON.']);
        return $result;
    }

    private function publish(LeagueSimulation $simulation, array $result): void
    {
        DB::transaction(function () use ($simulation, $result): void {
            $standings = [];
            foreach ($simulation->league->users()->orderBy('users.id')->get() as $user) $standings[$user->id] = ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0];
            foreach ($result['matches'] as $matchData) {
                $match = $simulation->matches()->where('fixture_id', $matchData['fixture_id'])->firstOrFail();
                $home = (int) $matchData['home_user_id']; $away = (int) $matchData['away_user_id'];
                $homeScore = (int) $matchData['home_score']; $awayScore = (int) $matchData['away_score'];
                $homeWin = $matchData['result'] === 'HOME_WIN'; $awayWin = $matchData['result'] === 'AWAY_WIN';
                $boostedHome = (int) $match->boost_user_id === $home;
                $homePoints = $homeWin ? ($boostedHome ? 4 : 3) : ($awayWin && $boostedHome ? -1 : 0);
                $awayPoints = $awayWin ? 3 : 0;
                $match->update([
                    'status' => 'completed', 'home_score' => $homeScore, 'away_score' => $awayScore,
                    'goal_scorers' => $matchData['goal_scorers'],
                    'result' => $matchData['result'], 'home_points' => $homePoints, 'away_points' => $awayPoints,
                    'decisive_factors' => $matchData['decisive_factors'] ?? [], 'player_impacts' => $matchData['player_impacts'] ?? [],
                    'narrative' => Str::limit((string) ($matchData['narrative'] ?? ''), 280, ''), 'raw_data' => $matchData,
                ]);
                $this->recordStanding($standings[$home], $homeScore, $awayScore, $homeWin, ! $homeWin && ! $awayWin, $homePoints);
                $this->recordStanding($standings[$away], $awayScore, $homeScore, $awayWin, ! $homeWin && ! $awayWin, $awayPoints);
            }
            foreach ($standings as $userId => $standing) $simulation->standings()->updateOrCreate(['user_id' => $userId], ['league_id' => $simulation->league_id, ...$standing]);
            $simulation->update(['status' => LeagueSimulation::COMPLETED, 'normalized_response' => $result, 'completed_at' => now()]);
            $simulation->league()->update(['status' => League::STATUS_FINISHED]);
        });
    }

    private function recordStanding(array &$standing, int $for, int $against, bool $win, bool $draw, int $points): void
    {
        $standing['played']++; $standing['goals_for'] += $for; $standing['goals_against'] += $against; $standing['goal_difference'] += $for - $against; $standing['points'] += $points;
        if ($win) $standing['wins']++; elseif ($draw) $standing['draws']++; else $standing['losses']++;
    }
}
