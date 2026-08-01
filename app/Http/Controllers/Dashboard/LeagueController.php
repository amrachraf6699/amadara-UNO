<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\RunLeagueSimulation;
use App\Models\League;
use App\Models\LeagueSimulation;
use App\Services\LeagueSimulationService;
use App\Services\LeagueSeasonService;
use App\Services\TeamsCatalog;
use App\Models\LeagueSeason;
use App\Models\LeagueSeasonSelection;
use App\Models\LeagueSeasonTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

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
        $currentSeason = app(LeagueSeasonService::class)->current($league);
        $season = $league->seasons()->whereKey($request->integer('season', $currentSeason->id))->firstOrFail();
        $league->load(['users', 'readyUsers', 'squads', 'effectiveSelections']);
        $seasonSelections = $league->effectiveSelections->where('season_id', $season->id);
        // Older seasons created before seasonal effective selections were retained
        // may no longer have a resolved snapshot. Their locked seasonal roster is
        // still authoritative for displaying formations and player names.
        if ($seasonSelections->isEmpty()) $seasonSelections = $season->selections()->get();
        $league->setRelation('effectiveSelections', $seasonSelections);
        $simulation = $league->simulations()->where('season_id', $season->id)->where('status', LeagueSimulation::COMPLETED)->with(['standings.user', 'matches.homeUser', 'matches.awayUser'])->latest()->first();
        $league->users->each(fn ($member) => $member->setAttribute('name', $member->pivot->team_name ?: $member->name));
        $simulation?->standings->each(function ($standing) use ($league): void {
            $member = $league->users->firstWhere('id', $standing->user_id);
            if ($member) $standing->user->setAttribute('name', $member->name);
        });
        $simulation?->matches->each(function ($match) use ($league): void {
            $scorerLabels = [];
            foreach (['homeUser', 'awayUser'] as $relation) {
                $member = $league->users->firstWhere('id', $match->{$relation}->id);
                if ($member) $match->{$relation}->setAttribute('name', $member->name);
            }
            foreach ($match->goal_scorers ?? [] as $scorer) {
                $selection = $league->effectiveSelections->first(fn ($item) => (int) $item->user_id === (int) $scorer['user_id'] && (int) $item->player_id === (int) $scorer['player_id']);
                $name = $selection?->player_data['known_name'] ?? $selection?->player_data['name'] ?? 'Unknown player';
                $scorerLabels[] = $name.' ('.$scorer['minute']."')";
            }
            if ($scorerLabels) $match->setAttribute('narrative', 'Goal scorers: '.implode(', ', $scorerLabels).($match->narrative ? ' — '.$match->narrative : ''));
        });
        $scorerTotals = collect();
        $simulation?->matches->each(function ($match) use (&$scorerTotals, $league): void {
            foreach ($match->goal_scorers ?? [] as $scorer) {
                $selection = $league->effectiveSelections->first(fn ($item) => (int) $item->user_id === (int) $scorer['user_id'] && (int) $item->player_id === (int) $scorer['player_id']);
                $key = $scorer['user_id'].':'.$scorer['player_id'];
                $row = $scorerTotals->get($key, ['user_id' => (int) $scorer['user_id'], 'player_id' => (int) $scorer['player_id'], 'name' => $selection?->player_data['known_name'] ?? $selection?->player_data['name'] ?? 'Unknown player', 'team' => $league->users->firstWhere('id', $scorer['user_id'])?->name ?? 'Team', 'goals' => 0]);
                $row['goals']++;
                $scorerTotals->put($key, $row);
            }
        });
        $scorerTotals = $scorerTotals->sortByDesc('goals')->values();

        return view('dashboard.league-table', compact('league', 'simulation', 'scorerTotals', 'season', 'currentSeason'));
    }

    public function simulationStatus(Request $request, League $league): JsonResponse
    {
        $this->authorizeMember($request, $league);
        $simulation = $league->simulations()->latest()->first();

        return response()->json([
            'status' => $simulation?->status,
            'league_status' => $league->status,
            'completed' => $simulation?->status === LeagueSimulation::COMPLETED,
            'failed' => $simulation?->status === LeagueSimulation::FAILED,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_users' => ['required', 'integer', 'min:2', 'max:20'],
            'icon' => ['required', Rule::in(League::ICONS)],
            'team_name' => ['nullable', 'string', 'max:80'],
            'team_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $team = $this->teamIdentity($request);
        $league = League::create(collect($validated)->except(['team_name', 'team_logo'])->all() + ['owner_id' => $request->user()->id]);
        $league->users()->attach($request->user(), $team);
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
            'team_name' => ['nullable', 'string', 'max:80'],
            'team_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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

        $league->users()->attach($request->user(), $this->teamIdentity($request));
        $league->loadCount(['users', 'readyUsers']);
        $message = "You joined {$league->name}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'league' => $this->leaguePayload($league), 'redirect_url' => route('squads.show', $league)]);
        }

        return redirect()->route('squads.show', $league)->with('status', $message);
    }

    public function ready(Request $request, League $league): RedirectResponse|JsonResponse
    {
        $this->authorizeMember($request, $league);
        $season = app(LeagueSeasonService::class)->current($league);
        if ($league->status !== League::STATUS_YET_TO_START) throw ValidationException::withMessages(['league' => 'This league is no longer waiting for players.']);
        app(LeagueSeasonService::class)->ensureRoster($season);
        if (! $season->selections()->where('user_id', $request->user()->id)->exists()) throw ValidationException::withMessages(['squad' => 'Lock your squad before marking yourself ready.']);

        $season->readyEntries()->updateOrCreate(['user_id' => $request->user()->id], ['ready_at' => now()]);
        $league->users()->updateExistingPivot($request->user()->id, ['ready_at' => now()]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'You are ready for the league.', 'redirect_url' => route('squads.show', $league)]);
        }
        return redirect()->route('squads.show', $league)->with('status', 'You are ready for the league.');
    }

    public function start(Request $request, League $league, LeagueSimulationService $simulationService): RedirectResponse|JsonResponse
    {
        $this->authorizeMember($request, $league);
        if ((int) $league->owner_id !== (int) $request->user()->id) throw ValidationException::withMessages(['league' => 'Only the league owner can start it.']);
        if ($league->status !== League::STATUS_YET_TO_START) throw ValidationException::withMessages(['league' => 'This league has already started.']);

        $season = app(LeagueSeasonService::class)->current($league);
        $members = $league->users()->count();
        $ready = $season->readyEntries()->count();
        // Keep Season 1 compatible with leagues/readiness created before the
        // season tables existed. Later seasons use only their own readiness.
        if ($season->number === 1) $ready = max($ready, $league->readyUsers()->count());
        if ($members === 0 || $members !== $ready) throw ValidationException::withMessages(['league' => 'Every league player must be ready before the league can start.']);

        if ($season->simulations()->whereIn('status', [LeagueSimulation::PENDING, LeagueSimulation::RUNNING])->exists()) {
            throw ValidationException::withMessages(['league' => 'This league simulation is already being prepared.']);
        }

        $simulation = $simulationService->prepare($league);
        $season->update(['status' => LeagueSeason::RUNNING, 'started_at' => now()]);
        RunLeagueSimulation::dispatch($simulation->id);
        if ($request->expectsJson()) {
            return response()->json(['message' => "{$league->name} is being simulated.", 'redirect_url' => route('leagues.show', $league)]);
        }
        return redirect()->route('leagues.show', $league)->with('status', "{$league->name} is being simulated.");
    }

    public function transfer(Request $request, League $league, TeamsCatalog $catalog): JsonResponse
    {
        $this->authorizeMember($request, $league);
        $season = app(LeagueSeasonService::class)->current($league);
        if ($season->status !== LeagueSeason::TRANSFER_WINDOW) throw ValidationException::withMessages(['transfer' => 'Transfers are available only between seasons.']);
        if ($season->readyEntries()->where('user_id', $request->user()->id)->exists()) throw ValidationException::withMessages(['transfer' => 'You are already ready for this season.']);
        if ($season->transfers()->where('user_id', $request->user()->id)->count() >= 3) throw ValidationException::withMessages(['transfer' => 'You have used all three transfers.']);
        $data = $request->validate(['outgoing_player_id' => ['required', 'integer'], 'incoming_player_id' => ['required', 'integer', 'different:outgoing_player_id']]);
        $incoming = $catalog->find((int) $data['incoming_player_id']);
        if (! $incoming) throw ValidationException::withMessages(['incoming_player_id' => 'The selected player is invalid.']);
        try {
            DB::transaction(function () use ($season, $request, $data, $incoming): void {
                $outgoing = $season->selections()->where('user_id', $request->user()->id)->where('player_id', $data['outgoing_player_id'])->lockForUpdate()->first();
                if (! $outgoing) throw ValidationException::withMessages(['outgoing_player_id' => 'Choose a player from your current roster.']);
                if ($season->selections()->where('player_id', $data['incoming_player_id'])->exists()) throw ValidationException::withMessages(['incoming_player_id' => 'That player is already selected by another team.']);
                $outgoing->update(['player_id' => $data['incoming_player_id'], 'player_data' => $incoming]);
                $season->transfers()->create(['user_id' => $request->user()->id, 'outgoing_player_id' => $data['outgoing_player_id'], 'incoming_player_id' => $data['incoming_player_id'], 'slot_key' => $outgoing->slot_key]);
            });
        } catch (\Illuminate\Database\QueryException) { return response()->json(['message' => 'That player was just selected by another team.', 'conflict_player_ids' => [(int) $data['incoming_player_id']]], 409); }
        return response()->json(['message' => 'Transfer completed.', 'transfers_used' => $season->transfers()->where('user_id', $request->user()->id)->count()]);
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

    /** @return array{team_name: string, team_logo_path: ?string} */
    private function teamIdentity(Request $request): array
    {
        $name = trim((string) $request->input('team_name', ''));

        return [
            'team_name' => $name !== '' ? $name : Str::limit($request->user()->name, 80, ''),
            'team_logo_path' => $request->file('team_logo')?->store('team-logos', 'public'),
        ];
    }
}
