<header class="sticky top-0 z-50 border-b border-white/10 bg-[#031323]/85 backdrop-blur-xl">
  <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8" aria-label="Main navigation">
    <a href="{{ url('/') }}#home" class="flex items-center gap-3" aria-label="Amadara UNO home">
      <img src="{{ asset('logo.png') }}" alt="Amadara UNO Football League" class="h-12 w-16 object-contain">
      <span class="hidden text-sm font-bold tracking-[.18em] text-white sm:block">AMADARA <span class="text-uno-lime">UNO</span></span>
    </a>

    @auth
      <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm font-extrabold text-white transition hover:-translate-y-0.5 hover:border-uno-lime/60 hover:bg-uno-lime hover:text-uno-navy">
          <i class="bx bx-grid-alt text-lg"></i><span>Dashboard</span>
        </a>
        <span class="hidden text-sm font-bold text-white/70 sm:block">Hi, {{ auth()->user()->name }}</span>
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
