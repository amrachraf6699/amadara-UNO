@extends('layouts.app')

@section('title', $league->name.' | Amadara UNO')
@section('description', 'League table and squad overview.')

@section('content')
<main class="mx-auto min-h-[calc(100vh-150px)] max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
  <a href="{{ route('dashboard.index') }}" class="text-sm font-bold text-white/50 hover:text-uno-lime"><i class="bx bx-arrow-back mr-1"></i> Back to leagues</a>
  <div class="mt-6 flex flex-wrap items-end justify-between gap-5">
    <div><p class="text-xs font-extrabold uppercase tracking-[.22em] text-uno-lime">{{ $league->name }}</p><h1 class="mt-2 text-4xl font-bold tracking-[-.04em]">League table.</h1><p class="mt-3 text-sm text-white/50">See who is ready and view every participant’s locked squad.</p></div>
    <div class="flex flex-wrap items-center gap-3"><span class="rounded-full bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-wider text-white/70">{{ ucfirst(str_replace('_', ' ', $league->status)) }}</span>@if ($league->owner_id === auth()->id() && $league->status === \App\Models\League::STATUS_YET_TO_START && $league->readyUsers->count() === $league->users->count() && $league->users->isNotEmpty())<form method="POST" action="{{ route('leagues.start', $league) }}">@csrf<button type="submit" class="rounded-xl bg-uno-lime px-4 py-2 text-xs font-extrabold text-uno-navy hover:bg-white">Start league</button></form>@endif</div>
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
</main>
@endsection
