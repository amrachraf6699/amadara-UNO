<?php

namespace App\Services;

use App\Models\LeagueSimulation;
use Illuminate\Support\Facades\DB;

class SimulationPromptBuilder
{
    public const VERSION = 'amadara-v2';

    public function payload(LeagueSimulation $simulation): array
    {
        $league = $simulation->league()->with(['users', 'squads'])->firstOrFail();
        $selections = $league->effectiveSelections()->get()->groupBy('user_id');

        $squads = $league->users->map(function ($user) use ($selections, $league): array {
            $teamSelections = $selections->get($user->id, collect());
            $coach = $teamSelections->first(fn ($selection) => $selection->role === 'coach');
            $players = $teamSelections->filter(fn ($selection) => $selection->role !== 'coach');
            $formation = $league->squads->firstWhere('user_id', $user->id)?->formation;
            $playerData = fn ($selection): array => [
                'player_id' => (int) $selection->player_id,
                'slot_key' => $selection->slot_key,
                'role' => $selection->role,
                ...$this->compactPlayerData($selection->player_data ?? []),
            ];

            return [
                'user_id' => (int) $user->id,
                'team_name' => $user->pivot?->team_name ?: $user->name,
                'formation' => $formation,
                'coach' => $coach ? $playerData($coach) : null,
                'players' => $players->map($playerData)->values()->all(),
                'slot_structure' => $players->mapWithKeys(fn ($selection) => [
                    $selection->slot_key => ['player_id' => (int) $selection->player_id, 'role' => $selection->role],
                ])->all(),
                'tactical_inputs' => $this->tacticalInputs($formation, $players),
            ];
        })->values()->all();

        return [
            'simulation_version' => self::VERSION,
            'league' => ['id' => (int) $league->id, 'name' => $league->name, 'status' => $league->status],
            'squads' => $squads,
            'power_card_resolution' => DB::table('league_card_resolutions')->where('league_id', $league->id)->get()->map(fn ($row) => [
                'power_card_id' => (int) $row->power_card_id,
                'card_type' => $row->card_type,
                'applied' => (bool) $row->applied,
                'reason' => $row->reason,
                'metadata' => json_decode($row->metadata ?: '{}', true),
            ])->all(),
            'fixtures' => $simulation->matches()->orderBy('id')->get()->map(fn ($match) => [
                'fixture_id' => $match->fixture_id,
                'home_user_id' => (int) $match->home_user_id,
                'away_user_id' => (int) $match->away_user_id,
                'leg' => (int) $match->leg,
                'boost_user_id' => $match->boost_user_id ? (int) $match->boost_user_id : null,
            ])->all(),
        ];
    }

    public function build(array $payload): string
    {
        $input = $this->json($payload);

        return <<<PROMPT
You are Amadara UNO's concise football simulation engine. Use only the supplied JSON. IDs, fixtures, teams, formations, players, coaches, slots, and resolved cards are authoritative; never invent them. Judge players at peak ability and use realistic controlled variance from tactics, squad balance, chemistry, coach influence, home advantage, fatigue, and cards.

Return valid JSON only, with no Markdown or commentary. Return every fixture once and every league user once in standings_projection. Scorer counts must equal scores and use players from the correct team. Produce exactly 10 chronological events per match, including goals when applicable and a compact mix of chances, saves, cards, substitutions, tactics, and momentum. Keep descriptions to one short sentence. Use three short phases, one or two coach decisions, concise tactical plans, chemistry, key battles, realistic statistics, ratings, impacts, decisive factors, a 1–2 sentence narrative, and a short display_narrative. Possession must total 100; shots on target must not exceed shots. The application calculates standings locally.

If the input cannot be satisfied, return {"error":{"code":"INVALID_SIMULATION_INPUT","message":"short explanation"}}.

INPUT JSON:
{$input}

REQUIRED OUTPUT SHAPE:
{
  "simulation_version":"amadara-v2",
  "league_id":0,
  "assumptions":["short assumption"],
  "player_evaluations":[{"player_id":0,"peak_role":"goalkeeper|defender|midfielder|forward|coach","peak_rating":0,"role_fit":0,"fitness_at_peak":0,"short_reason":"one sentence"}],
  "matches":[{"fixture_id":"stable-id","home_user_id":0,"away_user_id":0,"home_score":0,"away_score":0,"result":"HOME_WIN|DRAW|AWAY_WIN",
    "home_goal_scorers":[{"user_id":0,"player_id":0,"minute":0,"description":"goal description"}],"away_goal_scorers":[],
    "tactical_analysis":{"home_plan":{"approach":"","build_up":"","pressing":"","defensive_line":"","width":"","transition":"","set_piece_plan":"","coach_intent":""},"away_plan":{"approach":"","build_up":"","pressing":"","defensive_line":"","width":"","transition":"","set_piece_plan":"","coach_intent":""},"chemistry":{"home":0,"away":0,"home_links":[],"away_links":[]},"key_battles":[{"home_player_id":0,"away_player_id":0,"area":"","edge":"HOME|AWAY|EVEN","reason":""}],"phases":[{"label":"opening|middle|closing","start_minute":1,"end_minute":15,"dominant_user_id":0,"momentum":"","tactical_note":""}],"coach_decisions":[{"minute":0,"user_id":0,"decision":"","reason":""}]},
    "match_stats":{"home":{"possession":0,"shots":0,"shots_on_target":0,"expected_goals":0.0,"corners":0,"fouls":0,"offsides":0,"saves":0,"big_chances":0},"away":{}},
    "events":[{"minute":0,"type":"goal|chance|save|block|card|substitution|injury|tactical|set_piece|momentum|missed_chance|coach_instruction","team_user_id":0,"player_id":0,"related_player_id":0,"description":"match event"}],
    "home_performance_rating":0,"away_performance_rating":0,"decisive_factors":["factor"],"player_impacts":[{"player_id":0,"user_id":0,"impact":0,"reason":"tactical reason"}],"narrative":"Detailed 2-4 sentence match report.","display_narrative":"Short UI narrative."}],
  "standings_projection":[{"user_id":0,"played":0,"wins":0,"draws":0,"losses":0,"goals_for":0,"goals_against":0,"goal_difference":0}]
}
PROMPT;
    }

    private function compactPlayerData(array $playerData): array
    {
        return collect($playerData)->only([
            'name', 'known_name', 'nationality', 'position', 'team_name',
        ])->filter(fn ($value) => $value !== null && $value !== '')->all();
    }

    private function tacticalInputs(?string $formation, $players): array
    {
        $roles = $players->groupBy('role')->map->count()->all();
        $clubs = $players->map(fn ($selection) => $selection->player_data['team_name'] ?? null)->filter()->countBy()->all();
        $nationalities = $players->map(fn ($selection) => $selection->player_data['nationality'] ?? null)->filter()->countBy()->all();

        return [
            'formation_shape' => $formation,
            'role_counts' => $roles,
            'shared_club_context' => $clubs,
            'shared_nationality_context' => $nationalities,
            'positional_links' => $players->map(fn ($selection) => [
                'slot_key' => $selection->slot_key,
                'player_id' => (int) $selection->player_id,
                'role' => $selection->role,
            ])->values()->all(),
        ];
    }

    private function json(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
