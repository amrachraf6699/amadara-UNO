<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\LeaguePlayerReservation;
use App\Models\Squad;
use App\Models\SquadDraft;
use App\Models\SquadSelection;
use App\Models\User;
use App\Services\TeamsCatalog;
use App\Services\LeagueSeasonService;
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
        $season = app(LeagueSeasonService::class)->current($league);
        $league->load(['users', 'squads.selections']);
        $squad = $request->user()->squads()->where('league_id', $league->id)->with('selections')->first();
        $draft = $squad ? null : $request->user()->squadDrafts()->where('league_id', $league->id)->first();
        $draftSelections = $draft ? $league->playerReservations()->where('user_id', $request->user()->id)->whereNull('locked_at')->get() : collect();
        if ($squad) $this->useEffectiveSelections($league, $squad, $request->user()->id);
        if ($squad && $season->number > 1) $squad->setRelation('selections', $season->selections()->where('user_id', $request->user()->id)->get());
        $membership = $league->users()->whereKey($request->user()->id)->firstOrFail();
        $opponents = $league->users->reject(fn ($user) => $user->id === $request->user()->id)->map(function ($user) use ($league) {
            $squad = $league->squads->firstWhere('user_id', $user->id);
            return ['id' => $user->id, 'name' => $user->pivot->team_name ?: $user->name, 'squad' => $squad ? $squad->selections->where('role', 'player')->map(fn ($selection) => ['id' => $selection->player_id, 'name' => $selection->player_data['known_name'] ?? $selection->player_data['name']])->values()->all() : []];
        })->values();

        return view('dashboard.squad-builder', [
            'league' => $league,
            'squad' => $squad,
            'draft' => $draft,
            'draftSelections' => $draftSelections,
            'formations' => self::FORMATIONS,
            'reservedIds' => $league->playerReservations()->where('user_id', '!=', $request->user()->id)->pluck('player_id')->values(),
            'ready' => $season->readyEntries()->where('user_id', $request->user()->id)->exists(),
            'editable' => true,
            'viewedUser' => $request->user(),
            'submittedCards' => $league->powerCards()->where('season_id', $season->id)->where('user_id', $request->user()->id)->get()->keyBy('card_type'),
            'opponents' => $opponents,
            'season' => $season,
            'transfersUsed' => $season->transfers()->where('user_id', $request->user()->id)->count(),
        ]);
    }

    public function member(Request $request, League $league, User $user): View
    {
        $this->authorizeMembership($request, $league);
        abort_unless($league->users()->whereKey($user->id)->exists(), 404);
        $membership = $league->users()->whereKey($user->id)->firstOrFail();
        $squad = $user->squads()->where('league_id', $league->id)->with('selections')->firstOrFail();
        $this->useEffectiveSelections($league, $squad, $user->id);

        return view('dashboard.squad-builder', [
            'league' => $league,
            'squad' => $squad,
            'draft' => null,
            'formations' => self::FORMATIONS,
            'reservedIds' => collect(),
            'ready' => (bool) $membership->pivot->ready_at,
            'editable' => false,
            'viewedUser' => $user,
            'viewedTeamName' => $membership->pivot->team_name ?: $user->name,
            'viewedTeamLogo' => $membership->pivot->team_logo_path ? \Illuminate\Support\Facades\Storage::url($membership->pivot->team_logo_path) : null,
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
        $season = app(LeagueSeasonService::class)->current($league);
        $reserved = $league->playerReservations()->where('user_id', '!=', $request->user()->id)->pluck('player_id')->merge($season->selections()->where('user_id', '!=', $request->user()->id)->pluck('player_id'))->map(fn ($id) => (int) $id);
        $players = collect($result['players'])->reject(fn (array $player) => $reserved->contains($player['id']));

        return response()->json([
            'results' => $players->map(fn (array $player) => $this->playerPayload($player))->values(),
            'has_more' => $result['has_more'],
            'source' => 'teams.json',
        ]);
    }

    public function syncDraft(Request $request, League $league, TeamsCatalog $catalog): JsonResponse
    {
        $this->authorizeMembership($request, $league);
        $this->ensureEditable($request, $league);
        $validated = $request->validate([
            'formation' => ['required', Rule::in(array_keys(self::FORMATIONS))],
            'players' => ['present', 'array', 'max:11'],
            'players.*.slot' => ['required', 'string', 'max:30'],
            'players.*.player_id' => ['required', 'integer', 'distinct'],
            'coach_player_id' => ['nullable', 'integer'],
        ]);
        $selections = $this->draftSelections($validated);
        $slots = collect($selections)->pluck('slot_key');
        if ($slots->count() !== $slots->unique()->count() || $slots->diff(array_merge($this->slotKeys($validated['formation']), ['coach']))->isNotEmpty()) {
            throw ValidationException::withMessages(['players' => 'The selected players do not match this formation.']);
        }
        $ids = collect($selections)->pluck('player_id');
        if ($ids->count() !== $ids->unique()->count()) throw ValidationException::withMessages(['players' => 'A player or coach cannot be selected twice.']);
        $players = $this->catalogPlayers($ids, $catalog);
        if ($players->count() !== $ids->count()) throw ValidationException::withMessages(['players' => 'One or more selected players are invalid.']);

        $conflicts = $this->conflictingIds($league, $request->user()->id, $ids);
        if ($conflicts !== []) return response()->json(['message' => 'One or more selected people were just taken by another user.', 'conflict_player_ids' => $conflicts], 409);
        try {
            DB::transaction(function () use ($league, $request, $validated, $selections, $players): void {
                SquadDraft::updateOrCreate(['league_id' => $league->id, 'user_id' => $request->user()->id], ['formation' => $validated['formation']]);
                $desiredSlots = collect($selections)->pluck('slot_key')->all();
                $league->playerReservations()->where('user_id', $request->user()->id)->whereNull('locked_at')->whereNotIn('slot_key', $desiredSlots)->delete();
                foreach ($selections as $selection) {
                    LeaguePlayerReservation::updateOrCreate(
                        ['league_id' => $league->id, 'user_id' => $request->user()->id, 'slot_key' => $selection['slot_key']],
                        ['player_id' => $selection['player_id'], 'player_data' => $this->selectionData($players[$selection['player_id']], $selection['slot_key']), 'role' => $selection['role'], 'locked_at' => null],
                    );
                }
            });
        } catch (QueryException) {
            return response()->json(['message' => 'One or more selected people were just taken by another user.', 'conflict_player_ids' => $this->conflictingIds($league, $request->user()->id, $ids)], 409);
        }

        return response()->json(['message' => 'Draft saved.', 'reserved_ids' => $ids->values()]);
    }

    public function store(Request $request, League $league, TeamsCatalog $catalog): JsonResponse
    {
        $this->authorizeMembership($request, $league);
        $this->ensureEditable($request, $league);

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
        $players = $this->catalogPlayers($ids, $catalog);
        if ($players->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages(['players' => 'One or more selected players are invalid.']);
        }
        $conflicts = $this->conflictingIds($league, $request->user()->id, $ids);
        if ($conflicts !== []) return response()->json(['message' => 'One or more selected people were just taken by another user.', 'conflict_player_ids' => $conflicts], 422);

        try {
            DB::transaction(function () use ($request, $league, $validated, $players): void {
                if ($request->user()->squads()->where('league_id', $league->id)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['squad' => 'Your squad is already locked.']);
                }

                $allSelections = array_merge($validated['players'], [['slot' => 'coach', 'player_id' => $validated['coach_player_id']]]);
                foreach ($allSelections as $selection) {
                    LeaguePlayerReservation::updateOrCreate(
                        ['league_id' => $league->id, 'user_id' => $request->user()->id, 'slot_key' => $selection['slot']],
                        ['player_id' => $selection['player_id'], 'player_data' => $this->selectionData($players[$selection['player_id']], $selection['slot']), 'role' => $selection['slot'] === 'coach' ? 'coach' : 'player', 'locked_at' => now()],
                    );
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
                $request->user()->squadDrafts()->where('league_id', $league->id)->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') return response()->json(['message' => 'One or more selected people were just taken by another user.', 'conflict_player_ids' => $this->conflictingIds($league, $request->user()->id, $ids)], 422);
            throw $exception;
        }

        return response()->json(['message' => 'Your squad is saved and locked.', 'redirect_url' => route('squads.show', $league)]);
    }

    private function authorizeMembership(Request $request, League $league): void { abort_unless($league->users()->whereKey($request->user()->id)->exists(), 403); }

    private function ensureEditable(Request $request, League $league): void
    {
        if ($league->status !== League::STATUS_YET_TO_START) throw ValidationException::withMessages(['squad' => 'This league is no longer accepting squad changes.']);
        if ($request->user()->squads()->where('league_id', $league->id)->exists()) throw ValidationException::withMessages(['squad' => 'Your squad is already locked.']);
    }

    private function draftSelections(array $validated): array
    {
        $selections = collect($validated['players'])->map(fn (array $selection) => ['slot_key' => $selection['slot'], 'player_id' => (int) $selection['player_id'], 'role' => 'player'])->all();
        if (! empty($validated['coach_player_id'])) $selections[] = ['slot_key' => 'coach', 'player_id' => (int) $validated['coach_player_id'], 'role' => 'coach'];
        return $selections;
    }

    private function catalogPlayers($ids, TeamsCatalog $catalog)
    {
        return $ids->mapWithKeys(fn ($id) => [(int) $id => $catalog->find((int) $id)])->filter();
    }

    private function conflictingIds(League $league, int $userId, $ids): array
    {
        return $league->playerReservations()->whereIn('player_id', $ids)->where('user_id', '!=', $userId)->pluck('player_id')->map(fn ($id) => (int) $id)->all();
    }

    private function useEffectiveSelections(League $league, Squad $squad, int $userId): void
    {
        if ($league->status === League::STATUS_RUNNING) {
            $effective = $league->effectiveSelections()->where('user_id', $userId)->get();
            if ($effective->isNotEmpty()) $squad->setRelation('selections', $effective);
        }
    }

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
