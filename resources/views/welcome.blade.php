@extends('layouts.app')

@section('title', 'Amadara UNO | Football League')
@section('description', 'Amadara UNO Football League — every match, every moment.')

@section('content')
<main id="home">
      <section class="relative mx-auto max-w-7xl px-5 pb-16 pt-12 lg:px-8 lg:pb-24 lg:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-[1.02fr_.98fr]">
          <div>
            <div class="mb-6 flex items-center gap-3 text-xs font-extrabold uppercase tracking-[.22em] text-uno-lime">
              <span class="h-px w-10 bg-uno-lime"></span> Season 2026 / Matchweek 12</div>
            <h1 class="max-w-3xl text-5xl font-bold leading-[.96] tracking-[-.06em] sm:text-7xl lg:text-[88px]">The
              league is<br><span class="text-uno-lime">wide open.</span></h1>
            <p class="mt-7 max-w-xl text-base leading-8 text-white/60 sm:text-lg">Big nights. Bold clubs. No easy
              points. Follow the race for the Amadara UNO title as every matchday changes the table.</p>
            <div class="mt-9 flex flex-wrap gap-3">
              <a href="#matches"
                class="rounded-xl bg-uno-lime px-6 py-4 text-sm font-extrabold text-uno-navy transition hover:-translate-y-1 hover:bg-white">Explore
                matchday <i class="bx bx-right-arrow-alt align-middle text-xl"></i></a>
              <a href="#table"
                class="rounded-xl border border-white/20 bg-white/5 px-6 py-4 text-sm font-bold text-white transition hover:border-uno-lime/60 hover:bg-white/10">View
                the table</a>
            </div>
            <div class="mt-12 grid max-w-lg grid-cols-3 divide-x divide-white/10 border-y border-white/10 py-5">
              <div><b class="block text-2xl">18</b><span
                  class="text-xs font-bold uppercase tracking-widest text-white/40">Clubs</span></div>
              <div class="pl-5"><b class="block text-2xl">306</b><span
                  class="text-xs font-bold uppercase tracking-widest text-white/40">Matches</span></div>
              <div class="pl-5"><b class="block text-2xl">34</b><span
                  class="text-xs font-bold uppercase tracking-widest text-white/40">Rounds</span></div>
            </div>
          </div>

          <div class="relative">
            <div class="absolute -inset-8 rounded-full bg-uno-blue/15 blur-3xl"></div>
            <div class="glass-panel relative overflow-hidden rounded-[28px] p-6 shadow-uno sm:p-8">
              <div class="absolute right-0 top-0 h-40 w-40 rounded-full bg-uno-lime/10 blur-3xl"></div>
              <div class="relative flex items-start justify-between">
                <div>
                  <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Live from Uno National</p>
                  <h2 class="mt-2 text-2xl font-bold">The featured fixture</h2>
                </div><span class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-extrabold text-red-300"><i
                    class="bx bx-radio-circle-marked mr-1"></i>LIVE 72'</span>
              </div>
              <div class="relative my-10 grid grid-cols-[1fr_auto_1fr] items-center gap-3 text-center">
                <div><span
                    class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-uno-blue text-3xl font-bold shadow-lg shadow-blue-500/25">A</span><b
                    class="mt-4 block text-sm">Amadara Sharks</b><small class="text-white/40">1st / 26 pts</small></div>
                <div><span class="text-4xl font-bold tracking-tight">2 - 1</span><span
                    class="mt-2 block text-xs font-bold uppercase tracking-widest text-uno-lime">Second half</span>
                </div>
                <div><span
                    class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-uno-lime text-3xl font-bold text-uno-navy shadow-lg shadow-lime-500/20">V</span><b
                    class="mt-4 block text-sm">Valentoga FC</b><small class="text-white/40">2nd / 23 pts</small></div>
              </div>
              <div
                class="relative flex items-center justify-between border-t border-white/10 pt-4 text-xs text-white/45">
                <span><i class="bx bx-calendar mr-1"></i> Matchweek 12</span><span><i class="bx bx-map mr-1"></i> Uno
                  National Stadium</span></div>
              <a href="#matches"
                class="relative mt-6 flex w-full items-center justify-center rounded-xl bg-white/10 py-3 text-sm font-bold transition hover:bg-uno-lime hover:text-uno-navy">Open
                match center <i class="bx bx-right-arrow-alt ml-2 text-xl"></i></a>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-7xl px-5 pb-20 lg:px-8" aria-labelledby="story-title">
        <div class="grid gap-5 lg:grid-cols-[1.15fr_.85fr]">
          <article
            class="relative min-h-[330px] overflow-hidden rounded-[28px] bg-gradient-to-br from-uno-blue to-[#062544] p-7 shadow-uno sm:p-10">
            <div class="absolute -right-12 -top-20 opacity-20"><img src="{{ asset('logo.png') }}" alt=""
                class="w-80 rotate-12"></div>
            <div class="relative flex h-full flex-col justify-between">
              <div>
                <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Matchday story</p>
                <h2 id="story-title" class="editorial-line mt-3 max-w-md text-3xl font-bold leading-tight sm:text-4xl">
                  Amadara have turned pressure into a habit.</h2>
              </div>
              <p class="relative mt-10 max-w-md text-sm leading-7 text-white/65">Eight wins from eleven. The Sharks sit
                top, but the chasing pack is close enough to smell the trophy. One mistake changes everything.</p>
            </div>
          </article>
          <div class="glass-panel rounded-[28px] p-7 sm:p-8">
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">League pulse</p>
            <h2 class="mt-2 text-2xl font-bold">This matchday</h2>
            <div class="mt-7 grid grid-cols-2 gap-4">
              <div class="rounded-2xl bg-white/5 p-4"><i class="bx bx-football text-2xl text-uno-blue"></i><b
                  class="mt-3 block text-2xl">24</b><span class="text-xs text-white/45">Matches played</span></div>
              <div class="rounded-2xl bg-white/5 p-4"><i class="bx bx-target-lock text-2xl text-uno-lime"></i><b
                  class="mt-3 block text-2xl">74</b><span class="text-xs text-white/45">Goals scored</span></div>
              <div class="rounded-2xl bg-white/5 p-4"><i class="bx bx-group text-2xl text-purple-300"></i><b
                  class="mt-3 block text-2xl">8.4K</b><span class="text-xs text-white/45">Supporters</span></div>
              <div class="rounded-2xl bg-white/5 p-4"><i class="bx bx-calendar text-2xl text-amber-300"></i><b
                  class="mt-3 block text-2xl">12</b><span class="text-xs text-white/45">Upcoming fixtures</span></div>
            </div>
          </div>
        </div>
      </section>

      <section id="matches" class="mx-auto max-w-7xl px-5 pb-20 lg:px-8" aria-labelledby="matches-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Next up</p>
            <h2 id="matches-title" class="mt-2 text-3xl font-bold sm:text-4xl">Fixtures with a pulse</h2>
          </div><span class="text-sm text-white/45">Matchweek 13 Â· 24â€”26 July</span>
        </div>
        <div class="mt-8 grid gap-4 lg:grid-cols-3">
          <div class="match-card glass-panel rounded-3xl p-6">
            <div class="flex justify-between text-xs font-bold uppercase tracking-widest text-white/45"><span>Fri, 24
                Jul</span><span class="text-uno-lime">20:30</span></div>
            <div class="mt-7 space-y-4">
              <p class="flex items-center gap-3 font-bold"><span
                  class="grid h-11 w-11 place-items-center rounded-xl bg-uno-blue text-lg">A</span>Amadara Sharks</p>
              <p class="flex items-center gap-3 font-bold"><span
                  class="grid h-11 w-11 place-items-center rounded-xl bg-red-600 text-lg">K</span>Kalimpogsa</p>
            </div>
            <p class="mt-7 border-t border-white/10 pt-4 text-xs text-white/40"><i class="bx bx-map mr-1"></i> Uno
              National Stadium</p>
          </div>
          <div class="match-card glass-panel rounded-3xl p-6">
            <div class="flex justify-between text-xs font-bold uppercase tracking-widest text-white/45"><span>Sat, 25
                Jul</span><span class="text-uno-lime">18:00</span></div>
            <div class="mt-7 space-y-4">
              <p class="flex items-center gap-3 font-bold"><span
                  class="grid h-11 w-11 place-items-center rounded-xl bg-uno-lime text-lg text-uno-navy">V</span>Valentoga
                FC</p>
              <p class="flex items-center gap-3 font-bold"><span
                  class="grid h-11 w-11 place-items-center rounded-xl bg-purple-700 text-lg">G</span>Virgoda</p>
            </div>
            <p class="mt-7 border-t border-white/10 pt-4 text-xs text-white/40"><i class="bx bx-map mr-1"></i> Energy
              UNO Arena</p>
          </div>
          <div class="match-card glass-panel rounded-3xl p-6">
            <div class="flex justify-between text-xs font-bold uppercase tracking-widest text-white/45"><span>Sun, 26
                Jul</span><span class="text-uno-lime">21:00</span></div>
            <div class="mt-7 space-y-4">
              <p class="flex items-center gap-3 font-bold"><span
                  class="grid h-11 w-11 place-items-center rounded-xl bg-slate-800 text-lg">M</span>System Office</p>
              <p class="flex items-center gap-3 font-bold"><span
                  class="grid h-11 w-11 place-items-center rounded-xl bg-orange-500 text-lg">U</span>United Dara</p>
            </div>
            <p class="mt-7 border-t border-white/10 pt-4 text-xs text-white/40"><i class="bx bx-map mr-1"></i> FedUNO
              Park</p>
          </div>
        </div>
      </section>

      <section class="border-y border-white/10 bg-[#061a2d]/70 py-20" aria-labelledby="form-title">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">The momentum board</p>
              <h2 id="form-title" class="mt-2 text-3xl font-bold sm:text-4xl">Form & streaks</h2>
            </div>
            <p class="max-w-sm text-sm leading-6 text-white/45">The table tells you where clubs are. Form tells you
              where theyâ€™re going.</p>
          </div>
          <div class="mt-8 grid gap-4 md:grid-cols-2">
            <div class="glass-panel rounded-3xl p-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3"><span
                    class="grid h-12 w-12 place-items-center rounded-2xl bg-uno-blue text-lg font-bold">A</span>
                  <div><b class="block">Amadara Sharks</b><span class="text-xs text-white/45">1st place Â· 26 pts</span>
                  </div>
                </div><span class="rounded-full bg-uno-lime/15 px-3 py-1 text-xs font-bold text-uno-lime">Unbeaten in
                  5</span>
              </div>
              <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-5">
                <div class="flex gap-2"><span class="form-dot form-win">W</span><span
                    class="form-dot form-win">W</span><span class="form-dot form-draw">D</span><span
                    class="form-dot form-win">W</span><span class="form-dot form-win">W</span></div><span
                  class="text-xs font-bold uppercase tracking-widest text-white/40">+9 goal diff</span>
              </div>
            </div>
            <div class="glass-panel rounded-3xl p-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3"><span
                    class="grid h-12 w-12 place-items-center rounded-2xl bg-uno-lime text-lg font-bold text-uno-navy">V</span>
                  <div><b class="block">Valentoga FC</b><span class="text-xs text-white/45">2nd place Â· 23 pts</span>
                  </div>
                </div><span class="rounded-full bg-uno-blue/15 px-3 py-1 text-xs font-bold text-sky-300">On a 3-match
                  run</span>
              </div>
              <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-5">
                <div class="flex gap-2"><span class="form-dot form-win">W</span><span
                    class="form-dot form-win">W</span><span class="form-dot form-loss">L</span><span
                    class="form-dot form-win">W</span><span class="form-dot form-draw">D</span></div><span
                  class="text-xs font-bold uppercase tracking-widest text-white/40">+6 goal diff</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="table" class="mx-auto max-w-7xl px-5 py-20 lg:px-8" aria-labelledby="table-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Competition</p>
            <h2 id="table-title" class="mt-2 text-3xl font-bold sm:text-4xl">League standings</h2>
          </div><span class="text-sm text-white/45">After Matchweek 12</span>
        </div>
        <div class="mt-8 overflow-x-auto rounded-3xl border border-white/10">
          <table class="w-full min-w-[650px] text-left text-sm">
            <thead class="bg-white/5 text-xs uppercase tracking-widest text-white/40">
              <tr>
                <th class="p-5">#</th>
                <th>Club</th>
                <th>P</th>
                <th>W</th>
                <th>D</th>
                <th>L</th>
                <th>GD</th>
                <th class="pr-6 text-right">Pts</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <tr class="bg-uno-lime/5">
                <td class="p-5 font-bold text-uno-lime">1</td>
                <td class="font-bold">Amadara Sharks</td>
                <td>11</td>
                <td>8</td>
                <td>2</td>
                <td>1</td>
                <td class="text-uno-lime">+15</td>
                <td class="pr-6 text-right font-bold">26</td>
              </tr>
              <tr>
                <td class="p-5 font-bold">2</td>
                <td class="font-bold">Valentoga FC</td>
                <td>11</td>
                <td>7</td>
                <td>2</td>
                <td>2</td>
                <td class="text-uno-lime">+10</td>
                <td class="pr-6 text-right font-bold">23</td>
              </tr>
              <tr>
                <td class="p-5 font-bold">3</td>
                <td class="font-bold">Kalimpogsa</td>
                <td>11</td>
                <td>6</td>
                <td>3</td>
                <td>2</td>
                <td class="text-uno-lime">+8</td>
                <td class="pr-6 text-right font-bold">21</td>
              </tr>
              <tr>
                <td class="p-5 font-bold">4</td>
                <td class="font-bold">Virgoda</td>
                <td>11</td>
                <td>5</td>
                <td>3</td>
                <td>3</td>
                <td>+4</td>
                <td class="pr-6 text-right font-bold">18</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section id="teams" class="mx-auto max-w-7xl px-5 pb-24 text-center lg:px-8" aria-labelledby="teams-title">
        <p class="text-xs font-extrabold uppercase tracking-[.2em] text-uno-lime">Meet the clubs</p>
        <h2 id="teams-title" class="mt-2 text-3xl font-bold sm:text-4xl">Built for the big stage</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="match-card glass-panel rounded-3xl p-7"><span
              class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-uno-blue text-3xl font-bold">A</span><b
              class="mt-5 block">Amadara Sharks</b><span class="mt-1 block text-xs text-white/40">The leaders</span>
          </div>
          <div class="match-card glass-panel rounded-3xl p-7"><span
              class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-uno-lime text-3xl font-bold text-uno-navy">V</span><b
              class="mt-5 block">Valentoga FC</b><span class="mt-1 block text-xs text-white/40">The challengers</span>
          </div>
          <div class="match-card glass-panel rounded-3xl p-7"><span
              class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-red-600 text-3xl font-bold">K</span><b
              class="mt-5 block">Kalimpogsa</b><span class="mt-1 block text-xs text-white/40">The disruptors</span>
          </div>
          <div class="match-card glass-panel rounded-3xl p-7"><span
              class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-purple-700 text-3xl font-bold">G</span><b
              class="mt-5 block">Virgoda</b><span class="mt-1 block text-xs text-white/40">The contenders</span></div>
        </div>
      </section>
    </main>
@endsection
