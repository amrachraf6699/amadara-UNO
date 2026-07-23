@extends('layouts.app')

@section('title', $league->name.' | Amadara UNO')
@section('description', 'League table and squad overview.')

@section('content')
<main class="mx-auto min-h-[calc(100vh-150px)] max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
  <a href="{{ route('dashboard.index') }}" class="text-sm font-bold text-white/50 hover:text-uno-lime"><i class="bx bx-arrow-back mr-1"></i> Back to leagues</a>
  <div class="mt-6 flex flex-wrap items-end justify-between gap-5">
    <div><p class="text-xs font-extrabold uppercase tracking-[.22em] text-uno-lime">{{ $league->name }}</p><h1 class="mt-2 text-4xl font-bold tracking-[-.04em]">League table.</h1><p class="mt-3 text-sm text-white/50">See who is ready and view every participant’s locked squad.</p></div>
    @php $simulationInProgress = $league->simulations()->whereIn('status', [\App\Models\LeagueSimulation::PENDING, \App\Models\LeagueSimulation::RUNNING])->exists(); @endphp
    <div class="flex flex-wrap items-center gap-3"><span class="rounded-full bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-wider text-white/70">{{ $simulationInProgress ? 'Simulation in progress' : ucfirst(str_replace('_', ' ', $league->status)) }}</span>@if ($league->owner_id === auth()->id() && $league->status === \App\Models\League::STATUS_YET_TO_START && ! $simulationInProgress && $league->readyUsers->count() === $league->users->count() && $league->users->isNotEmpty())<form method="POST" action="{{ route('leagues.start', $league) }}">@csrf<button type="submit" class="rounded-xl bg-uno-lime px-4 py-2 text-xs font-extrabold text-uno-navy hover:bg-white">Start league</button></form>@endif</div>
  </div>

  <nav class="mt-8 flex gap-2 border-b border-white/10" aria-label="League sections">
    <a href="{{ route('leagues.show', $league) }}" class="rounded-t-xl border-b-2 border-uno-lime px-4 py-3 text-sm font-extrabold text-uno-lime">Table</a>
    <a href="{{ route('squads.show', $league) }}" class="rounded-t-xl border-b-2 border-transparent px-4 py-3 text-sm font-bold text-white/50 hover:border-white/30 hover:text-white">My formation</a>
  </nav>

  <section class="mt-6 overflow-hidden rounded-3xl border border-white/10 bg-white/[.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[650px] text-left text-sm">
        <thead class="bg-white/5 text-xs uppercase tracking-widest text-white/40"><tr><th class="px-5 py-4">Player</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Formation</th><th class="px-5 py-4 text-right">Squad</th></tr></thead>
        <tbody class="divide-y divide-white/10">
          @foreach ($league->users as $member)
            @php $memberSquad = $league->squads->firstWhere('user_id', $member->id); @endphp
            <tr class="hover:bg-white/[.03]"><td class="px-5 py-4 font-bold">{{ $member->name }} @if ($member->id === $league->owner_id)<span class="ml-2 rounded-full bg-uno-lime/15 px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-uno-lime">Owner</span>@endif</td><td class="px-5 py-4">@if ($member->pivot->ready_at)<span class="text-uno-lime"><i class="bx bx-check-circle mr-1"></i> Ready</span>@else<span class="text-white/40"><i class="bx bx-time-five mr-1"></i> Not ready</span>@endif</td><td class="px-5 py-4 text-white/60">{{ $memberSquad?->formation ?: 'No formation yet' }}</td><td class="px-5 py-4 text-right">@if ($memberSquad)<a href="{{ route('leagues.members.squad', [$league, $member]) }}" class="font-extrabold text-uno-lime hover:text-white">View squad <i class="bx bx-right-arrow-alt"></i></a>@else<span class="text-white/30">—</span>@endif</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>

  @if ($simulation)
    <section class="mt-8"><div class="flex items-end justify-between gap-4"><div><p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Simulation complete</p><h2 class="mt-2 text-2xl font-bold">Final standings.</h2></div><span class="text-xs text-white/40">{{ $simulation->matches->count() }} fixtures</span></div><div class="mt-4 overflow-x-auto rounded-3xl border border-white/10 bg-white/[.03]"><table class="w-full min-w-[650px] text-left text-sm"><thead class="bg-white/5 text-xs uppercase tracking-widest text-white/40"><tr><th class="px-5 py-4">#</th><th class="px-5 py-4">Player</th><th class="px-5 py-4">P</th><th class="px-5 py-4">W</th><th class="px-5 py-4">D</th><th class="px-5 py-4">L</th><th class="px-5 py-4">GD</th><th class="px-5 py-4">Pts</th></tr></thead><tbody class="divide-y divide-white/10">@foreach ($simulation->standings->sortByDesc(fn ($standing) => [$standing->points, $standing->goal_difference, $standing->goals_for])->values() as $rank => $standing)<tr><td class="px-5 py-4 font-bold text-white/40">{{ $rank + 1 }}</td><td class="px-5 py-4 font-bold">{{ $standing->user->name }}</td><td class="px-5 py-4">{{ $standing->played }}</td><td class="px-5 py-4">{{ $standing->wins }}</td><td class="px-5 py-4">{{ $standing->draws }}</td><td class="px-5 py-4">{{ $standing->losses }}</td><td class="px-5 py-4">{{ $standing->goal_difference }}</td><td class="px-5 py-4 font-extrabold text-uno-lime">{{ $standing->points }}</td></tr>@endforeach</tbody></table></div></section>
    <section class="mt-8"><h2 class="text-2xl font-bold">Match results.</h2><div class="mt-4 grid gap-3">@foreach ($simulation->matches->sortBy('id') as $match)<article class="rounded-2xl border border-white/10 bg-white/[.03] p-4"><div class="flex flex-wrap items-center justify-between gap-3"><span class="font-bold">{{ $match->homeUser->name }} <strong class="mx-2 text-uno-lime">{{ $match->home_score }} - {{ $match->away_score }}</strong> {{ $match->awayUser->name }}</span><span class="text-xs font-extrabold uppercase tracking-wider text-white/40">{{ str_replace('_', ' ', $match->result) }}{{ $match->boost_user_id ? ' · Boost' : '' }}</span></div>@if ($match->narrative)<p class="mt-2 text-xs leading-5 text-white/50">{{ $match->narrative }}</p>@endif</article>@endforeach</div></section>
  @elseif ($league->simulations()->where('status', 'pending')->exists() || $league->simulations()->where('status', 'running')->exists())
    <div class="mt-8 rounded-2xl border border-sky-300/20 bg-sky-300/10 px-5 py-4 text-sm text-sky-100">The league simulation is being prepared. Refresh this table shortly.</div>
  @endif
</main>
@endsection
