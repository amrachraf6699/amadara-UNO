<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create an Amadara UNO Football League account.">
  <title>Create account | Amadara UNO</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: { extend: { colors: { uno: { navy: '#071d33', blue: '#0878d1', bright: '#1197ef', green: '#49cf19', lime: '#7dec19', ice: '#eff8ff' } }, boxShadow: { uno: '0 25px 70px rgba(7, 29, 51, .22)', glow: '0 0 45px rgba(73, 207, 25, .20)' } } }
    };
  </script>
  <style>
    body { background: radial-gradient(circle at 15% 12%, rgba(17,151,239,.18), transparent 30%), radial-gradient(circle at 85% 85%, rgba(73,207,25,.15), transparent 28%), linear-gradient(135deg,#031323 0%,#082b4b 52%,#041827 100%); font-family: "Space Grotesk", sans-serif; }
    .pitch-grid { background-image: linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px); background-size: 42px 42px; }
    .glass { background: rgba(255,255,255,.96); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); }
    .input-ring:focus-within { border-color: #1197ef; box-shadow: 0 0 0 4px rgba(17,151,239,.13); }
    :focus-visible { outline: 3px solid #7dec19; outline-offset: 4px; }
  </style>
</head>
<body class="min-h-screen text-uno-navy selection:bg-uno-green selection:text-white">
  <main class="pitch-grid relative min-h-screen overflow-hidden px-4 py-8 sm:px-6 lg:px-10">
    <div class="pointer-events-none absolute -left-28 -top-28 h-80 w-80 rounded-full border border-white/10"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-24 h-96 w-96 rounded-full border border-white/10"></div>

    <section class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
      <div class="grid w-full overflow-hidden rounded-[32px] bg-white/10 shadow-uno ring-1 ring-white/15 lg:grid-cols-[1.05fr_.95fr]">
        <div class="relative hidden min-h-[720px] overflow-hidden p-12 lg:flex lg:flex-col lg:justify-between">
          <div class="absolute inset-0 bg-gradient-to-br from-uno-blue/85 via-uno-navy/75 to-uno-green/55"></div>
          <div class="absolute inset-0 opacity-25 pitch-grid"></div>
          <div class="relative z-10"><span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[.2em] text-white"><i class="bx bx-football text-lg text-uno-lime"></i> Amadara UNO</span></div>
          <div class="relative z-10 mx-auto w-full max-w-md"><div class="absolute inset-0 rounded-full bg-uno-green/20 blur-3xl"></div><img src="{{ asset('logo.png') }}" alt="Amadara UNO Football League logo" class="relative w-full object-contain shadow-glow"></div>
          <div class="relative z-10 max-w-lg"><h1 class="text-4xl font-black leading-tight text-white">Join the football universe, <span class="text-uno-lime">play your part.</span></h1><p class="mt-4 max-w-md text-sm leading-7 text-white/75">Follow competitions, clubs, fixtures, squads and every matchday moment from one powerful platform.</p><div class="mt-8 flex flex-wrap gap-3"><span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold text-white/80"><i class="bx bx-trophy mr-1 text-uno-lime"></i> Competitions</span><span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold text-white/80"><i class="bx bx-group mr-1 text-uno-lime"></i> Teams</span><span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold text-white/80"><i class="bx bx-line-chart mr-1 text-uno-lime"></i> Statistics</span></div></div>
        </div>

        <div class="glass flex min-h-[720px] items-center p-6 sm:p-10 lg:p-14">
          <div class="mx-auto w-full max-w-md">
            <div class="mb-8 flex items-center justify-center lg:hidden"><img src="{{ asset('logo.png') }}" alt="Amadara UNO" class="h-16 w-auto object-contain"></div>
            <div class="text-center"><p class="text-sm font-extrabold uppercase tracking-[.22em] text-uno-blue">Create your account</p><h2 class="mt-3 text-3xl font-black text-uno-navy">Ready for matchday?</h2><p class="mt-3 text-sm leading-6 text-slate-500">Set up your profile and step into the Amadara UNO league.</p></div>

            <div class="mt-8">
              <a class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm font-extrabold text-slate-700 transition hover:-translate-y-0.5 hover:border-uno-blue/40 hover:bg-uno-ice" href="{{ route('oauth.redirect') }}">
                <i class="bx bxl-google text-2xl text-red-500"></i>
                Continue with Google
              </a>
              <div class="my-6 flex items-center gap-4"><div class="h-px flex-1 bg-slate-200"></div><span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">or create with email</span><div class="h-px flex-1 bg-slate-200"></div></div>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('register') }}">
              @csrf
              <div><label for="name" class="mb-2 block text-sm font-bold text-slate-700">Full name</label><div class="input-ring flex items-center rounded-2xl border border-slate-200 bg-white transition"><i class="bx bx-user ml-4 text-xl text-slate-400"></i><input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" placeholder="Your name" required autofocus class="w-full rounded-2xl bg-transparent px-3 py-4 text-sm font-medium text-slate-800 outline-none placeholder:text-slate-400"></div>@error('name')<p class="mt-1.5 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror</div>
              <div><label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email address</label><div class="input-ring flex items-center rounded-2xl border border-slate-200 bg-white transition"><i class="bx bx-envelope ml-4 text-xl text-slate-400"></i><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" required class="w-full rounded-2xl bg-transparent px-3 py-4 text-sm font-medium text-slate-800 outline-none placeholder:text-slate-400"></div>@error('email')<p class="mt-1.5 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror</div>
              <div><label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label><div class="input-ring flex items-center rounded-2xl border border-slate-200 bg-white transition"><i class="bx bx-lock-alt ml-4 text-xl text-slate-400"></i><input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" required class="w-full bg-transparent px-3 py-4 text-sm font-medium text-slate-800 outline-none placeholder:text-slate-400"><button id="togglePassword" type="button" aria-label="Show password" class="mr-4 grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-uno-ice hover:text-uno-blue"><i class="bx bx-show text-xl"></i></button></div>@error('password')<p class="mt-1.5 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror</div>
              <div><label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-700">Confirm password</label><div class="input-ring flex items-center rounded-2xl border border-slate-200 bg-white transition"><i class="bx bx-check-shield ml-4 text-xl text-slate-400"></i><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat your password" required class="w-full bg-transparent px-3 py-4 text-sm font-medium text-slate-800 outline-none placeholder:text-slate-400"><button id="toggleConfirmation" type="button" aria-label="Show password confirmation" class="mr-4 grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-uno-ice hover:text-uno-blue"><i class="bx bx-show text-xl"></i></button></div></div>
              <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-uno-blue to-uno-bright px-5 py-4 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">Create account <i class="bx bx-right-arrow-alt text-xl transition group-hover:translate-x-1"></i></button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">Already have an account? <a class="font-extrabold text-uno-green transition hover:text-uno-blue" href="{{ route('login') }}">Log in</a></p>
          </div>
        </div>
      </div>
    </section>
  </main>
  <script>
    function toggleVisibility(buttonId, inputId) {
      const button = document.getElementById(buttonId);
      const input = document.getElementById(inputId);
      button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.innerHTML = `<i class="bx ${visible ? 'bx-hide' : 'bx-show'} text-xl"></i>`;
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
      });
    }
    toggleVisibility('togglePassword', 'password');
    toggleVisibility('toggleConfirmation', 'password_confirmation');
  </script>
</body>
</html>
