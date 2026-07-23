<?php

namespace App\Services;

class SimulationResultValidator
{
    public function validate(array $result, array $payload): array
    {
        $errors = [];
        $fixtures = collect($payload['fixtures'])->keyBy('fixture_id');
        $users = collect($payload['squads'])->pluck('user_id')->map(fn ($id) => (int) $id)->values();
        $playersByUser = collect($payload['squads'])->mapWithKeys(fn (array $squad) => [(int) $squad['user_id'] => collect($squad['players'])->pluck('player_id')->map(fn ($id) => (int) $id)->all()]);
        if (isset($result['error'])) $errors[] = $result['error']['message'] ?? 'Model rejected the simulation input.';
        if (($result['simulation_version'] ?? null) !== SimulationPromptBuilder::VERSION) $errors[] = 'Unexpected simulation version.';
        if ((int) ($result['league_id'] ?? 0) !== (int) $payload['league']['id']) $errors[] = 'Wrong league ID.';
        if (! is_array($result['matches'] ?? null) || count($result['matches']) !== $fixtures->count()) $errors[] = 'The result does not contain exactly all fixtures.';
        $seen = [];
        foreach (($result['matches'] ?? []) as $match) {
            $fixtureId = $match['fixture_id'] ?? null;
            if (! $fixtureId || ! $fixtures->has($fixtureId) || isset($seen[$fixtureId])) { $errors[] = 'Invalid or duplicate fixture ID.'; continue; }
            $seen[$fixtureId] = true;
            $fixture = $fixtures[$fixtureId];
            foreach (['home_user_id', 'away_user_id'] as $field) if ((int) ($match[$field] ?? -1) !== (int) $fixture[$field]) $errors[] = "Fixture {$fixtureId} has the wrong {$field}.";
            $home = $match['home_score'] ?? null; $away = $match['away_score'] ?? null;
            if (! is_int($home) || ! is_int($away) || $home < 0 || $away < 0) $errors[] = "Fixture {$fixtureId} has invalid scores.";
            $expected = $home === $away ? 'DRAW' : ($home > $away ? 'HOME_WIN' : 'AWAY_WIN');
            if (($match['result'] ?? null) !== $expected) $errors[] = "Fixture {$fixtureId} has an inconsistent result.";
            $homeScorers = $match['home_goal_scorers'] ?? null;
            $awayScorers = $match['away_goal_scorers'] ?? null;
            if ($homeScorers === null || $awayScorers === null) {
                $legacyScorers = $match['goal_scorers'] ?? null;
                $homeScorers = is_array($legacyScorers) ? array_values(array_filter($legacyScorers, fn ($scorer) => (int) ($scorer['user_id'] ?? 0) === (int) $fixture['home_user_id'])) : null;
                $awayScorers = is_array($legacyScorers) ? array_values(array_filter($legacyScorers, fn ($scorer) => (int) ($scorer['user_id'] ?? 0) === (int) $fixture['away_user_id'])) : null;
            }
            if (! is_array($homeScorers) || ! is_array($awayScorers)) $errors[] = "Fixture {$fixtureId} has invalid goal scorers.";
            else {
                $homeCount = 0; $awayCount = 0;
                foreach (['home' => $homeScorers, 'away' => $awayScorers] as $side => $scorers) foreach ($scorers as $scorer) {
                    $scorerUser = (int) ($scorer['user_id'] ?? -1); $scorerPlayer = (int) ($scorer['player_id'] ?? -1);
                    if ($side === 'home' && $scorerUser === (int) $fixture['home_user_id']) $homeCount++; elseif ($side === 'away' && $scorerUser === (int) $fixture['away_user_id']) $awayCount++; else $errors[] = "Fixture {$fixtureId} has a scorer in the wrong team list.";
                    if (! in_array($scorerPlayer, $playersByUser[$scorerUser] ?? [], true)) $errors[] = "Fixture {$fixtureId} has an invalid goal scorer.";
                    if (! is_int($scorer['minute'] ?? null) || $scorer['minute'] < 1 || $scorer['minute'] > 120) $errors[] = "Fixture {$fixtureId} has an invalid goal minute.";
                }
                if ($homeCount !== (int) $home || $awayCount !== (int) $away) $errors[] = "Fixture {$fixtureId} goal scorers do not match the score.";
            }
            $events = $match['events'] ?? null;
            if ($events !== null && ! is_array($events)) $errors[] = "Fixture {$fixtureId} has invalid match events.";
            elseif (is_array($events) && (count($events) < 6 || count($events) > 14)) $errors[] = "Fixture {$fixtureId} must contain between 6 and 14 match events.";
            elseif (is_array($events)) {
                $lastMinute = 0;
                foreach ($events as $event) {
                    $eventUser = (int) ($event['team_user_id'] ?? -1); $eventPlayer = $event['player_id'] ?? null;
                    if (! is_int($event['minute'] ?? null) || $event['minute'] < 1 || $event['minute'] > 120 || $event['minute'] < $lastMinute) $errors[] = "Fixture {$fixtureId} has invalid event timing.";
                    $lastMinute = (int) ($event['minute'] ?? 0);
                    if (! in_array($eventUser, [(int) $fixture['home_user_id'], (int) $fixture['away_user_id']], true)) $errors[] = "Fixture {$fixtureId} has an event from an invalid team.";
                    if ($eventPlayer !== null && ! in_array((int) $eventPlayer, $playersByUser[$eventUser] ?? [], true)) $errors[] = "Fixture {$fixtureId} has an event with an invalid player.";
                    if (! is_string($event['type'] ?? null) || ! is_string($event['description'] ?? null) || trim($event['description']) === '') $errors[] = "Fixture {$fixtureId} has an incomplete event.";
                }
            }
            foreach (['home_performance_rating', 'away_performance_rating'] as $field) if (! is_int($match[$field] ?? null) || $match[$field] < 0 || $match[$field] > 100) $errors[] = "Fixture {$fixtureId} has an invalid rating.";
            foreach (($match['player_impacts'] ?? []) as $impact) {
                $impactUser = (int) ($impact['user_id'] ?? -1); $impactPlayer = (int) ($impact['player_id'] ?? -1);
                if (! in_array($impactUser, [$fixture['home_user_id'], $fixture['away_user_id']], true) || ! in_array($impactPlayer, $playersByUser[$impactUser] ?? [], true)) $errors[] = "Fixture {$fixtureId} contains an invalid player impact.";
                if (! is_int($impact['impact'] ?? null) || $impact['impact'] < -100 || $impact['impact'] > 100) $errors[] = "Fixture {$fixtureId} contains an invalid impact value.";
            }
        }
        $standingUsers = collect($result['standings_projection'] ?? [])->pluck('user_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($standingUsers !== $users->sort()->values()->all()) $errors[] = 'Standings do not contain every league user exactly once.';
        return array_values(array_unique($errors));
    }
}
