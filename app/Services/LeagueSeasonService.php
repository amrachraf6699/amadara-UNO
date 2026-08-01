<?php

namespace App\Services;

use App\Models\League;
use App\Models\LeagueSeason;
use App\Models\LeagueSeasonSelection;
use Illuminate\Support\Facades\DB;

class LeagueSeasonService
{
    public function current(League $league): LeagueSeason
    {
        if ($league->current_season_id) return LeagueSeason::findOrFail($league->current_season_id);
        return DB::transaction(function () use ($league): LeagueSeason {
            $season = LeagueSeason::firstOrCreate(['league_id' => $league->id, 'number' => 1], ['status' => LeagueSeason::SETUP]);
            $league->update(['current_season_id' => $season->id]);
            return $season;
        });
    }

    public function ensureRoster(LeagueSeason $season): void
    {
        if ($season->selections()->exists()) return;
        $season->league->squads()->with('selections')->get()->each(function ($squad) use ($season): void {
            foreach ($squad->selections as $selection) $season->selections()->create(['user_id' => $squad->user_id, 'player_id' => $selection->player_id, 'player_data' => $selection->player_data, 'slot_key' => $selection->slot_key, 'role' => $selection->role]);
        });
    }

    public function openNext(LeagueSeason $season): LeagueSeason
    {
        return DB::transaction(function () use ($season): LeagueSeason {
            $season->refresh();
            $next = LeagueSeason::firstOrCreate(['league_id' => $season->league_id, 'number' => $season->number + 1], ['status' => LeagueSeason::TRANSFER_WINDOW]);
            if (! $next->selections()->exists()) {
                $source = $season->league->effectiveSelections()->where('season_id', $season->id)->get();
                if ($source->isEmpty()) $source = $season->selections()->get();
                foreach ($source as $selection) $next->selections()->create(['user_id' => $selection->user_id, 'player_id' => $selection->player_id, 'player_data' => $selection->player_data, 'slot_key' => $selection->slot_key, 'role' => $selection->role]);
            }
            $season->update(['status' => LeagueSeason::FINISHED, 'completed_at' => now()]);
            // Cards are single-use within their season. The legacy table also has
            // a league-wide unique key, so clear the completed season's cards when
            // opening the new window; resolution data remains in the simulation.
            $season->league->powerCards()->where('season_id', $season->id)->delete();
            $season->league()->update(['current_season_id' => $next->id, 'status' => League::STATUS_YET_TO_START]);
            return $next;
        });
    }
}
