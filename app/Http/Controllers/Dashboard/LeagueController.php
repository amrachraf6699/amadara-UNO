<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeagueController extends Controller
{
    public function index(Request $request): View
    {
        $leagues = $request->user()->leagues()->withCount(['users', 'readyUsers'])->with('squads')->latest('leagues.created_at')->get();

        return view('dashboard.index', [
            'leagues' => $leagues,
            'leagueIcons' => League::ICONS,
        ]);
    }

    public function show(Request $request, League $league): View
    {
        $this->authorizeMember($request, $league);
        $league->load(['users', 'readyUsers', 'squads']);

        return view('dashboard.league-table', compact('league'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_users' => ['required', 'integer', 'min:2', 'max:20'],
            'icon' => ['required', Rule::in(League::ICONS)],
        ]);

        $league = League::create([...$validated, 'owner_id' => $request->user()->id]);
        $league->users()->attach($request->user());
        $league->loadCount(['users', 'readyUsers']);
        $message = "{$league->name} was created. Your league code is {$league->code}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'league' => $this->leaguePayload($league), 'redirect_url' => route('squads.show', $league)], 201);
        }

        return redirect()->route('squads.show', $league)->with('status', $message);
    }

    public function join(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:5', 'alpha_num'],
        ]);

        $code = strtoupper($validated['code']);
        $league = League::where('code', $code)->first();

        if (! $league) {
            throw ValidationException::withMessages(['code' => 'No league was found with that code.']);
        }

        if ($league->status !== League::STATUS_YET_TO_START) {
            throw ValidationException::withMessages(['code' => 'This league is no longer accepting players.']);
        }

        if ($league->users()->whereKey($request->user()->id)->exists()) {
            throw ValidationException::withMessages(['code' => 'You are already participating in this league.']);
        }

        if ($league->users()->count() >= $league->max_users) {
            throw ValidationException::withMessages(['code' => 'This league has reached its maximum number of users.']);
        }

        $league->users()->attach($request->user());
        $league->loadCount(['users', 'readyUsers']);
        $message = "You joined {$league->name}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'league' => $this->leaguePayload($league), 'redirect_url' => route('squads.show', $league)]);
        }

        return redirect()->route('squads.show', $league)->with('status', $message);
    }

    public function ready(Request $request, League $league): RedirectResponse
    {
        $this->authorizeMember($request, $league);
        if ($league->status !== League::STATUS_YET_TO_START) throw ValidationException::withMessages(['league' => 'This league is no longer waiting for players.']);
        if (! $request->user()->squads()->where('league_id', $league->id)->exists()) throw ValidationException::withMessages(['squad' => 'Lock your squad before marking yourself ready.']);

        $league->users()->updateExistingPivot($request->user()->id, ['ready_at' => now()]);
        return redirect()->route('squads.show', $league)->with('status', 'You are ready for the league.');
    }

    public function start(Request $request, League $league): RedirectResponse
    {
        $this->authorizeMember($request, $league);
        if ((int) $league->owner_id !== (int) $request->user()->id) throw ValidationException::withMessages(['league' => 'Only the league owner can start it.']);
        if ($league->status !== League::STATUS_YET_TO_START) throw ValidationException::withMessages(['league' => 'This league has already started.']);

        $members = $league->users()->count();
        $ready = $league->readyUsers()->count();
        if ($members === 0 || $members !== $ready) throw ValidationException::withMessages(['league' => 'Every league player must be ready before the league can start.']);

        $league->update(['status' => League::STATUS_RUNNING]);
        return redirect()->route('dashboard.index')->with('status', "{$league->name} has started.");
    }

    /**
     * @return array<string, mixed>
     */
    private function leaguePayload(League $league): array
    {
        return [
            'id' => $league->id,
            'name' => $league->name,
            'code' => $league->code,
            'max_users' => $league->max_users,
            'icon' => $league->icon,
            'status' => $league->status,
            'owner_id' => $league->owner_id,
            'users_count' => $league->users_count,
            'ready_users_count' => $league->ready_users_count ?? $league->readyUsers()->count(),
        ];
    }

    private function authorizeMember(Request $request, League $league): void
    {
        abort_unless($league->users()->whereKey($request->user()->id)->exists(), 403);
    }
}
