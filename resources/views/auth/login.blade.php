
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Sign in to Amadara UNO Football League." />
  <title>Login | Amadara UNO</title>

  <!-- TailwindCSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Boxicons -->
  <link
    href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
    rel="stylesheet"
  />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            uno: {
              navy: "#071d33",
              blue: "#0878d1",
              bright: "#1197ef",
              green: "#49cf19",
              lime: "#7dec19",
              ice: "#eff8ff"
            }
          },
          boxShadow: {
            "uno": "0 25px 70px rgba(7, 29, 51, 0.22)",
            "glow": "0 0 45px rgba(73, 207, 25, 0.20)"
          }
        }
      }
    };
  </script>

  <style>
    body {
      background:
        radial-gradient(circle at 15% 12%, rgba(17, 151, 239, 0.18), transparent 30%),
        radial-gradient(circle at 85% 85%, rgba(73, 207, 25, 0.15), transparent 28%),
        linear-gradient(135deg, #031323 0%, #082b4b 52%, #041827 100%);
        font-family: "Space Grotesk", sans-serif;
    }

    .pitch-grid {
      background-image:
        linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
      background-size: 42px 42px;
    }

    .glass {
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }

    .input-ring:focus-within {
      border-color: #1197ef;
      box-shadow: 0 0 0 4px rgba(17, 151, 239, 0.13);
    }

    .football-orbit {
      animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(-2deg); }
      50% { transform: translateY(-12px) rotate(2deg); }
    }

    .toast {
      transform: translateY(18px);
      opacity: 0;
      pointer-events: none;
      transition: all .3s ease;
    }

    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }
  </style>
</head>

<body class="min-h-screen text-uno-navy selection:bg-uno-green selection:text-white">
  <main class="pitch-grid relative min-h-screen overflow-hidden px-4 py-8 sm:px-6 lg:px-10">
    <!-- Decorative elements -->
    <div class="pointer-events-none absolute -left-28 -top-28 h-80 w-80 rounded-full border border-white/10"></div>
    <div class="pointer-events-none absolute -left-12 -top-12 h-52 w-52 rounded-full border border-uno-green/20"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-24 h-96 w-96 rounded-full border border-white/10"></div>
    <div class="pointer-events-none absolute bottom-12 right-14 h-28 w-28 rounded-full bg-uno-green/10 blur-2xl"></div>

    <section class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
      <div class="grid w-full overflow-hidden rounded-[32px] bg-white/10 shadow-uno ring-1 ring-white/15 lg:grid-cols-[1.05fr_.95fr]">

        <!-- Brand panel -->
        <div class="relative hidden min-h-[680px] overflow-hidden p-12 lg:flex lg:flex-col lg:justify-between">
          <div class="absolute inset-0 bg-gradient-to-br from-uno-blue/85 via-uno-navy/75 to-uno-green/55"></div>
          <div class="absolute inset-0 opacity-25 pitch-grid"></div>

          <div class="relative z-10">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-white">
              <i class="bx bx-football text-lg text-uno-lime"></i>
              Amadara UNO
            </span>
          </div>

          <div class="football-orbit relative z-10 mx-auto w-full max-w-md">
            <div class="absolute inset-0 rounded-full bg-uno-green/20 blur-3xl"></div>
            <img
              src="{{ asset('logo.png') }}"
              alt="Amadara UNO Football League logo"
              class="relative w-full rounded-3xl object-contain shadow-glow"
            />
          </div>

          <div class="relative z-10 max-w-lg">
            <h2 class="text-4xl font-black leading-tight text-white">
              Your football universe,
              <span class="text-uno-lime">one login away.</span>
            </h2>
            <p class="mt-4 max-w-md text-sm leading-7 text-white/75">
              Manage leagues, clubs, fixtures, squads and matchday moments from one powerful platform.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
              <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold text-white/80">
                <i class="bx bx-trophy mr-1 text-uno-lime"></i> Competitions
              </span>
              <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold text-white/80">
                <i class="bx bx-group mr-1 text-uno-lime"></i> Teams
              </span>
              <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold text-white/80">
                <i class="bx bx-line-chart mr-1 text-uno-lime"></i> Statistics
              </span>
            </div>
          </div>
        </div>

        <!-- Login panel -->
        <div class="glass flex min-h-[680px] items-center p-6 sm:p-10 lg:p-14">
          <div class="mx-auto w-full max-w-md">
            <div class="mb-8 flex items-center justify-center lg:hidden">
              <img src="{{ asset('logo.png') }}" alt="Amadara UNO" class="h-16 w-auto rounded-xl object-contain" />
            </div>

            <div class="text-center">
              <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-uno-blue">Welcome back</p>
            </div>

            <form id="loginForm" class="mt-9 space-y-5" method="POST" action="{{ route('login') }}">
              @csrf
              <!-- Email -->
              <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email address</label>
                <div class="input-ring flex items-center rounded-2xl border border-slate-200 bg-white transition">
                  <i class="bx bx-envelope ml-4 text-xl text-slate-400"></i>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    class="w-full rounded-2xl bg-transparent px-3 py-4 text-sm font-medium text-slate-800 outline-none placeholder:text-slate-400"
                  />
                </div>
                @error('email')<p class="mt-1.5 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
              </div>

              <!-- Password -->
              <div>
                <div class="mb-2 flex items-center justify-between">
                  <label for="password" class="block text-sm font-bold text-slate-700">Password</label>
                  <button type="button" class="text-xs font-bold text-uno-blue transition hover:text-uno-green">
                    Forgot password?
                  </button>
                </div>

                <div class="input-ring flex items-center rounded-2xl border border-slate-200 bg-white transition">
                  <i class="bx bx-lock-alt ml-4 text-xl text-slate-400"></i>
                  <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="w-full bg-transparent px-3 py-4 text-sm font-medium text-slate-800 outline-none placeholder:text-slate-400"
                  />
                  <button
                    id="togglePassword"
                    type="button"
                    aria-label="Show password"
                    class="mr-4 grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-uno-ice hover:text-uno-blue"
                  >
                    <i class="bx bx-show text-xl"></i>
                  </button>
                </div>
                @error('password')<p class="mt-1.5 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
              </div>

              <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-slate-600">
                <input
                  type="checkbox"
                  name="remember"
                  value="1"
                  class="h-4 w-4 rounded border-slate-300 accent-uno-green"
                />
                Remember me
              </label>

              <button
                type="submit"
                class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-uno-blue to-uno-bright px-5 py-4 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-200"
              >
                Login
                <i class="bx bx-right-arrow-alt text-xl transition group-hover:translate-x-1"></i>
              </button>
            </form>

            <div class="my-7 flex items-center gap-4">
              <div class="h-px flex-1 bg-slate-200"></div>
              <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">or continue with</span>
              <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <a
              class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm font-extrabold text-slate-700 transition hover:-translate-y-0.5 hover:border-uno-blue/40 hover:bg-uno-ice"
              href="{{ route('oauth.redirect') }}"
            >
              <i class="bx bxl-google text-2xl text-red-500"></i>
              Login with Google
            </a>

            <p class="mt-8 text-center text-sm text-slate-500">
              New to Amadara UNO?
              <a
                class="font-extrabold text-uno-green transition hover:text-uno-blue"
                href="{{ route('register') }}"
              >
                Create an account
              </a>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Toast -->
    <div
      id="toast"
      class="toast fixed bottom-6 left-1/2 z-50 flex -translate-x-1/2 items-center gap-3 rounded-2xl bg-uno-navy px-5 py-4 text-sm font-bold text-white shadow-2xl"
    >
      <i id="toastIcon" class="bx bx-check-circle text-xl text-uno-lime"></i>
      <span id="toastMessage">Success</span>
    </div>
  </main>

  <script>
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', () => {
      const isPassword = password.type === 'password';

      password.type = isPassword ? 'text' : 'password';
      togglePassword.innerHTML = `<i class="bx ${isPassword ? 'bx-hide' : 'bx-show'} text-xl"></i>`;
      togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });

  </script>
</body>
</html>
