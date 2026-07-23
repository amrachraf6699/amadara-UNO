<?php

namespace App\Services;

class SimulationResultValidator
{
    private const EVENT_TYPES = ['goal', 'chance', 'save', 'block', 'card', 'substitution', 'injury', 'tactical', 'set_piece', 'momentum', 'missed_chance', 'coach_instruction'];
    private const STAT_FIELDS = ['possession', 'shots', 'shots_on_target', 'expected_goals', 'corners', 'fouls', 'offsides', 'saves', 'big_chances'];

    public function validate(array $result, array $payload): array
    {
        $errors = [];
        $fixtures = collect($payload['fixtures'] ?? [])->keyBy('fixture_id');
        $users = collect($payload['squads'] ?? [])->pluck('user_id')->map(fn ($id) => (int) $id)->values();
        $playersByUser = collect($payload['squads'] ?? [])->mapWithKeys(function (array $squad): array {
            $players = collect($squad['players'] ?? [])->pluck('player_id')->map(fn ($id) => (int) $id)->all();
            if (! empty($squad['coach']['player_id'])) $players[] = (int) $squad['coach']['player_id'];
            return [(int) $squad['user_id'] => array_values(array_unique($players))];
        });

        if (isset($result['error'])) $errors[] = $result['error']['message'] ?? 'Model rejected the simulation input.';
        if (($result['simulation_version'] ?? null) !== SimulationPromptBuilder::VERSION) $errors[] = 'Unexpected simulation version.';
        if ((int) ($result['league_id'] ?? 0) !== (int) ($payload['league']['id'] ?? 0)) $errors[] = 'Wrong league ID.';
        $allPlayerIds = $playersByUser->flatten()->map(fn ($id) => (int) $id)->all();
        foreach (($result['player_evaluations'] ?? []) as $evaluation) {
            if (! is_array($evaluation) || ! in_array((int) ($evaluation['player_id'] ?? -1), $allPlayerIds, true) || ! in_array($evaluation['peak_role'] ?? null, ['goalkeeper', 'defender', 'midfielder', 'forward', 'coach'], true) || ! is_int($evaluation['peak_rating'] ?? null) || $evaluation['peak_rating'] < 0 || $evaluation['peak_rating'] > 100 || ! is_int($evaluation['role_fit'] ?? null) || $evaluation['role_fit'] < 0 || $evaluation['role_fit'] > 100 || ! is_int($evaluation['fitness_at_peak'] ?? null) || $evaluation['fitness_at_peak'] < 0 || $evaluation['fitness_at_peak'] > 100) $errors[] = 'The result contains an invalid player evaluation.';
        }
        if (! is_array($result['matches'] ?? null) || count($result['matches']) !== $fixtures->count()) $errors[] = 'The result does not contain exactly all fixtures.';

        $seen = [];
        foreach (($result['matches'] ?? []) as $match) {
            if (! is_array($match)) { $errors[] = 'A match result is not an object.'; continue; }
            $fixtureId = $match['fixture_id'] ?? null;
            if (! $fixtureId || ! $fixtures->has($fixtureId) || isset($seen[$fixtureId])) { $errors[] = 'Invalid or duplicate fixture ID.'; continue; }
            $seen[$fixtureId] = true;
            $fixture = $fixtures[$fixtureId];
            $homeUser = (int) ($fixture['home_user_id'] ?? 0); $awayUser = (int) ($fixture['away_user_id'] ?? 0);
            foreach (['home_user_id' => $homeUser, 'away_user_id' => $awayUser] as $field => $expectedUser) if ((int) ($match[$field] ?? -1) !== $expectedUser) $errors[] = "Fixture {$fixtureId} has the wrong {$field}.";

            $home = $match['home_score'] ?? null; $away = $match['away_score'] ?? null;
            if (! is_int($home) || ! is_int($away) || $home < 0 || $away < 0 || $home > 15 || $away > 15) $errors[] = "Fixture {$fixtureId} has invalid scores.";
            $expected = is_int($home) && is_int($away) ? ($home === $away ? 'DRAW' : ($home > $away ? 'HOME_WIN' : 'AWAY_WIN')) : null;
            if ($expected !== null && ($match['result'] ?? null) !== $expected) $errors[] = "Fixture {$fixtureId} has an inconsistent result.";

            foreach (['home' => $homeUser, 'away' => $awayUser] as $side => $teamUser) {
                $scorers = $match[$side.'_goal_scorers'] ?? null;
                if ($scorers === null && is_array($match['goal_scorers'] ?? null)) {
                    $scorers = array_values(array_filter($match['goal_scorers'], fn ($scorer) => (int) ($scorer['user_id'] ?? 0) === $teamUser));
                }
                if (! is_array($scorers)) { $errors[] = "Fixture {$fixtureId} has invalid {$side} goal scorers."; continue; }
                if (is_int($home) && is_int($away) && count($scorers) !== ($side === 'home' ? $home : $away)) $errors[] = "Fixture {$fixtureId} goal scorers do not match the score.";
                foreach ($scorers as $scorer) {
                    $player = (int) ($scorer['player_id'] ?? -1);
                    if ((int) ($scorer['user_id'] ?? -1) !== $teamUser || ! in_array($player, $playersByUser[$teamUser] ?? [], true)) $errors[] = "Fixture {$fixtureId} has a scorer in the wrong team or an invalid player.";
                    if (! is_int($scorer['minute'] ?? null) || $scorer['minute'] < 1 || $scorer['minute'] > 120 || ! is_string($scorer['description'] ?? null)) $errors[] = "Fixture {$fixtureId} has an invalid goal scorer entry.";
                }
            }

            $analysis = $match['tactical_analysis'] ?? null;
            if (! is_array($analysis)) $errors[] = "Fixture {$fixtureId} has no tactical analysis.";
            else {
                foreach (['home_plan', 'away_plan'] as $plan) {
                    if (! is_array($analysis[$plan] ?? null)) { $errors[] = "Fixture {$fixtureId} has an invalid {$plan}."; continue; }
                    foreach (['approach', 'build_up', 'pressing', 'defensive_line', 'width', 'transition', 'set_piece_plan', 'coach_intent'] as $field) if (! is_string($analysis[$plan][$field] ?? null) || trim($analysis[$plan][$field]) === '') $errors[] = "Fixture {$fixtureId} has an incomplete {$plan}.";
                }
                $chemistry = $analysis['chemistry'] ?? [];
                foreach (['home', 'away'] as $side) if (! is_int($chemistry[$side] ?? null) || $chemistry[$side] < 0 || $chemistry[$side] > 100) $errors[] = "Fixture {$fixtureId} has invalid {$side} chemistry.";
                foreach (($analysis['key_battles'] ?? []) as $battle) if (! is_array($battle) || ! in_array((int) ($battle['home_player_id'] ?? -1), $playersByUser[$homeUser] ?? [], true) || ! in_array((int) ($battle['away_player_id'] ?? -1), $playersByUser[$awayUser] ?? [], true)) $errors[] = "Fixture {$fixtureId} has invalid key battle players.";
                $phases = $analysis['phases'] ?? [];
                if (! is_array($phases) || count($phases) < 3) $errors[] = "Fixture {$fixtureId} needs at least three match phases.";
                $lastPhase = 0;
                foreach (is_array($phases) ? $phases : [] as $phase) {
                    $start = $phase['start_minute'] ?? null; $end = $phase['end_minute'] ?? null; $dominant = $phase['dominant_user_id'] ?? null;
                    if (! is_int($start) || ! is_int($end) || $start < 1 || $end > 120 || $start > $end || $start < $lastPhase || ($dominant !== null && ! in_array((int) $dominant, [$homeUser, $awayUser], true))) $errors[] = "Fixture {$fixtureId} has invalid phase ordering or team.";
                    $lastPhase = is_int($end) ? $end : $lastPhase;
                }
                foreach (($analysis['coach_decisions'] ?? []) as $decision) if (! is_array($decision) || ! is_int($decision['minute'] ?? null) || $decision['minute'] < 1 || $decision['minute'] > 120 || ! in_array((int) ($decision['user_id'] ?? -1), [$homeUser, $awayUser], true) || ! is_string($decision['decision'] ?? null) || ! is_string($decision['reason'] ?? null)) $errors[] = "Fixture {$fixtureId} has an invalid coach decision.";
            }

            $stats = $match['match_stats'] ?? null;
            if (! is_array($stats) || ! is_array($stats['home'] ?? null) || ! is_array($stats['away'] ?? null)) $errors[] = "Fixture {$fixtureId} has invalid match statistics.";
            else {
                foreach (['home', 'away'] as $side) {
                    foreach (self::STAT_FIELDS as $field) if (! is_int($stats[$side][$field] ?? null) && ! is_float($stats[$side][$field] ?? null)) $errors[] = "Fixture {$fixtureId} has an invalid {$side} {$field}.";
                    if (($stats[$side]['possession'] ?? -1) < 0 || ($stats[$side]['possession'] ?? 101) > 100 || ($stats[$side]['shots'] ?? -1) < 0 || ($stats[$side]['shots'] ?? 41) > 40 || ($stats[$side]['shots_on_target'] ?? -1) < 0 || ($stats[$side]['shots_on_target'] ?? 0) > ($stats[$side]['shots'] ?? 0) || ($stats[$side]['expected_goals'] ?? -1) < 0 || ($stats[$side]['expected_goals'] ?? 11) > 10 || ($stats[$side]['corners'] ?? -1) < 0 || ($stats[$side]['corners'] ?? 26) > 25 || ($stats[$side]['fouls'] ?? -1) < 0 || ($stats[$side]['fouls'] ?? 41) > 40 || ($stats[$side]['offsides'] ?? -1) < 0 || ($stats[$side]['offsides'] ?? 16) > 15 || ($stats[$side]['saves'] ?? -1) < 0 || ($stats[$side]['saves'] ?? 21) > 20 || ($stats[$side]['big_chances'] ?? -1) < 0 || ($stats[$side]['big_chances'] ?? 0) > ($stats[$side]['shots'] ?? 0)) $errors[] = "Fixture {$fixtureId} has out-of-range {$side} statistics.";
                }
                if (($stats['home']['possession'] ?? -1) + ($stats['away']['possession'] ?? -1) !== 100) $errors[] = "Fixture {$fixtureId} possession does not total 100.";
            }

            $events = $match['events'] ?? null;
            if (! is_array($events) || count($events) < 10 || count($events) > 18) $errors[] = "Fixture {$fixtureId} must contain between 10 and 18 match events.";
            $lastMinute = 0;
            $eventGoals = [$homeUser => 0, $awayUser => 0];
            foreach (is_array($events) ? $events : [] as $event) {
                $eventUser = (int) ($event['team_user_id'] ?? -1); $eventPlayer = $event['player_id'] ?? null; $relatedPlayer = $event['related_player_id'] ?? null;
                if (! is_int($event['minute'] ?? null) || $event['minute'] < 1 || $event['minute'] > 120 || $event['minute'] < $lastMinute || ! in_array($event['type'] ?? null, self::EVENT_TYPES, true) || ! is_string($event['description'] ?? null) || trim($event['description']) === '') $errors[] = "Fixture {$fixtureId} has an invalid event.";
                $lastMinute = (int) ($event['minute'] ?? $lastMinute);
                if (! in_array($eventUser, [$homeUser, $awayUser], true)) $errors[] = "Fixture {$fixtureId} has an event from an invalid team.";
                // The prompt schema uses 0 as the empty participant sentinel for
                // events such as coach instructions. Treat it like null.
                foreach ([$eventPlayer, $relatedPlayer] as $participant) if ($participant !== null && (int) $participant !== 0 && ! in_array((int) $participant, $playersByUser[$eventUser] ?? [], true)) $errors[] = "Fixture {$fixtureId} has an event with an invalid player.";
                if (($event['type'] ?? null) === 'goal' && isset($eventGoals[$eventUser])) $eventGoals[$eventUser]++;
            }
            if (is_int($home) && is_int($away) && ($eventGoals[$homeUser] ?? -1) !== $home || is_int($home) && is_int($away) && ($eventGoals[$awayUser] ?? -1) !== $away) $errors[] = "Fixture {$fixtureId} goal events do not match the score.";
            foreach (['home_performance_rating', 'away_performance_rating'] as $field) if (! is_int($match[$field] ?? null) || $match[$field] < 0 || $match[$field] > 100) $errors[] = "Fixture {$fixtureId} has an invalid rating.";
            foreach (($match['player_impacts'] ?? []) as $impact) {
                $impactUser = (int) ($impact['user_id'] ?? -1); $impactPlayer = (int) ($impact['player_id'] ?? -1);
                if (! in_array($impactUser, [$homeUser, $awayUser], true) || ! in_array($impactPlayer, $playersByUser[$impactUser] ?? [], true) || ! is_int($impact['impact'] ?? null) || $impact['impact'] < -100 || $impact['impact'] > 100 || ! is_string($impact['reason'] ?? null)) $errors[] = "Fixture {$fixtureId} contains an invalid player impact.";
            }
            foreach (['narrative', 'display_narrative'] as $field) if (! is_string($match[$field] ?? null) || trim($match[$field]) === '') $errors[] = "Fixture {$fixtureId} has no {$field}.";
        }

        $standingUsers = collect($result['standings_projection'] ?? [])->pluck('user_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($standingUsers !== $users->sort()->values()->all()) $errors[] = 'Standings do not contain every league user exactly once.';
        return array_values(array_unique($errors));
    }
}
