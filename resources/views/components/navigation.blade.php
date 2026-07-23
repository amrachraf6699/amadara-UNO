<header class="sticky top-0 z-50 border-b border-white/10 bg-[#031323]/85 backdrop-blur-xl">
  <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 lg:px-8" aria-label="Main navigation">
    <a href="{{ url('/') }}#home" class="flex items-center gap-3" aria-label="Amadara UNO home">
      <span class="hud-icon-frame h-11 w-11"><img src="{{ asset('logo.png') }}" alt="Amadara UNO Football League" class="h-9 w-10 object-contain"></span>
      <span class="hidden text-xs font-black tracking-[.2em] text-white sm:block">AMADARA <span class="text-uno-lime">UNO</span></span>
    </a>

    @auth
      <div class="flex items-center gap-2 sm:gap-3">
        <a href="{{ route('dashboard.index') }}" data-spa-link @class(['inline-flex items-center gap-2 rounded-xl px-3 py-2.5 text-xs font-extrabold transition sm:px-4 sm:text-sm', 'border border-uno-lime/30 bg-uno-lime/10 text-uno-lime' => request()->routeIs('dashboard.index', 'leagues.*', 'squads.*', 'cards.*'), 'border-white/15 bg-white/5 text-white' => ! request()->routeIs('dashboard.index', 'leagues.*', 'squads.*', 'cards.*'), 'hover:-translate-y-0.5 hover:border-uno-lime/60 hover:bg-uno-lime hover:text-uno-navy']) aria-current="{{ request()->routeIs('dashboard.index', 'leagues.*', 'squads.*', 'cards.*') ? 'page' : 'false' }}">
          <i class="bx bx-football text-lg"></i><span>Dashboard</span>
        </a>
        <span class="hidden border-l border-white/10 pl-3 text-xs font-bold text-white/60 sm:block">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" aria-label="Log out" title="Log out" class="grid h-11 w-11 place-items-center rounded-xl border border-white/15 bg-white/5 text-white transition hover:-translate-y-0.5 hover:border-uno-lime/60 hover:bg-uno-lime hover:text-uno-navy">
            <i class="bx bx-log-out text-xl"></i>
          </button>
        </form>
      </div>
    @else
      <a href="{{ route('login') }}" class="rounded-xl bg-uno-lime px-5 py-3 text-sm font-extrabold text-uno-navy transition hover:-translate-y-0.5 hover:bg-white">
        Log in <i class="bx bx-right-arrow-alt align-middle text-lg"></i>
      </a>
    @endauth
  </nav>
</header>
