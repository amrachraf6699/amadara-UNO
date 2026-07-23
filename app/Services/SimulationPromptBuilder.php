<?php

namespace App\Services;

use App\Models\LeagueSimulation;

class SimulationPromptBuilder
{
    public const VERSION = 'amadara-v1';

    public function payload(LeagueSimulation $simulation): array
    {
        $league = $simulation->league()->with('users')->firstOrFail();
        $squads = $league->effectiveSelections()->get()->groupBy('user_id')->map(fn ($selections, $userId) => [
            'user_id' => (int) $userId,
            'players' => $selections->map(fn ($selection) => [
                'player_id' => (int) $selection->player_id,
                'slot_key' => $selection->slot_key,
                'role' => $selection->role,
                ...($selection->player_data ?? []),
            ])->values()->all(),
        ])->values()->all();

        return [
            'league' => ['id' => $league->id, 'name' => $league->name, 'status' => $league->status],
            'squads' => $squads,
            'power_card_resolution' => \DB::table('league_card_resolutions')->where('league_id', $league->id)->get()->map(fn ($row) => [
                'power_card_id' => $row->power_card_id,
                'card_type' => $row->card_type,
                'applied' => (bool) $row->applied,
                'reason' => $row->reason,
                'metadata' => json_decode($row->metadata ?: '{}', true),
            ])->all(),
            'fixtures' => $simulation->matches()->orderBy('id')->get()->map(fn ($match) => [
                'fixture_id' => $match->fixture_id,
                'home_user_id' => $match->home_user_id,
                'away_user_id' => $match->away_user_id,
                'leg' => $match->leg,
                'boost_user_id' => $match->boost_user_id,
            ])->all(),
        ];
    }

    public function build(array $payload): string
    {
        $input = $this->json($payload);
        return <<<PROMPT
You are the deterministic football league simulation engine for Amadara UNO.

Simulate the complete league using ONLY the supplied JSON input, resolved squads, resolved power cards, and fixtures. This is a fictional fantasy simulation. Evaluate every player at their absolute peak career condition, not current form, current age, injuries, or recent form. You may use broad football knowledge to estimate peak ability and role suitability, but estimated values are simulation judgments, not factual database fields.

Return valid JSON only. Do not return Markdown, commentary, additional fixtures, duplicate fixtures, or unresolved card decisions. Do not calculate final league points; the application calculates and verifies points locally.

RULES:
1. Every supplied fixture must appear exactly once.
2. Every pair appears exactly twice, once in each home/away direction.
3. Use every player supplied in each formation.
4. Evaluate peak technical ability, peak physical ability, tactical role fit, formation balance, defense, midfield control, attack, goalkeeper influence, chemistry, matchup, and home advantage.
5. Use realistic non-negative integer football scores.
6. Equal scores require DRAW; higher home score requires HOME_WIN; higher away score requires AWAY_WIN.
7. Boost affects only the booster’s home fixture against the selected opponent.
8. Do not favor fame alone.
9. Keep one consistent simulation logic across all fixtures.
10. Return every goal scorer for each match with the scoring player's integer user_id, integer player_id, and a realistic integer minute from 1 to 120. The number of home goal scorers must equal home_score and the number of away goal scorers must equal away_score. Use an empty array when a team scores zero.
11. Return a short fictional summary of each match, maximum 280 characters.

INPUT JSON:
{$input}

REQUIRED OUTPUT SHAPE:
{
  "simulation_version":"amadara-v1",
  "league_id":0,
  "assumptions":["short assumption"],
  "player_evaluations":[{"player_id":0,"peak_role":"goalkeeper|defender|midfielder|forward|coach","peak_rating":0,"role_fit":0,"fitness_at_peak":0,"short_reason":"one sentence"}],
  "matches":[{"fixture_id":"stable-id","home_user_id":0,"away_user_id":0,"home_score":0,"away_score":0,"result":"HOME_WIN|DRAW|AWAY_WIN","goal_scorers":[{"user_id":0,"player_id":0,"minute":0}],"home_performance_rating":0,"away_performance_rating":0,"decisive_factors":["factor"],"player_impacts":[{"player_id":0,"user_id":0,"impact":0,"reason":"short reason"}],"narrative":"Maximum 280 characters."}],
  "standings_projection":[{"user_id":0,"played":0,"wins":0,"draws":0,"losses":0,"goals_for":0,"goals_against":0,"goal_difference":0}]
}

VALIDATION:
- Use only IDs and fixture IDs from the input.
- Return exactly one match per supplied fixture and every supplied user exactly once in standings_projection.
- Scores must be non-negative integers.
- Ratings must be integers from 0 to 100.
- Impact must be an integer from -100 to 100.
- goal_scorers must be an array; every scorer must belong to the correct home or away squad, and scorer count must equal the corresponding score.
- Every player impact must belong to that fixture’s supplied squad.
- If input cannot be satisfied, return {"error":{"code":"INVALID_SIMULATION_INPUT","message":"short explanation"}}.
PROMPT;
    }

    private function json(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
