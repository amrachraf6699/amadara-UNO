@extends('layouts.app')

@section('title', 'How to play | Amadara UNO')
@section('description', 'Learn how to create, join and play an Amadara UNO football league.')

@section('content')
<main id="home" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
  <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-gradient-to-br from-uno-blue/35 via-[#082746] to-[#031323] px-6 py-12 shadow-uno sm:px-10 sm:py-16 lg:px-16">
    <div class="pointer-events-none absolute -right-24 -top-28 h-80 w-80 rounded-full bg-uno-lime/10 blur-3xl"></div>
    <div class="relative max-w-3xl">
      <p class="hud-kicker">Amadara UNO · How to play</p>
      <h1 class="mt-4 max-w-3xl text-5xl font-black leading-[.96] tracking-[-.06em] sm:text-7xl">Build your team.<br><span class="text-uno-lime">Own the league.</span></h1>
      <p class="mt-7 max-w-2xl text-base leading-8 text-white/65 sm:text-lg">Create a private football competition with your friends, build a locked squad, use your power cards wisely and watch the whole league come alive.</p>
      <a href="{{ route('login') }}" class="mt-9 inline-flex items-center gap-2 rounded-2xl bg-uno-lime px-6 py-4 text-sm font-extrabold text-uno-navy shadow-glow transition hover:-translate-y-1 hover:bg-white">Get started <i class="bx bx-right-arrow-alt text-xl"></i></a>
    </div>
  </section>

  <section class="mt-16" aria-labelledby="how-to-play-title">
    <div class="max-w-2xl"><p class="hud-kicker">How to play</p><h2 id="how-to-play-title" class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">From first invite to final whistle.</h2><p class="mt-4 text-sm leading-7 text-white/50">Every league follows the same simple matchday loop.</p></div>
    <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      @foreach ([['01','Create or join','Start a league or enter a friend’s five-character league code.'],['02','Build your squad','Choose a formation, 11 players and a coach from the football catalogue.'],['03','Ready up','Lock your squad, compare formations and use one card of each type before everyone is ready.'],['04','Play the league','The engine simulates every home-and-away fixture with scorers, events and standings.']] as [$number,$title,$copy])
        <article class="hud-panel p-5"><span class="grid h-10 w-10 place-items-center rounded-xl bg-uno-lime font-black text-uno-navy">{{ $number }}</span><h3 class="mt-5 text-lg font-extrabold">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-white/50">{{ $copy }}</p></article>
      @endforeach
    </div>
  </section>

  <section class="mt-16 grid gap-6 lg:grid-cols-[.9fr_1.1fr]" aria-labelledby="rules-title">
    <div><p class="hud-kicker">The rules</p><h2 id="rules-title" class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Fair competition, big decisions.</h2><p class="mt-4 text-sm leading-7 text-white/50">Your choices shape the story, while the league keeps the competition consistent.</p></div>
    <div class="grid gap-3 sm:grid-cols-2">
      @foreach ([['bx-lock-alt','Locked squads','Once submitted, a squad cannot be changed. Every player is exclusive within the league.'],['bx-shield-quarter','Power cards','Guard protects a player, Steal swaps players and Boost can improve a home fixture.'],['bx-football','Double round robin','Every pair meets twice, once at home and once away.'],['bx-trophy','Local standings','Results, points, goal difference and the Golden Boot are calculated from the completed fixtures.']] as [$icon,$title,$copy])
        <article class="rounded-2xl border border-white/10 bg-white/[.035] p-5"><i class="bx {{ $icon }} text-2xl text-uno-lime"></i><h3 class="mt-4 font-extrabold">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-white/50">{{ $copy }}</p></article>
      @endforeach
    </div>
  </section>

  <section class="mt-16 overflow-hidden rounded-[28px] border border-white/10 bg-white/[.035] p-6 sm:p-10" aria-labelledby="connect-title">
    <div class="grid items-center gap-8 md:grid-cols-[1fr_auto]"><div><p class="hud-kicker">Connect and compete</p><h2 id="connect-title" class="mt-3 text-3xl font-black tracking-tight">Your league is better with your people.</h2><p class="mt-4 max-w-2xl text-sm leading-7 text-white/55">Share the league code with friends, set team names and logos, compare locked formations and follow every match report together.</p></div><div class="grid h-20 w-20 place-items-center rounded-full bg-uno-lime/15 text-4xl text-uno-lime"><i class="bx bx-group"></i></div></div>
  </section>

  <section class="py-16 text-center"><p class="hud-kicker">Ready for matchday?</p><h2 class="mt-3 text-3xl font-black sm:text-4xl">Step into your football universe.</h2><a href="{{ route('login') }}" class="mt-7 inline-flex items-center gap-2 rounded-2xl bg-uno-lime px-6 py-4 text-sm font-extrabold text-uno-navy transition hover:-translate-y-1 hover:bg-white">Get started <i class="bx bx-right-arrow-alt text-xl"></i></a></section>
</main>
@endsection
