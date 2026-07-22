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
        $leagues = $request->user()->leagues()->withCount('users')->latest('leagues.created_at')->get();

        return view('dashboard.index', [
            'leagues' => $leagues,
            'leagueIcons' => League::ICONS,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_users' => ['required', 'integer', 'min:2', 'max:20'],
            'icon' => ['required', Rule::in(League::ICONS)],
            'start_at' => ['required', 'date', 'after:'.now()->addMinutes(5)->toDateTimeString()],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $league = League::create($validated);
        $league->users()->attach($request->user());
        $league->loadCount('users');
        $message = "{$league->name} was created. Your league code is {$league->code}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'league' => $this->leaguePayload($league)], 201);
        }

        return redirect()->route('dashboard.index')->with('status', $message);
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

        if ($league->status === League::STATUS_ARCHIVED) {
            throw ValidationException::withMessages(['code' => 'This league is archived and cannot be joined.']);
        }

        if ($league->users()->whereKey($request->user()->id)->exists()) {
            throw ValidationException::withMessages(['code' => 'You are already participating in this league.']);
        }

        if ($league->users()->count() >= $league->max_users) {
            throw ValidationException::withMessages(['code' => 'This league has reached its maximum number of users.']);
        }

        $league->users()->attach($request->user());
        $league->loadCount('users');
        $message = "You joined {$league->name}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'league' => $this->leaguePayload($league)]);
        }

        return redirect()->route('dashboard.index')->with('status', $message);
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
            'start_at' => $league->start_at->toIso8601String(),
            'end_at' => $league->end_at->toIso8601String(),
            'users_count' => $league->users_count,
        ];
    }
}
