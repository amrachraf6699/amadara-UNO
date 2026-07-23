<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\LeaguePowerCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PowerCardController extends Controller
{
    public function index(Request $request, League $league): JsonResponse
    {
        $this->authorizeMember($request, $league);
        return response()->json(['cards' => $league->powerCards()->where('user_id', $request->user()->id)->get()]);
    }

    public function store(Request $request, League $league): JsonResponse
    {
        $this->authorizeMember($request, $league);
        if ($league->status !== League::STATUS_YET_TO_START) throw ValidationException::withMessages(['card' => 'Power cards are closed for this league.']);
        if (! $request->user()->squads()->where('league_id', $league->id)->exists()) throw ValidationException::withMessages(['card' => 'Lock your squad before using a power card.']);
        if ($league->users()->whereKey($request->user()->id)->first()?->pivot?->ready_at) throw ValidationException::withMessages(['card' => 'Power cards are locked after you are ready.']);

        $validated = $request->validate([
            'card_type' => ['required', Rule::in(LeaguePowerCard::TYPES)],
            'target_user_id' => ['nullable', 'integer'],
            'target_player_id' => ['nullable', 'integer'],
            'replacement_player_id' => ['nullable', 'integer'],
        ]);
        if ($league->powerCards()->where('user_id', $request->user()->id)->where('card_type', $validated['card_type'])->exists()) {
            throw ValidationException::withMessages(['card' => 'This power card has already been submitted.']);
        }

        $this->validateTarget($request, $league, $validated);
        $card = $league->powerCards()->create([...$validated, 'user_id' => $request->user()->id]);
        return response()->json(['message' => ucfirst($card->card_type).' card submitted.', 'card' => $card], 201);
    }

    private function validateTarget(Request $request, League $league, array $data): void
    {
        $ownSelections = $request->user()->squads()->where('league_id', $league->id)->firstOrFail()->selections()->pluck('player_id');
        if ($data['card_type'] === LeaguePowerCard::GUARD) {
            if (! $data['target_player_id'] || ! $ownSelections->contains((int) $data['target_player_id'])) throw ValidationException::withMessages(['target_player_id' => 'Guard must target one of your own players.']);
            return;
        }
        if (! $data['target_user_id'] || (int) $data['target_user_id'] === (int) $request->user()->id || ! $league->users()->whereKey($data['target_user_id'])->exists()) throw ValidationException::withMessages(['target_user_id' => 'Choose another league player.']);
        if (! $league->squads()->where('user_id', $data['target_user_id'])->exists()) throw ValidationException::withMessages(['target_user_id' => 'That opponent has not locked a squad yet.']);
        if ($data['card_type'] === LeaguePowerCard::BOOST) return;
        if (! $data['target_player_id'] || ! $data['replacement_player_id']) throw ValidationException::withMessages(['target_player_id' => 'Steal requires a target and replacement player.']);
        $targetSelections = $league->squads()->where('user_id', $data['target_user_id'])->first()?->selections()->pluck('player_id') ?? collect();
        if (! $targetSelections->contains((int) $data['target_player_id'])) throw ValidationException::withMessages(['target_player_id' => 'The target player is not in that formation.']);
        if (! $ownSelections->contains((int) $data['replacement_player_id'])) throw ValidationException::withMessages(['replacement_player_id' => 'The replacement player must be from your formation.']);
    }

    private function authorizeMember(Request $request, League $league): void
    {
        abort_unless($league->users()->whereKey($request->user()->id)->exists(), 403);
    }
}
