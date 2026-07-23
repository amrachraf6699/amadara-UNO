<?php

namespace App\Services;

use App\Models\League;
use App\Models\LeaguePowerCard;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PowerCardResolver
{
    public function resolve(League $league): array
    {
        $league->load(['users', 'squads.selections']);
        $squads = [];
        foreach ($league->users as $user) {
            $squad = $league->squads->firstWhere('user_id', $user->id);
            if (! $squad) throw new RuntimeException('Every league member must have a locked squad.');
            $squads[$user->id] = $squad->selections->mapWithKeys(fn ($selection) => [$selection->slot_key => [
                'player_id' => (int) $selection->player_id,
                'player_data' => $selection->player_data,
                'role' => $selection->role,
                'slot_key' => $selection->slot_key,
            ]])->all();
        }

        $cards = $league->powerCards()->orderBy('id')->get();
        $guarded = $cards->where('card_type', LeaguePowerCard::GUARD)->pluck('target_player_id')->filter()->map(fn ($id) => (int) $id)->flip();

        DB::transaction(function () use ($league, $cards, &$squads, $guarded): void {
            DB::table('league_card_resolutions')->where('league_id', $league->id)->delete();
            foreach ($cards as $card) {
                $applied = true;
                $reason = 'Applied successfully.';
                $metadata = [];

                if ($card->card_type === LeaguePowerCard::STEAL) {
                    $targetUserId = (int) $card->target_user_id;
                    $targetPlayerId = (int) $card->target_player_id;
                    $replacementPlayerId = (int) $card->replacement_player_id;
                    if ($guarded->has($targetPlayerId)) {
                        $applied = false;
                        $reason = 'Blocked by Guard.';
                    } else {
                        $targetSlot = $this->findSlot($squads[$targetUserId] ?? [], $targetPlayerId);
                        $replacementSlot = $this->findSlot($squads[$card->user_id] ?? [], $replacementPlayerId);
                        if ($targetSlot === null || $replacementSlot === null) {
                            $applied = false;
                            $reason = 'Rejected because another Steal already changed one of the players.';
                        } else {
                            $temporary = $squads[$targetUserId][$targetSlot];
                            $squads[$targetUserId][$targetSlot] = $squads[$card->user_id][$replacementSlot];
                            $squads[$card->user_id][$replacementSlot] = $temporary;
                            $metadata = ['target_slot' => $targetSlot, 'replacement_slot' => $replacementSlot];
                        }
                    }
                } elseif ($card->card_type === LeaguePowerCard::GUARD) {
                    $metadata = ['protected_player_id' => (int) $card->target_player_id];
                } elseif ($card->card_type === LeaguePowerCard::BOOST) {
                    $metadata = ['target_user_id' => (int) $card->target_user_id, 'scope' => 'booster_home_fixture_only'];
                }

                $card->update([
                    'resolution_status' => $applied ? 'applied' : 'rejected',
                    'resolution_reason' => $reason,
                    'resolution_data' => $metadata,
                ]);
                DB::table('league_card_resolutions')->insert([
                    'league_id' => $league->id,
                    'power_card_id' => $card->id,
                    'card_type' => $card->card_type,
                    'applied' => $applied,
                    'reason' => $reason,
                    'metadata' => json_encode($metadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $league->effectiveSelections()->delete();
            foreach ($squads as $userId => $selections) {
                foreach ($selections as $selection) {
                    $league->effectiveSelections()->create([
                        'user_id' => $userId,
                        'player_id' => $selection['player_id'],
                        'player_data' => $selection['player_data'],
                        'slot_key' => $selection['slot_key'],
                        'role' => $selection['role'],
                    ]);
                }
            }
        });

        return $squads;
    }

    private function findSlot(array $squad, int $playerId): ?string
    {
        foreach ($squad as $slot => $selection) if ((int) $selection['player_id'] === $playerId) return $slot;
        return null;
    }
}
