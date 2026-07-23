<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Squad;
use App\Models\SquadSelection;
use App\Models\User;
use App\Services\TeamsCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SquadController extends Controller
{
    public const FORMATIONS = [
        '4-3-3' => ['defender' => 4, 'midfielder' => 3, 'forward' => 3],
        '4-4-2' => ['defender' => 4, 'midfielder' => 4, 'forward' => 2],
        '3-5-2' => ['defender' => 3, 'midfielder' => 5, 'forward' => 2],
        '3-4-3' => ['defender' => 3, 'midfielder' => 4, 'forward' => 3],
        '4-5-1' => ['defender' => 4, 'midfielder' => 5, 'forward' => 1],
        '5-3-2' => ['defender' => 5, 'midfielder' => 3, 'forward' => 2],
        '5-4-1' => ['defender' => 5, 'midfielder' => 4, 'forward' => 1],
    ];

    public function show(Request $request, League $league): View
    {
        $this->authorizeMembership($request, $league);
        $squad = $request->user()->squads()->where('league_id', $league->id)->with('selections')->first();
        $membership = $league->users()->whereKey($request->user()->id)->firstOrFail();

        return view('dashboard.squad-builder', [
            'league' => $league,
            'squad' => $squad,
            'formations' => self::FORMATIONS,
            'reservedIds' => $league->selections()->pluck('player_id')->values(),
            'ready' => (bool) $membership->pivot->ready_at,
            'editable' => true,
            'viewedUser' => $request->user(),
        ]);
    }

    public function member(Request $request, League $league, User $user): View
    {
        $this->authorizeMembership($request, $league);
        abort_unless($league->users()->whereKey($user->id)->exists(), 404);
        $squad = $user->squads()->where('league_id', $league->id)->with('selections')->firstOrFail();

        return view('dashboard.squad-builder', [
            'league' => $league,
            'squad' => $squad,
            'formations' => self::FORMATIONS,
            'reservedIds' => collect(),
            'ready' => (bool) $league->users()->whereKey($user->id)->first()->pivot->ready_at,
            'editable' => false,
            'viewedUser' => $user,
        ]);
    }

    public function search(Request $request, League $league, TeamsCatalog $catalog): JsonResponse
    {
        $this->authorizeMembership($request, $league);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'more' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $more = (bool) ($validated['more'] ?? false);
        $result = $catalog->search(trim($validated['q']), (int) ($validated['page'] ?? 1), $more);
        $reserved = $league->selections()->pluck('player_id')->map(fn ($id) => (int) $id);
        $players = collect($result['players'])->reject(fn (array $player) => $reserved->contains($player['id']));

        return response()->json([
            'results' => $players->map(fn (array $player) => $this->playerPayload($player))->values(),
            'has_more' => $result['has_more'],
            'source' => 'teams.json',
        ]);
    }

    public function store(Request $request, League $league, TeamsCatalog $catalog): JsonResponse
    {
        $this->authorizeMembership($request, $league);
        if ($league->status !== League::STATUS_YET_TO_START) {
            throw ValidationException::withMessages(['squad' => 'This league is no longer accepting squad changes.']);
        }

        $validated = $request->validate([
            'formation' => ['required', Rule::in(array_keys(self::FORMATIONS))],
            'players' => ['required', 'array', 'size:11'],
            'players.*.slot' => ['required', 'string', 'max:30'],
            'players.*.player_id' => ['required', 'integer', 'distinct'],
            'coach_player_id' => ['required', 'integer'],
        ]);

        $expectedSlots = $this->slotKeys($validated['formation']);
        $submittedSlots = collect($validated['players'])->pluck('slot');
        if ($submittedSlots->count() !== $submittedSlots->unique()->count() || $submittedSlots->sort()->values()->all() !== collect($expectedSlots)->sort()->values()->all()) {
            throw ValidationException::withMessages(['players' => 'The selected players do not match this formation.']);
        }

        $ids = collect($validated['players'])->pluck('player_id')->push($validated['coach_player_id']);
        if ($ids->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages(['players' => 'A player or coach cannot be selected twice.']);
        }
        $players = $ids->mapWithKeys(fn ($id) => [(int) $id => $catalog->find((int) $id)])->filter();
        if ($players->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages(['players' => 'One or more selected players are invalid.']);
        }

        try {
            DB::transaction(function () use ($request, $league, $validated, $players): void {
                if ($request->user()->squads()->where('league_id', $league->id)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['squad' => 'Your squad is already locked.']);
                }

                $squad = Squad::create(['league_id' => $league->id, 'user_id' => $request->user()->id, 'formation' => $validated['formation'], 'locked_at' => now()]);
                foreach ($validated['players'] as $selection) {
                    SquadSelection::create([
                        'squad_id' => $squad->id,
                        'league_id' => $league->id,
                        'player_id' => $selection['player_id'],
                        'player_data' => $this->selectionData($players[$selection['player_id']], $selection['slot']),
                        'slot_key' => $selection['slot'],
                        'role' => 'player',
                    ]);
                }
                SquadSelection::create([
                    'squad_id' => $squad->id,
                    'league_id' => $league->id,
                    'player_id' => $validated['coach_player_id'],
                    'player_data' => $this->selectionData($players[$validated['coach_player_id']], 'coach'),
                    'slot_key' => 'coach',
                    'role' => 'coach',
                ]);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') throw ValidationException::withMessages(['players' => 'One or more selected people were just taken by another user.']);
            throw $exception;
        }

        return response()->json(['message' => 'Your squad is saved and locked.', 'redirect_url' => route('squads.show', $league)]);
    }

    private function authorizeMembership(Request $request, League $league): void { abort_unless($league->users()->whereKey($request->user()->id)->exists(), 403); }

    private function slotKeys(string $formation): array
    {
        $slots = ['goalkeeper'];
        foreach (self::FORMATIONS[$formation] as $role => $count) for ($i = 1; $i <= $count; $i++) $slots[] = "{$role}_{$i}";
        return $slots;
    }

    private function selectionData(array $player, string $slot): array
    {
        unset($player['_normalized_name']);
        if ($slot === 'coach') $player['position'] = 'Coach';
        return $player;
    }

    private function playerPayload(array $player): array
    {
        unset($player['_normalized_name']);
        return $player;
    }
}
