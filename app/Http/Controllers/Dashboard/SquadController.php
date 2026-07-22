<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\FootballPlayer;
use App\Models\League;
use App\Models\Squad;
use App\Models\SquadSelection;
use App\Services\FootballdataClient;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

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
        $squad = $request->user()->squads()->where('league_id', $league->id)->with('selections.footballPlayer')->first();

        return view('dashboard.squad-builder', [
            'league' => $league,
            'squad' => $squad,
            'formations' => self::FORMATIONS,
            'reservedIds' => $league->selections()->pluck('football_player_id')->values(),
        ]);
    }

    public function search(Request $request, League $league, FootballdataClient $client): JsonResponse
    {
        $this->authorizeMembership($request, $league);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'more' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = trim($validated['q']);
        $normalized = Str::lower($query);
        $more = (bool) ($validated['more'] ?? false);
        $players = collect();
        $source = 'database';

        if (! $more) {
            $players = FootballPlayer::query()
                ->where('normalized_name', 'like', "%{$normalized}%")
                ->limit(3)
                ->get();
        }

        if ($more || $players->count() < 3) {
            try {
                $providerData = $client->searchPlayers($query, (int) ($validated['page'] ?? 1), 10);
                $providerPlayers = $this->providerPlayers($providerData);
                foreach ($providerPlayers as $data) {
                    $player = $this->upsertPlayer($data);
                    if ($player) {
                        $players->push($player);
                    }
                }
                $source = $more ? 'provider' : 'database_and_provider';
            } catch (RuntimeException $exception) {
                if ($players->isEmpty()) {
                    return response()->json(['message' => 'Player search is temporarily unavailable.'], 503);
                }
            }
        }

        $reserved = $league->selections()->pluck('football_player_id');
        $players = $players->unique('id')->reject(fn (FootballPlayer $player) => $reserved->contains($player->id))->values();

        return response()->json([
            'results' => $players->map(fn (FootballPlayer $player) => $this->playerPayload($player))->values(),
            'has_more' => $more || $players->count() >= 3,
            'source' => $source,
        ]);
    }

    public function store(Request $request, League $league, FootballdataClient $client): JsonResponse
    {
        $this->authorizeMembership($request, $league);
        if ($league->status === League::STATUS_ARCHIVED) {
            throw ValidationException::withMessages(['squad' => 'This league is archived.']);
        }

        $validated = $request->validate([
            'formation' => ['required', Rule::in(array_keys(self::FORMATIONS))],
            'players' => ['required', 'array', 'size:11'],
            'players.*.slot' => ['required', 'string', 'max:30'],
            'players.*.provider_id' => ['required', 'integer', 'distinct'],
            'coach_provider_id' => ['required', 'integer'],
        ]);

        $expectedSlots = $this->slotKeys($validated['formation']);
        $submittedSlots = collect($validated['players'])->pluck('slot');
        if ($submittedSlots->count() !== $submittedSlots->unique()->count() || $submittedSlots->sort()->values()->all() !== collect($expectedSlots)->sort()->values()->all()) {
            throw ValidationException::withMessages(['players' => 'The selected players do not match this formation.']);
        }

        $providerIds = collect($validated['players'])->pluck('provider_id')->push($validated['coach_provider_id']);
        if ($providerIds->count() !== $providerIds->unique()->count()) {
            throw ValidationException::withMessages(['players' => 'A player or coach cannot be selected twice.']);
        }

        try {
            DB::transaction(function () use ($request, $league, $validated, $providerIds): void {
                if ($request->user()->squads()->where('league_id', $league->id)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['squad' => 'Your squad is already locked.']);
                }

                $players = FootballPlayer::whereIn('provider_id', $providerIds)->get()->keyBy('provider_id');
                foreach ($providerIds->unique()->diff($players->keys()) as $providerId) {
                    try {
                        $player = $this->upsertPlayer($client->getPlayer((int) $providerId));
                        if ($player) $players->put((int) $providerId, $player);
                    } catch (RuntimeException $exception) {
                        throw ValidationException::withMessages(['players' => 'One or more selected people could not be verified.']);
                    }
                }
                if ($players->count() !== $providerIds->unique()->count()) {
                    throw ValidationException::withMessages(['players' => 'One or more selected players are invalid.']);
                }

                $squad = Squad::create([
                    'league_id' => $league->id,
                    'user_id' => $request->user()->id,
                    'formation' => $validated['formation'],
                    'locked_at' => now(),
                ]);

                foreach ($validated['players'] as $selection) {
                    SquadSelection::create([
                        'squad_id' => $squad->id,
                        'league_id' => $league->id,
                        'football_player_id' => $players[$selection['provider_id']]->id,
                        'slot_key' => $selection['slot'],
                        'role' => 'player',
                    ]);
                }
                SquadSelection::create([
                    'squad_id' => $squad->id,
                    'league_id' => $league->id,
                    'football_player_id' => $players[$validated['coach_provider_id']]->id,
                    'slot_key' => 'coach',
                    'role' => 'coach',
                ]);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages(['players' => 'One or more selected people were just taken by another user.']);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Your squad is saved and locked.', 'redirect_url' => route('squads.show', $league)]);
    }

    private function authorizeMembership(Request $request, League $league): void
    {
        abort_unless($league->users()->whereKey($request->user()->id)->exists(), 403);
    }

    private function slotKeys(string $formation): array
    {
        $slots = ['goalkeeper'];
        foreach (self::FORMATIONS[$formation] as $role => $count) {
            for ($i = 1; $i <= $count; $i++) $slots[] = "{$role}_{$i}";
        }
        return $slots;
    }

    private function providerPlayers(array $data): array
    {
        $players = $data['data']['players']
            ?? $data['data']['results']
            ?? $data['data']['items']
            ?? $data['players']
            ?? $data['results']
            ?? $data['items']
            ?? $data['data']
            ?? $data;
        return is_array($players) && array_is_list($players) ? $players : [];
    }

    private function upsertPlayer(array $data): ?FootballPlayer
    {
        $providerId = $data['player_id'] ?? $data['id'] ?? null;
        $name = $data['player_name'] ?? $data['name'] ?? null;
        if (! is_numeric($providerId) || ! $name) return null;
        $team = $data['team'] ?? [];
        return FootballPlayer::updateOrCreate(['provider_id' => (int) $providerId], [
            'name' => $name,
            'normalized_name' => Str::lower($name),
            'position' => $data['position'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'age' => isset($data['age']) && is_numeric($data['age']) ? $data['age'] : null,
            'team_provider_id' => $team['team_id'] ?? null,
            'team_name' => $team['team_name'] ?? $team['name'] ?? null,
            'image_url' => $data['player_image'] ?? $data['profile_image'] ?? $data['image'] ?? null,
            'profile_url' => $data['profile_url'] ?? null,
            'raw_data' => $data,
        ]);
    }

    private function playerPayload(FootballPlayer $player): array
    {
        return ['id' => $player->provider_id, 'name' => $player->name, 'position' => $player->position, 'team_name' => $player->team_name, 'image_url' => $player->image_url];
    }
}
