<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="@yield('description', 'Amadara UNO Football League — every match, every moment.')">
  <title>@yield('title', 'Amadara UNO | Football League')</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            uno: { navy: '#071d33', blue: '#0878d1', bright: '#1197ef', green: '#49cf19', lime: '#7dec19', ice: '#eff8ff' }
          },
          boxShadow: {
            uno: '0 25px 70px rgba(7, 29, 51, .24)',
            glow: '0 0 45px rgba(73, 207, 25, .20)'
          }
        }
      }
    };
  </script>

  <style>
    :root { color-scheme: dark; }
    html { scroll-behavior: smooth; }
    body { font-family: "Space Grotesk", sans-serif; background: #031323; color: #f7fbff; }
    .pitch-grid { background-image: linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px); background-size: 42px 42px; }
    .stadium-glow { background: radial-gradient(circle at 10% 5%, rgba(17,151,239,.25), transparent 32%), radial-gradient(circle at 90% 18%, rgba(73,207,25,.13), transparent 26%); }
    .glass-panel { background: rgba(8,39,70,.72); border: 1px solid rgba(255,255,255,.12); backdrop-filter: blur(18px); }
    .editorial-line { position: relative; }
    .editorial-line::after { content: ''; display: block; width: 56px; height: 3px; margin-top: 12px; background: #7dec19; }
    .form-dot { width: 24px; height: 24px; display: grid; place-items: center; border-radius: 999px; font-size: 10px; font-weight: 800; }
    .form-win { background: #7dec19; color: #071d33; }
    .form-draw { background: #6c8298; color: white; }
    .form-loss { background: #e04a61; color: white; }
    .formation-row { --slot-count: 1; }
    @media (max-width: 639px) {
      .formation-row { display: grid; grid-template-columns: repeat(var(--slot-count), minmax(0, 1fr)); gap: .35rem; }
      .formation-row:not(:has(> :nth-child(2))) { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .formation-row:not(:has(> :nth-child(2))) > :only-child { grid-column: 2; }
      .formation-slot { width: 100%; min-width: 0; padding: .35rem .25rem; font-size: 9px; }
      .formation-slot > div { width: 2rem; height: 2rem; }
      #slots > div { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: .35rem; }
      #slots > div:has(> button:nth-child(2)) { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      #slots > div:has(> button:nth-child(3)):not(:has(> button:nth-child(4))) { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      #slots > div:has(> button:nth-child(4)) { grid-template-columns: repeat(4, minmax(0, 1fr)); }
      #slots > div:has(> button:nth-child(5)) { grid-template-columns: repeat(5, minmax(0, 1fr)); }
      #slots > div:not(:has(> button:nth-child(2))) { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      #slots > div:not(:has(> button:nth-child(2))) > :only-child { grid-column: 2; }
      #slots > div > button { width: 100%; min-width: 0; padding: .35rem .25rem; font-size: 9px; }
    }
    .match-card { transition: transform .25s ease, border-color .25s ease, background .25s ease; }
    .match-card:hover { transform: translateY(-5px); border-color: rgba(125,237,25,.48); background: rgba(12,53,88,.9); }
    :focus-visible { outline: 3px solid #7dec19; outline-offset: 4px; }
  </style>
</head>
<body class="min-h-screen overflow-x-hidden selection:bg-uno-lime selection:text-uno-navy">
  <div class="stadium-glow pitch-grid min-h-screen">
    <x-navigation />
    <x-toast.success />
    <x-toast.error />

    @yield('content')

    <x-footer />
  </div>
</body>
</html>
