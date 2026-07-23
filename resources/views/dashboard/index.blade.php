@extends('layouts.app')

@section('title', 'Dashboard | Amadara UNO')
@section('description', 'Manage your Amadara UNO football leagues.')

@section('content')
  @php
    $statusLabels = [
      'archived' => 'Archived',
      'yet_to_start' => 'Waiting for players',
      'running' => 'Running',
      'finished' => 'Finished',
    ];
  @endphp

  <main class="mx-auto min-h-[calc(100vh-150px)] max-w-7xl px-5 py-12 lg:px-8 lg:py-16">
    <div class="flex flex-wrap items-end justify-between gap-6">
      <div>
        <p class="text-xs font-extrabold uppercase tracking-[.22em] text-uno-lime">League headquarters</p>
        <h1 class="mt-3 text-4xl font-bold tracking-[-.04em] sm:text-6xl">Your leagues<span class="text-uno-lime">.</span></h1>
        <p class="mt-4 max-w-xl text-sm leading-7 text-white/55 sm:text-base">Keep your competitions close, invite your squad, and get ready for the next matchday.</p>
      </div>
      @if(!$leagues->isEmpty())
      <button id="newLeagueButton" type="button" class="rounded-xl bg-uno-lime px-5 py-3 text-sm font-extrabold text-uno-navy transition hover:-translate-y-0.5 hover:bg-white">
        <i class="bx bx-plus mr-1 align-middle text-lg"></i> New league
      </button>
      @endif
    </div>

    @if ($leagues->isEmpty())
      <section id="emptyLeaguesState" class="glass-panel mt-10 rounded-[28px] px-6 py-16 text-center sm:px-10">
        <div class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-uno-blue/20 text-4xl text-uno-lime"><i class="bx bx-trophy"></i></div>
        <h2 class="mt-6 text-2xl font-bold">Your next competition starts here.</h2>
        <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-white/50">Create a league for your squad or join an existing competition with its five-character code.</p>
        <button type="button" data-open-new-league class="mt-7 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:border-uno-lime/60 hover:bg-uno-lime hover:text-uno-navy">New League</button>
      </section>
    @else
      <section id="leaguesGrid" class="mt-10 overflow-hidden rounded-3xl border border-white/10 bg-white/[.03]" aria-label="Your participating leagues">
        <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-white/5 text-xs uppercase tracking-widest text-white/40"><tr><th class="px-5 py-4">League</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Ready</th><th class="px-5 py-4">Players</th><th class="px-5 py-4 text-right">Action</th></tr></thead><tbody class="divide-y divide-white/10">
        @foreach ($leagues as $league)
          <tr class="hover:bg-white/[.03]"><td class="px-5 py-4"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-uno-blue/20 text-xl text-uno-lime"><i class="{{ $league->icon }}"></i></span><div><strong class="block">{{ $league->name }}</strong><small class="text-white/40">Code: <span class="tracking-[.2em] text-uno-lime">{{ $league->code }}</span></small></div></div></td><td class="px-5 py-4"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white/65">{{ $statusLabels[$league->status] ?? str_replace('_', ' ', ucfirst($league->status)) }}</span></td><td class="px-5 py-4 font-bold text-uno-lime">{{ $league->ready_users_count }} / {{ $league->users_count }}</td><td class="px-5 py-4 text-white/60">{{ $league->users_count }} / {{ $league->max_users }}</td><td class="px-5 py-4 text-right"><a href="{{ route('leagues.show', $league) }}" class="font-extrabold text-uno-lime hover:text-white">Open league <i class="bx bx-right-arrow-alt"></i></a></td></tr>
        @endforeach
        </tbody></table></div>
      </section>
    @endif
  </main>

  <div id="leagueOptionsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#020b15]/80 px-5 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="leagueOptionsTitle">
    <div class="glass-panel w-full max-w-md rounded-[28px] p-6 shadow-uno sm:p-8">
      <div class="flex items-start justify-between"><div><p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">League room</p><h2 id="leagueOptionsTitle" class="mt-2 text-2xl font-bold">What are we doing?</h2></div><button type="button" data-close-modal class="text-2xl text-white/45 hover:text-white" aria-label="Close modal"><i class="bx bx-x"></i></button></div>
      <div class="mt-8 grid gap-3"><button type="button" data-show-create class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 text-left transition hover:border-uno-lime/50 hover:bg-uno-lime/10"><i class="bx bx-plus-circle text-3xl text-uno-lime"></i><span><strong class="block">Create a league</strong><small class="text-white/45">Start a new competition for your squad.</small></span></button><button type="button" data-show-join class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 text-left transition hover:border-uno-blue/60 hover:bg-uno-blue/10"><i class="bx bx-log-in-circle text-3xl text-sky-300"></i><span><strong class="block">Join a league</strong><small class="text-white/45">Enter a code from a league captain.</small></span></button></div>
    </div>
  </div>

  <div id="createLeagueModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-[#020b15]/80 px-3 py-4 backdrop-blur-sm sm:px-5 sm:py-8" role="dialog" aria-modal="true" aria-labelledby="createLeagueTitle">
    <div class="glass-panel max-h-[calc(100dvh-2rem)] w-full max-w-2xl overflow-y-auto rounded-[24px] p-5 shadow-uno sm:max-h-none sm:rounded-[28px] sm:p-8">
      <div class="flex items-start justify-between"><div><p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">New competition</p><h2 id="createLeagueTitle" class="mt-2 text-2xl font-bold">Create a league</h2></div><button type="button" data-close-modal class="text-2xl text-white/45 hover:text-white" aria-label="Close modal"><i class="bx bx-x"></i></button></div>
      <form id="createLeagueForm" class="mt-6 space-y-4 sm:mt-7 sm:space-y-5" method="POST" action="{{ route('leagues.store') }}" enctype="multipart/form-data">
        @csrf
        <div><label for="league-name" class="mb-2 block text-sm font-bold">League name</label><input id="league-name" name="name" value="{{ old('name') }}" required class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none focus:border-uno-lime" placeholder="e.g. Friday Night League"></div>
        <div><span class="mb-2 block text-sm font-bold">Choose an icon</span><input id="league-icon" type="hidden" name="icon" value="{{ old('icon', $leagueIcons[0]) }}"><div class="icon-picker flex max-w-full gap-2 overflow-x-auto pb-2">@foreach ($leagueIcons as $icon)<button type="button" data-icon="{{ $icon }}" class="icon-option grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/5 text-2xl text-white/60 transition hover:border-uno-lime/60 hover:text-uno-lime" aria-label="Choose {{ $icon }}"><i class="{{ $icon }}"></i></button>@endforeach</div></div>
        <div><label for="max-users" class="mb-2 block text-sm font-bold">Maximum users</label><input id="max-users" name="max_users" type="number" min="2" max="10000" value="{{ old('max_users', 10) }}" required class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none focus:border-uno-lime"></div>
        <div><label for="create-team-name" class="mb-2 block text-sm font-bold">Your team name</label><input id="create-team-name" name="team_name" value="{{ old('team_name') }}" maxlength="80" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none focus:border-uno-lime" placeholder="e.g. Amr United"><p class="mt-2 text-xs text-white/40">This appears in this league instead of your account name.</p></div>
        <div><label for="create-team-logo" class="mb-2 block text-sm font-bold">Team logo <span class="font-normal text-white/40">(optional)</span></label><input id="create-team-logo" name="team_logo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-uno-lime file:px-3 file:py-2 file:text-xs file:font-bold file:text-uno-navy"></div>
        <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-4 sm:flex-row sm:justify-end sm:pt-5"><button type="button" data-close-modal class="w-full rounded-xl border border-white/15 px-5 py-3 text-sm font-bold text-white/70 hover:bg-white/10 sm:w-auto">Cancel</button><button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-uno-lime px-5 py-3 text-sm font-extrabold text-uno-navy hover:bg-white sm:w-auto">Create league <i class="bx bx-right-arrow-alt align-middle text-lg"></i></button></div>
      </form>
    </div>
  </div>

  <div id="joinLeagueModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#020b15]/80 px-3 backdrop-blur-sm sm:px-5" role="dialog" aria-modal="true" aria-labelledby="joinLeagueTitle">
    <div class="glass-panel w-full max-w-md rounded-[24px] p-5 shadow-uno sm:rounded-[28px] sm:p-8">
      <div class="flex items-start justify-between"><div><p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Join competition</p><h2 id="joinLeagueTitle" class="mt-2 text-2xl font-bold">Enter the league code</h2></div><button type="button" data-close-modal class="text-2xl text-white/45 hover:text-white" aria-label="Close modal"><i class="bx bx-x"></i></button></div>
      <form id="joinLeagueForm" class="mt-7" method="POST" action="{{ route('leagues.join') }}" enctype="multipart/form-data">
        @csrf
        <label for="league-code" class="mb-2 block text-sm font-bold">Five-character code</label><input id="league-code" name="code" value="{{ old('code') }}" maxlength="5" minlength="5" required autocomplete="off" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-4 text-center text-2xl font-bold uppercase tracking-[.35em] text-uno-lime outline-none focus:border-uno-lime" placeholder="A7K2P"><p class="mt-3 text-xs leading-5 text-white/40">Ask the league captain for the code shown on their dashboard.</p>
        <div class="mt-5"><label for="join-team-name" class="mb-2 block text-sm font-bold">Your team name</label><input id="join-team-name" name="team_name" value="{{ old('team_name') }}" maxlength="80" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none focus:border-uno-lime" placeholder="e.g. Amr United"><p class="mt-2 text-xs text-white/40">Use a team identity instead of your registered name.</p></div>
        <div class="mt-4"><label for="join-team-logo" class="mb-2 block text-sm font-bold">Team logo <span class="font-normal text-white/40">(optional)</span></label><input id="join-team-logo" name="team_logo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-uno-lime file:px-3 file:py-2 file:text-xs file:font-bold file:text-uno-navy"></div>
        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-4 sm:flex-row sm:justify-end sm:pt-5"><button type="button" data-close-modal class="w-full rounded-xl border border-white/15 px-5 py-3 text-sm font-bold text-white/70 hover:bg-white/10 sm:w-auto">Cancel</button><button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-uno-lime px-5 py-3 text-sm font-extrabold text-uno-navy hover:bg-white sm:w-auto">Join league <i class="bx bx-right-arrow-alt align-middle text-lg"></i></button></div>
      </form>
    </div>
  </div>

  <script>
    const modalIds = ['leagueOptionsModal', 'createLeagueModal', 'joinLeagueModal'];
    const closeModals = () => modalIds.forEach((id) => { const modal = document.getElementById(id); modal.classList.add('hidden'); modal.classList.remove('flex'); });
    const showModal = (id) => { closeModals(); const modal = document.getElementById(id); modal.classList.remove('hidden'); modal.classList.add('flex'); };

    document.getElementById('newLeagueButton')?.addEventListener('click', () => showModal('leagueOptionsModal'));
    document.querySelector('[data-open-new-league]')?.addEventListener('click', () => showModal('leagueOptionsModal'));
    document.querySelector('[data-show-create]')?.addEventListener('click', () => showModal('createLeagueModal'));
    document.querySelector('[data-show-join]')?.addEventListener('click', () => showModal('joinLeagueModal'));
    document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModals));
    document.querySelectorAll('[role="dialog"]').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModals(); }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModals(); });

    const iconInput = document.getElementById('league-icon');
    const iconOptions = document.querySelectorAll('.icon-option');
    const defaultIcon = @json($leagueIcons[0]);
    const selectIcon = (icon) => { iconInput.value = icon; iconOptions.forEach((option) => option.classList.toggle('border-uno-lime', option.dataset.icon === icon)); };
    iconOptions.forEach((option) => option.addEventListener('click', () => selectIcon(option.dataset.icon)));
    selectIcon(iconInput.value || defaultIcon);

    const spinner = '<svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle><path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 1-9 9v-3a6 6 0 0 0 6-6h3Z"></path></svg>';
    const setLoading = (button, loading) => {
      if (loading) { button.dataset.originalContent = button.innerHTML; button.disabled = true; button.classList.add('cursor-wait', 'opacity-70'); button.innerHTML = spinner; }
      else { button.disabled = false; button.classList.remove('cursor-wait', 'opacity-70'); button.innerHTML = button.dataset.originalContent; }
    };
    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
    const statusLabels = { archived: 'Archived', yet_to_start: 'Yet to start', running: 'Running', finished: 'Finished' };
    const leagueCard = (league) => `<article class="match-card glass-panel rounded-[28px] p-6"><div class="flex items-start justify-between gap-4"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-uno-blue/20 text-3xl text-uno-lime"><i class="${escapeHtml(league.icon)}"></i></span><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white/65">${escapeHtml(statusLabels[league.status] || league.status)}</span></div><h2 class="mt-6 truncate text-2xl font-bold" title="${escapeHtml(league.name)}">${escapeHtml(league.name)}</h2><div class="mt-6 flex items-center justify-between border-y border-white/10 py-4 text-sm"><span class="text-white/45">League code</span><strong class="tracking-[.25em] text-uno-lime">${escapeHtml(league.code)}</strong></div><div class="mt-5 flex items-center justify-between text-sm"><span class="text-white/45">Ready players</span><strong class="text-uno-lime">${league.ready_users_count} / ${league.users_count}</strong></div><div class="mt-6 flex items-center justify-between text-xs text-white/45"><span><i class="bx bx-group mr-1"></i>${league.users_count} / ${league.max_users} users</span><a href="/leagues/${league.id}/squad" class="font-bold text-uno-lime hover:text-white">Build squad <i class="bx bx-right-arrow-alt"></i></a></div></article>`;
    const appendLeague = (league) => { let grid = document.getElementById('leaguesGrid'); if (!grid) { document.getElementById('emptyLeaguesState')?.remove(); grid = document.createElement('section'); grid.id = 'leaguesGrid'; grid.className = 'mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3'; grid.setAttribute('aria-label', 'Your participating leagues'); document.querySelector('main').appendChild(grid); } grid.insertAdjacentHTML('afterbegin', leagueCard(league)); };

    const submitLeagueForm = async (form) => {
      const button = form.querySelector('button[type="submit"]');
      setLoading(button, true);
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) { const errors = payload.errors ? Object.values(payload.errors).flat() : []; throw new Error(errors[0] || payload.message || 'Something went wrong. Please try again.'); }
        if (payload.redirect_url) { window.location.href = payload.redirect_url; return; }
        appendLeague(payload.league);
        form.reset();
        if (form.id === 'createLeagueForm') selectIcon(defaultIcon);
        closeModals();
        window.showToast?.(payload.message || 'League updated.');
      } catch (error) {
        window.showToast?.(error.message, 'error');
      } finally {
        setLoading(button, false);
      }
    };
    document.getElementById('createLeagueForm')?.addEventListener('submit', (event) => { event.preventDefault(); submitLeagueForm(event.currentTarget); });
    document.getElementById('joinLeagueForm')?.addEventListener('submit', (event) => { event.preventDefault(); submitLeagueForm(event.currentTarget); });
  </script>
@endsection
