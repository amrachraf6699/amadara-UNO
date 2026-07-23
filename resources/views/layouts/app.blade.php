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
    .hud-shell { position: relative; }
    .hud-shell::before { content: ''; position: absolute; inset: 0 0 auto; height: 420px; pointer-events: none; background: radial-gradient(circle at 12% 0%, rgba(17,151,239,.18), transparent 42%), radial-gradient(circle at 88% 2%, rgba(125,237,25,.10), transparent 34%); }
    .hud-panel { background: linear-gradient(145deg, rgba(8,39,70,.88), rgba(4,25,46,.78)); border: 1px solid rgba(255,255,255,.11); border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.18), inset 0 1px rgba(255,255,255,.06); }
    .hud-panel:hover { border-color: rgba(125,237,25,.32); box-shadow: 0 24px 70px rgba(0,0,0,.24), 0 0 32px rgba(125,237,25,.07); }
    .hud-kicker { color: #7dec19; font-size: 10px; font-weight: 900; letter-spacing: .24em; text-transform: uppercase; }
    .hud-title { letter-spacing: -.055em; text-shadow: 0 0 30px rgba(125,237,25,.08); }
    .hud-number { font-variant-numeric: tabular-nums; letter-spacing: -.06em; }
    .hud-status { display: inline-flex; align-items: center; gap: .45rem; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; background: rgba(255,255,255,.06); padding: .45rem .75rem; font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
    .hud-status::before { content: ''; width: 6px; height: 6px; border-radius: 999px; background: #7dec19; box-shadow: 0 0 12px #7dec19; }
    .hud-status[data-status='finished']::before { background: #55b8ff; box-shadow: 0 0 12px #55b8ff; }
    .hud-status[data-status='running']::before { animation: hud-pulse 1.5s ease-in-out infinite; }
    .hud-meter { height: 6px; overflow: hidden; border-radius: 999px; background: rgba(255,255,255,.09); }
    .hud-meter > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #49cf19, #d8ff5f); box-shadow: 0 0 16px rgba(125,237,25,.55); }
    .hud-action { border-radius: 14px; background: #7dec19; color: #071d33; font-weight: 900; transition: transform .2s ease, box-shadow .2s ease, background .2s ease; }
    .hud-action:hover { transform: translateY(-2px); background: #fff; box-shadow: 0 10px 28px rgba(125,237,25,.18); }
    .hud-icon-frame { display: grid; place-items: center; border: 1px solid rgba(125,237,25,.3); border-radius: 18px; background: linear-gradient(145deg, rgba(8,120,209,.3), rgba(125,237,25,.12)); color: #7dec19; box-shadow: inset 0 1px rgba(255,255,255,.12), 0 0 22px rgba(8,120,209,.12); }
    .hud-league-table tbody { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: .75rem; }
    .hud-league-table tbody tr { display: grid; grid-template-columns: minmax(0, 2.2fr) repeat(3, minmax(90px, .8fr)) minmax(110px, .7fr); align-items: center; border: 1px solid rgba(255,255,255,.09); border-radius: 20px; background: linear-gradient(110deg, rgba(8,39,70,.82), rgba(5,28,49,.68)); transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .hud-league-table tbody tr:hover { transform: translateY(-2px); border-color: rgba(125,237,25,.3); box-shadow: 0 12px 34px rgba(0,0,0,.2); }
    .hud-league-table tbody td { border: 0; }
    .hud-league-table thead { display: none; }
    @media (max-width: 720px) { .hud-league-table tbody tr { grid-template-columns: 1fr 1fr; padding: .45rem; } .hud-league-table tbody td { padding: .7rem .75rem; } .hud-league-table tbody td:first-child { grid-column: 1 / -1; } .hud-league-table tbody td:last-child { text-align: left; } }
    .hud-divider { border-color: rgba(255,255,255,.09); }
    .hud-results > section, .hud-squad > section:not(#playerModal) { transition: border-color .2s ease, box-shadow .2s ease; }
    .hud-results > div span.rounded-full { border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); }
    .hud-results > div form button { border-radius: 14px; background: #7dec19; color: #071d33; font-weight: 900; transition: transform .2s ease, box-shadow .2s ease; }
    .hud-results > div form button:hover { transform: translateY(-2px); background: #fff; box-shadow: 0 10px 28px rgba(125,237,25,.18); }
    .hud-results > section:not(:first-of-type), .hud-squad .glass-panel { border-color: rgba(255,255,255,.10); }
    .hud-results .overflow-hidden.rounded-3xl, .hud-results details, .hud-squad .glass-panel { box-shadow: 0 18px 55px rgba(0,0,0,.13), inset 0 1px rgba(255,255,255,.05); }
    .hud-squad #pitch { border-color: rgba(125,237,25,.28); box-shadow: 0 22px 70px rgba(0,0,0,.3), 0 0 38px rgba(73,207,25,.10); }
    .hud-squad .formation-slot { border-color: rgba(255,255,255,.18); background: linear-gradient(145deg, rgba(7,29,51,.72), rgba(0,0,0,.28)); box-shadow: 0 8px 24px rgba(0,0,0,.15); transition: transform .2s ease, border-color .2s ease; }
    .hud-squad .formation-slot:hover { transform: translateY(-3px); border-color: rgba(125,237,25,.55); }
    .hud-squad [data-power-card] { border-color: rgba(255,255,255,.10); background: linear-gradient(145deg, rgba(255,255,255,.07), rgba(0,0,0,.13)); transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .hud-squad [data-power-card]:hover { transform: translateY(-3px); border-color: rgba(125,237,25,.4); box-shadow: 0 15px 34px rgba(0,0,0,.2); }
    @keyframes hud-pulse { 50% { opacity: .35; transform: scale(.72); } }
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; } }
    @media (max-width: 639px) { .hud-panel { border-radius: 20px; } .hud-title { letter-spacing: -.045em; } }
  </style>
</head>
<body class="min-h-screen overflow-x-hidden selection:bg-uno-lime selection:text-uno-navy">
  <div class="stadium-glow pitch-grid hud-shell min-h-screen">
    <x-navigation />
    <x-toast.success />
    <x-toast.error />

    @yield('content')

    <x-footer />
  </div>
</body>
</html>
