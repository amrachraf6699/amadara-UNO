@extends('layouts.app')

@section('title', $league->name . ' | Amadara UNO')
@section('description', 'League results and competitors.')

@php
  $simulationInProgress = $league->simulations()->whereIn('status', [\App\Models\LeagueSimulation::PENDING, \App\Models\LeagueSimulation::RUNNING])->exists();
  $standings = $simulation?->standings->sortByDesc(fn($standing) => [$standing->points, $standing->goal_difference, $standing->goals_for])->values() ?? collect();
  $matches = $simulation?->matches->sortBy('id')->values() ?? collect();
  $winner = $standings->first();
  $winnerMember = $winner ? $league->users->firstWhere('id', $winner->user_id) : null;
  $winnerLogo = $winnerMember?->pivot?->team_logo_path ? \Illuminate\Support\Facades\Storage::url($winnerMember->pivot->team_logo_path) : null;
  $containsArabic = fn($value) => preg_match('/\p{Arabic}/u', (string) $value) === 1;
  $teamLogo = fn($member) => $member?->pivot?->team_logo_path ? \Illuminate\Support\Facades\Storage::url($member->pivot->team_logo_path) : null;
  $playerName = function (array $scorer) use ($league): string {
    $selection = $league->effectiveSelections->first(fn($item) => (int) $item->user_id === (int) ($scorer['user_id'] ?? 0) && (int) $item->player_id === (int) ($scorer['player_id'] ?? 0));
    return $selection?->player_data['known_name'] ?? $selection?->player_data['name'] ?? 'Unknown player';
  };
  $matchScorers = function ($match, string $side): array {
    $raw = $match->raw_data ?? [];
    $key = $side === 'home' ? 'home_goal_scorers' : 'away_goal_scorers';
    if (isset($raw[$key]) && is_array($raw[$key]))
      return $raw[$key];
    $userId = $side === 'home' ? $match->home_user_id : $match->away_user_id;
    return collect($match->goal_scorers ?? [])->filter(fn($scorer) => (int) ($scorer['user_id'] ?? 0) === (int) $userId)->values()->all();
  };
@endphp

@section('content')
  <main data-dashboard-page="league-table" class="hud-results mx-auto min-h-[calc(100vh-150px)] max-w-7xl px-4 py-8 lg:px-8 lg:py-14">
    <a href="{{ route('dashboard.index') }}" class="league-back-link"><span class="league-back-icon"><i
        class="bx bx-arrow-back"></i></span> Back to leagues</a>
    <div class="mt-6 flex flex-wrap items-end justify-between gap-5">
      <div>
        <h1 class="mt-2 text-4xl font-bold tracking-[-.04em] font-extrabold uppercase text-uno-lime">{{ $league->name }}</h1>
      </div>
      <div class="league-header-actions"><div class="league-copy-actions"><button type="button" data-copy-value="{{ $league->code }}" data-copy-label="League code" class="league-icon-action" title="Copy league code"><span class="league-icon-action-glyph"><i class="bx bx-clipboard"></i></span><span class="league-icon-action-label">Code</span></button><button type="button" data-copy-value="{{ route('dashboard.index', ['join' => $league->code]) }}" data-copy-label="Invitation link" class="league-icon-action" title="Copy invitation link" aria-label="Copy invitation link"><span class="league-icon-action-glyph"><i class="bx bx-user-plus"></i></span><span class="league-icon-action-label">Invite</span></button></div><span
          class="league-status-pill">{{ $simulationInProgress ? 'Simulation in progress' : ucfirst(str_replace('_', ' ', $league->status)) }}</span>@if (!$simulation && $league->owner_id === auth()->id() && $league->status === \App\Models\League::STATUS_YET_TO_START && !$simulationInProgress && $league->readyUsers->count() === $league->users->count() && $league->users->isNotEmpty())
            <form method="POST" action="{{ route('leagues.start', $league) }}">@csrf<button type="submit"
                class="league-start-action"><i class="bx bx-play-circle"></i><span>Start league</span></button></form>@endif
      </div>
    </div>

    @if (!$simulation)
      <section class="mt-8 grid gap-3 sm:grid-cols-3" aria-label="Match lobby status">
        <div class="hud-panel p-4">
          <p class="hud-kicker">Squads ready</p>
          <div class="mt-2 flex items-end justify-between"><strong
              class="hud-number text-3xl font-black text-uno-lime">{{ $league->readyUsers->count() }}<span
                class="text-lg text-white/35"> / {{ $league->users->count() }}</span></strong><span class="lobby-ready-icon"><i
              class="bx bx-check-shield"></i></span></div>
          <div class="hud-meter mt-3"><span
              style="width: {{ $league->users->count() ? ($league->readyUsers->count() / $league->users->count()) * 100 : 0 }}%"></span>
          </div>
        </div>
      </section>
      <nav class="mt-8 flex gap-2 border-b border-white/10" aria-label="League sections"><a
          href="{{ route('leagues.show', $league) }}"
          class="rounded-t-xl border-b-2 border-uno-lime px-4 py-3 text-sm font-extrabold text-uno-lime">Table</a><a
          href="{{ route('squads.show', $league) }}"
          class="rounded-t-xl border-b-2 border-transparent px-4 py-3 text-sm font-bold text-white/50 hover:border-white/30 hover:text-white">My
          formation</a></nav>
      <section class="mt-6 overflow-hidden rounded-3xl border border-white/10 bg-white/[.03]">
        <div class="overflow-x-auto">
          <table class="results-table w-full min-w-[650px] text-left text-sm">
            <thead class="bg-white/5 text-xs uppercase tracking-widest text-white/40">
              <tr>
                <th class="px-5 py-4">Team</th>
                <th class="px-5 py-4">Status</th>
                <th class="px-5 py-4">Formation</th>
                <th class="px-5 py-4 text-right">Squad</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">@foreach ($league->users as $member)
              @php $memberSquad = $league->squads->firstWhere('user_id', $member->id);
                $memberLogo = $teamLogo($member);
              $memberName = $member->name; @endphp
              <tr class="hover:bg-white/[.03]">
                <td class="px-5 py-4 font-bold"><span class="inline-flex items-center gap-3"><span
                      class="team-avatar team-avatar-sm">@if ($memberLogo)<img src="{{ $memberLogo }}" alt=""
                      class="h-full w-full object-cover">@else<i class="bx bx-shield"></i>@endif</span><span dir="auto"
                      class="{{ $containsArabic($memberName) ? 'font-arabic' : '' }}">{{ $memberName }}</span></span></td>
                <td class="px-5 py-4">@if ($member->pivot->ready_at)<span class="text-uno-lime"><i
                class="bx bx-check-circle mr-1"></i> Ready</span>@else<span class="text-white/40"><i
                      class="bx bx-time-five mr-1"></i> Not ready</span>@endif</td>
                <td class="px-5 py-4 text-white/60">{{ $memberSquad?->formation ?: 'No formation yet' }}</td>
                <td class="px-5 py-4 text-right">@if ($memberSquad)<a
                  href="{{ route('leagues.members.squad', [$league, $member]) }}"
                  class="font-extrabold text-uno-lime hover:text-white">View squad <i
                class="bx bx-right-arrow-alt"></i></a>@else<span class="text-white/30">—</span>@endif</td>
            </tr>@endforeach</tbody>
          </table>
        </div>
      </section>
    @elseif ($simulationInProgress)
      <div class="mt-8 rounded-2xl border border-sky-300/20 bg-sky-300/10 px-5 py-4 text-sm text-sky-100"><i
          class="bx bx-loader-alt mr-2 animate-spin"></i> The league simulation is running. This page will update
        automatically when results are ready.</div>
      <script>(() => { const endpoint = @json(route('leagues.simulation.status', $league)); const resultsUrl = @json(route('leagues.show', $league)); let checking = false; const check = async () => { if (checking) return; checking = true; try { const response = await fetch(`${endpoint}?_=${Date.now()}`, { headers: { Accept: 'application/json' }, cache: 'no-store' }); const data = await response.json(); if (data.completed || data.failed) { if (window.DashboardSPA) window.DashboardSPA.navigate(resultsUrl, { replace: true }); else window.location.assign(resultsUrl); } } catch (error) { } finally { checking = false; } }; check(); const timer = window.setInterval(check, 3000); window.__dashboardSpaCleanup = () => window.clearInterval(timer); })();</script>
    @else
      <section class="mt-8">
        <div class="flex items-end justify-between gap-4">
          <div>
            <h2 class="mt-2 text-2xl font-bold">Competitors</h2>
          </div><span class="text-xs text-white/40">{{ $league->users->count() }} teams</span>
        </div>
        <div class="teams-scroller mt-5 flex w-full min-w-0 snap-x snap-mandatory flex-nowrap gap-3 overflow-x-auto px-1 py-3 pb-5 scrollbar-thin">
          @foreach ($league->users as $member)
            @php $memberSquad = $league->squads->firstWhere('user_id', $member->id);
              $teamSelections = $league->effectiveSelections->where('user_id', $member->id)->where('role', 'player');
              $logo = $teamLogo($member);
              $modalId = 'team-modal-' . $member->id;
            $memberName = $member->name; @endphp<button
              type="button" data-team-open="{{ $modalId }}"
              class="team-avatar team-avatar-lg shrink-0 snap-start border-2 border-white/20 bg-uno-blue/30 text-2xl text-uno-lime shadow-xl transition hover:-translate-y-1 hover:border-uno-lime focus:outline-none focus:ring-2 focus:ring-uno-lime">@if ($logo)<img
              src="{{ $logo }}" alt="{{ $memberName }} logo" class="h-full w-full object-cover">@else<i
                class="bx bx-shield"></i>@endif<span class="sr-only">View {{ $memberName }}</span></button>
            <div id="{{ $modalId }}" data-team-modal
              class="fixed inset-0 z-50 hidden items-center justify-center bg-[#020b15]/85 px-3 py-4 backdrop-blur-sm">
              <div class="glass-panel max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl p-4 shadow-uno sm:p-6">
                <div class="flex items-center justify-between gap-4">
                  <div class="flex min-w-0 items-center gap-3"><span
                      class="team-avatar team-avatar-md shrink-0">@if ($logo)<img src="{{ $logo }}" alt=""
                      class="h-full w-full object-cover">@else<i class="bx bx-shield"></i>@endif</span>
                    <div class="min-w-0">
                      <p class="text-xs font-extrabold uppercase tracking-widest text-uno-lime">Team ·
                        {{ $memberSquad?->formation ?? 'Formation' }}</p>
                      <h3 dir="auto"
                        class="mt-1 truncate text-2xl font-extrabold {{ $containsArabic($memberName) ? 'font-arabic' : '' }}">
                        {{ $memberName }}</h3>
                    </div>
                  </div><button type="button" data-team-close class="shrink-0 text-2xl text-white/45 hover:text-white"
                    aria-label="Close"><i class="bx bx-x"></i></button>
                </div>
                <div class="formation-board mt-5">
                  <div class="formation-lines" aria-hidden="true"></div>
                  <div class="relative flex min-h-[360px] flex-col justify-between gap-2 py-3 sm:min-h-[500px] sm:py-5">
                    @foreach (['forward' => 'Forward', 'midfielder' => 'Midfielder', 'defender' => 'Defender', 'goalkeeper' => 'Goalkeeper'] as $role => $roleLabel)
                      @php $roleSelections = $teamSelections->filter(fn($selection) => str_starts_with($selection->slot_key, $role) || ($role === 'goalkeeper' && $selection->slot_key === 'goalkeeper'))->sortBy('slot_key'); @endphp
                      @if ($roleSelections->isNotEmpty())
                        <div class="formation-row flex flex-wrap justify-center gap-2 sm:gap-3"
                          style="--slot-count: {{ $roleSelections->count() }}">@foreach ($roleSelections as $selection)
                            @php $player = $selection->player;
                            $name = $player->known_name ?: $player->name; @endphp<div
                              class="formation-slot w-24 rounded-2xl border border-white/25 bg-black/25 p-2 text-center sm:w-32 sm:p-3">
                              <div
                                class="mx-auto grid h-9 w-9 place-items-center overflow-hidden rounded-full bg-uno-blue/30 text-uno-lime sm:h-12 sm:w-12">
                                @if ($player->image_url)<img src="{{ $player->image_url }}" alt="{{ $name }}"
                                class="h-full w-full object-cover">@else<i class="bx bx-user text-lg"></i>@endif</div><strong
                                dir="auto"
                                class="mt-2 block truncate text-[10px] text-white sm:text-xs {{ $containsArabic($name) ? 'font-arabic' : '' }}">{{ $name }}</strong><small
                                class="mt-1 block text-[9px] uppercase tracking-wide text-white/40">{{ $roleLabel }}</small>
                    </div>@endforeach</div>@endif @endforeach
                  </div>
                </div>
              </div>
                </div>
          @endforeach
        </div>
      </section>
      <script>document.querySelectorAll('[data-team-open]').forEach((button) => button.addEventListener('click', () => { const modal = document.getElementById(button.dataset.teamOpen); modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.classList.add('modal-open'); })); document.querySelectorAll('[data-team-close]').forEach((button) => button.addEventListener('click', () => { const modal = button.closest('[data-team-modal]'); modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('modal-open'); })); document.querySelectorAll('[data-team-modal]').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('modal-open'); } }));</script>

      <section class="mt-12">
        <div class="flex items-end justify-between gap-4">
          <div>
            <h2 class="mt-2 text-2xl font-bold">Match centre</h2>
          </div><span id="fixtureCounter" class="text-xs font-extrabold uppercase tracking-widest text-white/40">1 /
            {{ $matches->count() }}</span>
        </div>
        <div class="fixture-shell mt-4 rounded-3xl border border-white/10 bg-white/[.03] p-3 sm:p-5">
          <div class="flex items-center justify-between gap-3"><button type="button" id="fixturePrevious"
              class="fixture-nav" aria-label="Previous fixture"><i class="bx bx-left-arrow-alt"></i><span
                class="hidden sm:inline">Previous</span></button><span
              id="fixtureLabel" class="text-[10px] font-extrabold uppercase tracking-[.2em] text-uno-lime">Fixture 1</span><button
              type="button" id="fixtureNext" class="fixture-nav" aria-label="Next fixture"><span
                class="hidden sm:inline">Next</span><i class="bx bx-right-arrow-alt"></i></button></div>
          @foreach ($matches as $index => $match)
            @php $homeMember = $league->users->firstWhere('id', $match->home_user_id);
              $awayMember = $league->users->firstWhere('id', $match->away_user_id);
              $homeName = $match->homeUser->name;
              $awayName = $match->awayUser->name;
              $homeScorers = $matchScorers($match, 'home');
              $awayScorers = $matchScorers($match, 'away');
            $events = is_array($match->raw_data['events'] ?? null) ? $match->raw_data['events'] : [];
            $goalTimeline = collect($homeScorers)->map(fn($scorer) => ['scorer' => $scorer, 'team' => $homeName, 'logo' => $teamLogo($homeMember), 'side' => 'home'])->merge(collect($awayScorers)->map(fn($scorer) => ['scorer' => $scorer, 'team' => $awayName, 'logo' => $teamLogo($awayMember), 'side' => 'away']))->sortBy(fn($goal) => (int) ($goal['scorer']['minute'] ?? 999))->values(); @endphp
            <div data-fixture-card class="{{ $index === 0 ? '' : 'hidden' }} pt-5" data-fixture-index="{{ $index }}">
              <div class="flex items-center justify-center gap-3 text-center sm:gap-8">
                <div class="min-w-0 flex-1"><span class="team-avatar team-avatar-md mx-auto">@if ($teamLogo($homeMember))<img
                src="{{ $teamLogo($homeMember) }}" alt="" class="h-full w-full object-cover">@else<i
                      class="bx bx-shield"></i>@endif</span><strong dir="auto"
                    class="mt-2 block truncate text-sm sm:text-base {{ $containsArabic($homeName) ? 'font-arabic' : '' }}">{{ $homeName }}</strong>
                </div>
                <div class="shrink-0"><span
                    class="block text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $match->home_score }} <b
                      class="text-uno-lime">—</b> {{ $match->away_score }}</span><small
                    class="mt-1 block text-[10px] font-extrabold uppercase tracking-widest text-white/40">{{ str_replace('_', ' ', $match->result) }}</small>
                </div>
                <div class="min-w-0 flex-1"><span class="team-avatar team-avatar-md mx-auto">@if ($teamLogo($awayMember))<img
                src="{{ $teamLogo($awayMember) }}" alt="" class="h-full w-full object-cover">@else<i
                      class="bx bx-shield"></i>@endif</span><strong dir="auto"
                    class="mt-2 block truncate text-sm sm:text-base {{ $containsArabic($awayName) ? 'font-arabic' : '' }}">{{ $awayName }}</strong>
                </div>
              </div>
              <details class="match-details mt-6 rounded-2xl border border-white/10 bg-black/10">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-xs font-extrabold uppercase tracking-widest text-white/60"><span><i class="bx bx-list-ul mr-1 text-uno-lime"></i> Match details</span><i class="bx bx-chevron-down text-lg text-uno-lime transition"></i></summary>
                <div class="border-t border-white/10 p-3">
              <div class="goal-timeline rounded-2xl border border-white/10 bg-black/15 p-4">
                <div class="mb-3 flex items-center justify-between gap-3"><p class="text-[10px] font-extrabold uppercase tracking-widest text-uno-lime">Goals timeline</p><span class="text-[10px] uppercase tracking-widest text-white/35">{{ $goalTimeline->count() }} goals</span></div>
                @forelse ($goalTimeline as $goal)
                  @php $goalName = $playerName($goal['scorer']); @endphp
                  <div class="goal-event {{ $goal['side'] === 'away' ? 'goal-event-away' : '' }}"><time>{{ $goal['scorer']['minute'] ?? '—' }}′</time><span class="goal-event-dot"></span><span class="team-avatar team-avatar-sm">@if ($goal['logo'])<img src="{{ $goal['logo'] }}" alt="" class="h-full w-full object-cover">@else<i class="bx bx-shield"></i>@endif</span><span class="min-w-0"><strong dir="auto" class="block truncate {{ $containsArabic($goalName) ? 'font-arabic' : '' }}">{{ $goalName }}</strong></span></div>
                @empty
                  <p class="text-sm text-white/35">No goals.</p>
                @endforelse
              </div>
              <div class="mt-3 rounded-2xl border border-white/10 bg-black/15 p-4">
                <div class="flex items-center justify-between gap-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-widest text-uno-lime">Match timeline</p><span
                    class="text-[10px] uppercase tracking-widest text-white/35">{{ count($events) }} events</span>
                </div>@if ($events)
                  <div class="mt-3 grid gap-2">@foreach ($events as $event)<div
                    class="flex items-start gap-3 border-l-2 border-uno-lime/50 pl-3 text-sm"><time
                      class="w-9 shrink-0 font-black text-uno-lime">{{ $event['minute'] ?? '—' }}′</time><span
                      class="leading-6 text-white/70">{{ $event['description'] ?? str_replace('_', ' ', $event['type'] ?? 'Match event') }}</span>
                </div>@endforeach</div>@else<p class="mt-2 text-sm leading-6 text-white/60">
                  {{ $match->narrative ?: 'A competitive match played at full intensity.' }}</p>@endif
              </div>@if ($match->decisive_factors)
                <p class="mt-3 text-xs leading-5 text-white/40"><span class="font-bold text-white/65">Decisive factors:</span>
              {{ collect($match->decisive_factors)->join(' · ') }}</p>@endif
                </div>
              </details>
          </div>@endforeach
        </div>
      </section>

      @if ($winner && $winnerMember)
        <section
          class="relative mt-12 overflow-hidden rounded-[32px] border border-uno-lime/30 bg-gradient-to-br from-uno-lime/20 via-white/[.04] to-transparent px-6 py-12 text-center"
          data-winner>
          <div class="pointer-events-none absolute inset-0 overflow-hidden" data-confetti aria-hidden="true"></div>
          <p class="relative text-xs font-extrabold uppercase tracking-[.25em] text-uno-lime">Winner</p>@if ($winnerLogo)<img
          src="{{ $winnerLogo }}" alt="" class="relative mx-auto mt-5 h-24 w-24 rounded-full object-cover shadow-2xl">@else
              <div
                class="relative mx-auto mt-5 grid h-24 w-24 place-items-center rounded-full bg-uno-blue/30 text-5xl text-uno-lime">
            <i class="bx bx-trophy"></i></div>@endif<h2 dir="auto"
            class="relative mt-5 text-3xl font-extrabold {{ $containsArabic($winnerMember->name) ? 'font-arabic' : '' }}">
            {{ $winnerMember->name }}</h2>
          <p class="relative mt-2 text-sm text-white/50">League champion · {{ $winner->points }} points</p>
      </section>@endif

      <section class="mt-12">
        <h2 class="mt-2 text-2xl font-bold">Table</h2>
        <div class="results-table-wrap mt-4">
          <table class="results-table standings-table w-full min-w-[620px] text-left text-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Team</th>
                <th>P</th>
                <th>W</th>
                <th>D</th>
                <th>L</th>
                <th>GD</th>
                <th>Pts</th>
              </tr>
            </thead>
            <tbody>@foreach ($standings as $rank => $standing)
              @php $standingMember = $league->users->firstWhere('id', $standing->user_id);
                $standingLogo = $teamLogo($standingMember);
              $standingName = $standing->user->name; @endphp
              <tr>
                <td class="font-bold text-white/40">{{ $rank + 1 }}</td>
                <td class="font-bold"><span class="inline-flex items-center gap-3"><span
                      class="team-avatar team-avatar-sm">@if ($standingLogo)<img src="{{ $standingLogo }}" alt=""
                      class="h-full w-full object-cover">@else<i class="bx bx-shield"></i>@endif</span><span dir="auto"
                      class="{{ $containsArabic($standingName) ? 'font-arabic' : '' }}">{{ $standingName }}</span></span>
                </td>
                <td data-label="Played">{{ $standing->played }}</td>
                <td data-label="Won">{{ $standing->wins }}</td>
                <td data-label="Drawn">{{ $standing->draws }}</td>
                <td data-label="Lost">{{ $standing->losses }}</td>
                <td data-label="Goal difference">{{ $standing->goal_difference }}</td>
                <td data-label="Points" class="font-extrabold text-uno-lime">{{ $standing->points }}</td>
            </tr>@endforeach</tbody>
          </table>
        </div>
      </section>
      <script>document.querySelector('[data-winner]')?.querySelector('[data-confetti]') && (() => { const box = document.querySelector('[data-winner] [data-confetti]'); for (let i = 0; i < 70; i++) { const piece = document.createElement('i'); piece.style.left = `${Math.random() * 100}%`; piece.style.background = ['#d8ff5f', '#fff', '#55b8ff', '#ffcf5c'][i % 4]; piece.style.animationDelay = `${Math.random() * 1.5}s`; piece.style.transform = `rotate(${Math.random() * 360}deg)`; box.appendChild(piece); } })();</script>
    @endif
    @if ($simulation)
      <section class="mt-12">
        <h2 class="mt-2 text-2xl font-bold">Top scorers</h2>
        <div class="results-table-wrap mt-4">
          <table class="results-table scorers-table w-full min-w-[560px] text-left text-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Player</th>
                <th>Team</th>
                <th class="text-right">Goals</th>
              </tr>
            </thead>
            <tbody>@foreach ($scorerTotals as $rank => $scorer) @php $scorerMember = $league->users->firstWhere('id', $scorer['user_id']); $scorerLogo = $teamLogo($scorerMember); @endphp<tr>
              <td data-label="Rank" class="font-bold text-white/40">{{ $rank + 1 }}</td>
              <td data-label="Player" class="font-bold">
                <span dir="auto" class="{{ $containsArabic($scorer['team']) ? 'font-arabic' : '' }}">
                  {{ $scorer['name'] }}
                </span>
              </td>
              <td data-label="Team" class="text-white/60">
                <span class="inline-flex items-center gap-3">
                  <span class="team-avatar team-avatar-sm shrink-0">
                    @if ($scorerLogo)
                      <img src="{{ $scorerLogo }}" alt="" class="h-full w-full object-cover">
                    @else
                      <i class="bx bx-shield"></i>
                    @endif
                  </span>
                  <span dir="auto" class="{{ $containsArabic($scorer['name']) ? 'font-arabic' : '' }}">
                    {{ $scorer['team'] }}
                  </span>
                </span>
              </td>
              <td data-label="Goals" class="text-right text-lg font-extrabold text-uno-lime">{{ $scorer['goals'] }}</td>
            </tr>@endforeach</tbody>
          </table>
        </div>
      </section>
    @endif
  </main>
  <script>
    (() => {
      const fallbackCopy = (value) => { const input = document.createElement('textarea'); input.value = value; input.style.position = 'fixed'; input.style.opacity = '0'; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove(); };
      document.querySelectorAll('[data-copy-value]').forEach((button) => button.addEventListener('click', async () => {
        const value = button.dataset.copyValue || '';
        try { if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(value); else fallbackCopy(value); window.showToast?.(`${button.dataset.copyLabel || 'Value'} copied.`); }
        catch (error) { try { fallbackCopy(value); window.showToast?.(`${button.dataset.copyLabel || 'Value'} copied.`); } catch (fallbackError) { window.showToast?.('Unable to copy. Please copy it manually.', 'error'); } }
      }));
    })();
  </script>
  <style>
    [data-confetti] i {
      position: absolute;
      top: -10%;
      width: 8px;
      height: 14px;
      animation: fall 3.5s linear infinite;
      opacity: .9
    }

    @keyframes fall {
      to {
        top: 110%;
        transform: translateY(100vh) rotate(720deg)
      }
    }
  </style>
  <script>
    (() => {
      const cards = [...document.querySelectorAll('[data-fixture-card]')];
      if (!cards.length) return;
      let current = 0;
      const counter = document.getElementById('fixtureCounter');
      const label = document.getElementById('fixtureLabel');
      const previous = document.getElementById('fixturePrevious');
      const next = document.getElementById('fixtureNext');
      const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const animate = window.gsap && !reduceMotion;
      const setMeta = () => { if (counter) counter.textContent = `${current + 1} / ${cards.length}`; if (label) label.textContent = `Fixture ${current + 1}`; if (previous) previous.disabled = cards.length < 2; if (next) next.disabled = cards.length < 2; };
      const revealCard = (card, direction = 1) => {
        card.classList.remove('hidden');
        card.style.display = '';
        if (!animate) return;
        const pieces = card.querySelectorAll('.team-avatar, strong, .fixture-score, .match-details, .goal-timeline, .match-timeline');
        gsap.fromTo(card, { x: direction * 36, y: 10 }, { x: 0, y: 0, duration: .55, ease: 'power3.out', clearProps: 'transform' });
        gsap.fromTo(pieces, { y: 14 }, { y: 0, duration: .42, stagger: .055, delay: .12, ease: 'back.out(1.4)', clearProps: 'transform' });
      };
      const show = (index, direction = 1) => {
        const nextIndex = (index + cards.length) % cards.length;
        if (nextIndex === current && cards[current].style.display !== 'none') return;
        cards.forEach((card, i) => { if (i !== nextIndex) { card.classList.add('hidden'); card.style.display = 'none'; } });
        current = nextIndex;
        revealCard(cards[current], direction);
        setMeta();
        if (animate) gsap.fromTo([previous, next, counter, label], { y: -5, autoAlpha: .45 }, { y: 0, autoAlpha: 1, duration: .3, stagger: .035, ease: 'power2.out' });
      };
      cards.forEach((card, i) => { card.style.display = i === 0 ? '' : 'none'; card.classList.toggle('hidden', i !== 0); });
      setMeta();
      if (animate) {
        gsap.fromTo('.hud-results > *', { y: 16 }, { y: 0, duration: .65, stagger: .08, ease: 'power3.out', clearProps: 'transform' });
        gsap.fromTo('.fixture-shell', { y: 24, scale: .985 }, { y: 0, scale: 1, duration: .8, delay: .2, ease: 'expo.out', clearProps: 'transform' });
        gsap.fromTo('.team-avatar', { scale: .82, rotation: -18 }, { scale: 1, rotation: 0, duration: .7, delay: .35, stagger: .08, ease: 'back.out(1.8)', clearProps: 'transform' });
        gsap.to('.fixture-nav', { y: -3, repeat: -1, yoyo: true, duration: 1.3, stagger: .12, ease: 'sine.inOut' });
        gsap.fromTo('.hud-league-table tbody tr, [data-winner], .hud-results section:last-of-type tbody tr', { y: 18 }, { y: 0, duration: .5, stagger: .06, delay: .45, ease: 'power2.out', clearProps: 'transform' });
      }
      revealCard(cards[0], 1);
      previous?.addEventListener('click', () => show(current - 1, -1));
      next?.addEventListener('click', () => show(current + 1, 1));
      document.addEventListener('keydown', (event) => { if (event.target.matches('input, textarea, select')) return; if (event.key === 'ArrowLeft') show(current - 1, -1); if (event.key === 'ArrowRight') show(current + 1, 1); });
      document.querySelectorAll('.match-details').forEach((details) => details.addEventListener('toggle', () => {
        if (!animate) return;
        const content = details.querySelector(':scope > div');
        if (details.open && content) gsap.fromTo(content, { autoAlpha: 0, y: -12, height: 0 }, { autoAlpha: 1, y: 0, height: 'auto', duration: .45, ease: 'power3.out', clearProps: 'height' });
        if (details.open) gsap.fromTo(details.querySelectorAll('.goal-timeline, .match-timeline'), { autoAlpha: 0, x: -12 }, { autoAlpha: 1, x: 0, duration: .35, stagger: .08, delay: .12, ease: 'power2.out' });
      }));
      document.querySelectorAll('.fixture-nav, [data-team-open], [data-team-close]').forEach((button) => {
        if (!animate) return;
        button.addEventListener('mouseenter', () => gsap.to(button, { scale: 1.08, duration: .2, ease: 'power2.out' }));
        button.addEventListener('mouseleave', () => gsap.to(button, { scale: 1, duration: .25, ease: 'power2.out' }));
      });
    })();
  </script>
@endsection
