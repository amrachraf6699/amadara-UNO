@extends('layouts.app')

@section('title', 'Build your squad | Amadara UNO')
@section('description', 'Choose your locked squad for this league.')

@section('content')
@php
  $locked = $squad !== null;
  $saved = $squad?->selections->mapWithKeys(fn ($selection) => [$selection->slot_key => [
      'id' => $selection->footballPlayer->provider_id,
       'name' => $selection->footballPlayer->name,
      'known_name' => $selection->footballPlayer->known_name,
      'nationality' => $selection->footballPlayer->nationality,
      'age' => $selection->footballPlayer->age,
      'height_cm' => $selection->footballPlayer->height_cm,
      'position' => $selection->footballPlayer->position,
      'team_name' => $selection->footballPlayer->team_name,
      'image_url' => $selection->footballPlayer->image_url,
  ]])->toArray() ?? [];
  $slotKeys = ['goalkeeper'];
  if (!$locked) foreach ($formations[array_key_first($formations)] as $role => $count) for ($i = 1; $i <= $count; $i++) $slotKeys[] = "{$role}_{$i}";
@endphp

<main class="mx-auto min-h-[calc(100vh-150px)] max-w-6xl px-5 py-10 lg:px-8 lg:py-14">
  <a href="{{ route('dashboard.index') }}" class="text-sm font-bold text-white/50 hover:text-uno-lime"><i class="bx bx-arrow-back mr-1"></i> Back to leagues</a>
  <div class="mt-6 flex flex-wrap items-end justify-between gap-5">
    <div><p class="text-xs font-extrabold uppercase tracking-[.22em] text-uno-lime">{{ $league->name }}</p><h1 class="mt-2 text-4xl font-bold tracking-[-.04em]">{{ $locked ? 'Your locked squad.' : 'Build your squad.' }}</h1><p class="mt-3 text-sm text-white/50">{{ $locked ? 'This squad is final and cannot be edited.' : 'Pick 11 players and a coach. Every selection is exclusive within this league.' }}</p></div>
    @if ($locked)<span class="rounded-full bg-uno-lime px-4 py-2 text-xs font-extrabold uppercase tracking-wider text-uno-navy"><i class="bx bx-lock-alt mr-1"></i> Locked {{ $squad->formation }}</span>@endif
  </div>

  @if (!$locked)
  <section class="mt-8 glass-panel rounded-3xl p-5 sm:p-7">
    <label for="formation" class="text-sm font-bold">Formation</label>
    <select id="formation" class="mt-2 w-full max-w-xs rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none focus:border-uno-lime">
      @foreach ($formations as $formation => $counts)<option value="{{ $formation }}">{{ $formation }}</option>@endforeach
    </select>
    <div class="mt-5 flex flex-wrap gap-2 text-xs text-white/45"><span id="progress">0 / 12 selected</span><span>•</span><span>11 players + coach</span></div>
  </section>
  @endif

  <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
    <div id="pitch" class="relative min-h-[620px] overflow-hidden rounded-[30px] border border-white/15 bg-gradient-to-b from-emerald-700/80 to-emerald-900/90 p-5 shadow-uno sm:p-8">
      <div class="pointer-events-none absolute inset-5 rounded-2xl border-2 border-white/25 sm:inset-8"><div class="absolute left-1/2 top-1/2 h-28 w-28 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white/25"></div><div class="absolute left-0 right-0 top-1/2 border-t-2 border-white/25"></div><div class="absolute bottom-0 left-1/2 h-20 w-48 -translate-x-1/2 border-2 border-b-0 border-white/25"></div><div class="absolute left-1/2 top-0 h-20 w-48 -translate-x-1/2 border-2 border-t-0 border-white/25"></div></div>
      <div id="slots" class="relative flex min-h-[560px] flex-col justify-between gap-5 py-4"></div>
    </div>
    <aside class="glass-panel rounded-3xl p-5 sm:p-6"><p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Coach</p><p class="mt-2 text-sm text-white/45">Choose your coach from the same football data catalogue.</p><button type="button" data-slot="coach" class="slot-button mt-5 flex min-h-24 w-full items-center justify-center rounded-2xl border border-dashed border-white/25 bg-white/5 p-3 text-center text-sm font-bold text-white/55 hover:border-uno-lime hover:text-uno-lime">+ Select coach</button></aside>
  </section>
  @if (!$locked)<button id="saveSquad" type="button" disabled class="mt-6 w-full rounded-2xl bg-uno-lime px-5 py-4 text-sm font-extrabold text-uno-navy opacity-40 transition hover:bg-white">Save and lock squad</button>@endif
</main>

@if (!$locked)
<div id="playerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#020b15]/80 px-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="playerModalTitle">
  <div class="glass-panel w-full max-w-lg rounded-3xl p-5 shadow-uno sm:p-7"><div class="flex items-start justify-between"><div><p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Selection</p><h2 id="playerModalTitle" class="mt-2 text-2xl font-bold">Choose a player</h2></div><button id="closePlayerModal" type="button" class="text-2xl text-white/45 hover:text-white" aria-label="Close"><i class="bx bx-x"></i></button></div><input id="playerSearch" type="search" minlength="3" autocomplete="off" placeholder="Type at least 3 letters" class="mt-6 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-4 text-sm text-white outline-none focus:border-uno-lime"><p id="searchHint" class="mt-2 text-xs text-white/40">Search begins after three characters.</p><div id="searchResults" class="mt-4 grid max-h-80 gap-2 overflow-y-auto"></div><button id="showMore" type="button" class="mt-4 hidden w-full rounded-xl border border-white/15 px-4 py-3 text-xs font-bold text-white/65 hover:border-uno-lime hover:text-uno-lime">Show more</button></div>
</div>
<script>
(() => {
  const formations = @json($formations);
  const saved = @json($saved);
  const reserved = new Set(@json($reservedIds));
  const slots = document.getElementById('slots'), formation = document.getElementById('formation'), modal = document.getElementById('playerModal'), search = document.getElementById('playerSearch'), results = document.getElementById('searchResults'), more = document.getElementById('showMore'), progress = document.getElementById('progress');
  const selected = {...saved}; let activeSlot = null, timer = null, controller = null, page = 1;
  const labels = { goalkeeper: 'Goalkeeper', defender: 'Defender', midfielder: 'Midfielder', forward: 'Forward', coach: 'Coach' };
  const escape = (value) => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
  const slotList = () => { const list = ['goalkeeper']; Object.entries(formations[formation.value]).forEach(([role, count]) => { for (let i=1;i<=count;i++) list.push(`${role}_${i}`); }); return list; };
  const slotRole = (slot) => slot === 'goalkeeper' ? 'goalkeeper' : slot.split('_')[0];
  const render = () => { slots.innerHTML = ''; const byRole = {}; slotList().forEach(slot => (byRole[slotRole(slot)] ||= []).push(slot)); ['forward','midfielder','defender','goalkeeper'].forEach(role => { if (!byRole[role]) return; const row = document.createElement('div'); row.className = 'flex flex-wrap justify-center gap-3'; row.innerHTML = byRole[role].map(slot => `<button type="button" data-slot="${slot}" class="slot-button w-32 rounded-2xl border border-white/25 bg-black/20 p-3 text-center text-xs font-bold transition hover:border-uno-lime">${selected[slot] ? `<strong class="block truncate text-uno-lime">${escape(selected[slot].known_name || selected[slot].name)}</strong><small class="mt-1 block truncate text-white/60">${escape(selected[slot].position || '')}</small><small class="mt-1 block truncate text-white/40">${escape([selected[slot].nationality, selected[slot].age ? `${selected[slot].age} yrs` : '', selected[slot].height_cm ? `${selected[slot].height_cm} cm` : ''].filter(Boolean).join(' · '))}</small>` : `<span class="block text-white/55">+ ${labels[role]}</span><small class="mt-1 block text-white/35">Slot ${slot.split('_')[1] || ''}</small>`}</button>`).join(''); slots.appendChild(row); }); document.querySelector('[data-slot="coach"]').innerHTML = selected.coach ? `<strong class="block truncate text-uno-lime">${escape(selected.coach.known_name || selected.coach.name)}</strong><small class="mt-1 block truncate text-white/60">${escape(selected.coach.position || 'Coach')}</small><small class="mt-1 block truncate text-white/40">${escape([selected.coach.nationality, selected.coach.age ? `${selected.coach.age} yrs` : '', selected.coach.height_cm ? `${selected.coach.height_cm} cm` : ''].filter(Boolean).join(' · '))}</small>` : '+ Select coach'; const count = Object.keys(selected).length; if (progress) progress.textContent = `${count} / 12 selected`; const save = document.getElementById('saveSquad'); if (save) { save.disabled = count !== 12; save.classList.toggle('opacity-40', count !== 12); } };
  const open = (slot) => { activeSlot = slot; document.getElementById('playerModalTitle').textContent = `Choose ${labels[slotRole(slot)] || 'coach'}`; modal.classList.remove('hidden'); modal.classList.add('flex'); search.value=''; results.innerHTML=''; more.classList.add('hidden'); search.focus(); };
  const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); activeSlot = null; if (controller) controller.abort(); };
  const showResults = (items) => { const selectedIds = new Set(Object.values(selected).map(p => Number(p.id))); items = items.filter(p => !selectedIds.has(Number(p.id))); results.innerHTML = items.length ? items.map(p => `<button type="button" data-player='${JSON.stringify(p).replace(/'/g, '&#039;')}' class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/5 p-3 text-left hover:border-uno-lime"><span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-full bg-uno-blue/30 text-uno-lime">${p.image_url ? `<img src="${escape(p.image_url)}" alt="${escape(p.known_name || p.name)}" class="h-full w-full object-cover">` : '<i class="bx bx-user text-xl"></i>'}</span><span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-2"><strong class="truncate text-sm">${escape(p.known_name || p.name)}</strong><small class="rounded-full bg-uno-lime/15 px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-uno-lime">${escape(p.position || 'Unknown')}</small></span><small class="mt-1 block truncate text-xs text-white/55">${escape(p.name)}</small><small class="mt-1 block truncate text-xs text-white/45">${escape([p.nationality, p.age ? `${p.age} years` : '', p.height_cm ? `${p.height_cm} cm` : ''].filter(Boolean).join(' · ') || 'Details unavailable')}</small></span></button>`).join('') : '<p class="py-5 text-center text-sm text-white/45">No available players found.</p>'; };
  const runSearch = async (isMore = false) => { const q = search.value.trim(); if (q.length < 3) { results.innerHTML=''; more.classList.add('hidden'); return; } if (controller) controller.abort(); controller = new AbortController(); if (!isMore) page=1; document.getElementById('searchHint').textContent='Searching…'; try { const url = new URL(@json(route('squads.players.search', $league)), location.origin); url.searchParams.set('q', q); url.searchParams.set('more', isMore ? '1' : '0'); url.searchParams.set('page', page); const response = await fetch(url, {headers:{Accept:'application/json'}, signal:controller.signal}); const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Search unavailable.'); showResults(data.results || []); more.classList.toggle('hidden', !data.has_more); document.getElementById('searchHint').textContent = data.source === 'database' ? 'Results from your local player catalogue.' : 'Results refreshed from football data.'; } catch (error) { if (error.name !== 'AbortError') { results.innerHTML = `<p class="py-5 text-center text-sm text-red-300">${escape(error.message)}</p>`; more.classList.add('hidden'); } } };
  document.addEventListener('click', e => { const button = e.target.closest('.slot-button'); if (button) open(button.dataset.slot); const result = e.target.closest('[data-player]'); if (result) { selected[activeSlot === 'coach' ? 'coach' : activeSlot] = JSON.parse(result.dataset.player); reserved.add(Number(selected[activeSlot === 'coach' ? 'coach' : activeSlot].id)); close(); render(); } });
  search.addEventListener('input', () => { clearTimeout(timer); timer=setTimeout(() => runSearch(), 300); }); more.addEventListener('click', () => { page++; runSearch(true); }); document.getElementById('closePlayerModal').addEventListener('click', close); modal.addEventListener('click', e => { if (e.target === modal) close(); }); formation.addEventListener('change', () => { Object.keys(selected).filter(k => k !== 'coach' && !slotList().includes(k)).forEach(k => delete selected[k]); render(); });
  document.getElementById('saveSquad').addEventListener('click', async () => { const button = document.getElementById('saveSquad'); button.disabled=true; button.textContent='Saving…'; const payload={formation:formation.value, players:slotList().map(slot=>({slot,provider_id:selected[slot].id})), coach_provider_id:selected.coach.id}; const response=await fetch(@json(route('squads.store',$league)),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content},body:JSON.stringify(payload)}); const data=await response.json(); if(response.ok) location.href=data.redirect_url; else { window.showToast?.(data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to save squad.', 'error'); button.disabled=false; button.textContent='Save and lock squad'; } });
  render();
})();
</script>
@endif
@endsection
