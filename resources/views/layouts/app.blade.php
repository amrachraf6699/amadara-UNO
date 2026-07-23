@if (request()->header('X-SPA-Request') === '1')
  <div data-spa-fragment data-spa-content data-page-title="@yield('title', 'Amadara UNO | Football League')">
    @yield('content')
  </div>
@else
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
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
  @if (! app()->runningUnitTests())
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @endif

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
    .hud-results { box-sizing: border-box; width: 100%; padding-inline: 1rem; }
    .league-back-link { display: inline-flex; align-items: center; gap: .55rem; color: rgba(255,255,255,.56); font-size: .875rem; font-weight: 800; transition: color .2s ease, transform .2s ease; }
    .league-back-link:hover { color: #7dec19; transform: translateX(-2px); }
    .league-back-icon { display: grid; width: 1.8rem; height: 1.8rem; place-items: center; border: 1px solid rgba(85,184,255,.28); border-radius: .65rem; background: rgba(8,120,209,.12); color: #9bd7ff; font-size: 1rem; }
    .league-header-actions { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; flex-wrap: wrap; }
    .league-copy-actions { display: flex; align-items: center; gap: .45rem; }
    .league-icon-action { display: inline-flex; align-items: center; gap: .45rem; min-height: 3rem; padding: .35rem .65rem .35rem .4rem; border: 1px solid rgba(255,255,255,.14); border-radius: 1rem; background: linear-gradient(145deg, rgba(8,120,209,.16), rgba(255,255,255,.045)); color: rgba(255,255,255,.68); font-size: .68rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; transition: transform .2s ease, border-color .2s ease, color .2s ease, background .2s ease, box-shadow .2s ease; }
    .league-icon-action:hover, .league-icon-action:focus-visible { border-color: rgba(125,237,25,.65); background: rgba(125,237,25,.12); color: #d8ff5f; box-shadow: 0 8px 24px rgba(125,237,25,.1); transform: translateY(-2px); outline: none; }
    .league-icon-action-glyph { display: grid; width: 2.15rem; height: 2.15rem; place-items: center; border: 1px solid rgba(155,215,255,.25); border-radius: .72rem; background: rgba(3,19,35,.55); color: #9bd7ff; font-size: 1.18rem; transition: color .2s ease, border-color .2s ease, background .2s ease; }
    .league-icon-action:hover .league-icon-action-glyph, .league-icon-action:focus-visible .league-icon-action-glyph { border-color: rgba(125,237,25,.45); background: rgba(125,237,25,.16); color: #7dec19; }
    .league-status-pill { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.85rem; border: 1px solid rgba(255,255,255,.13); border-radius: 999px; background: rgba(255,255,255,.055); padding: .55rem .85rem; color: rgba(255,255,255,.68); font-size: .65rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
    .league-status-pill::before { content: ''; width: .42rem; height: .42rem; border-radius: 999px; background: #7dec19; box-shadow: 0 0 12px rgba(125,237,25,.9); }
    .league-start-action { display: inline-flex; min-height: 2.85rem; align-items: center; justify-content: center; gap: .45rem; border-radius: 999px; background: linear-gradient(135deg, #7dec19, #b5f72c); padding: .7rem 1.1rem; color: #071d33; font-size: .7rem; font-weight: 900; box-shadow: 0 8px 22px rgba(125,237,25,.16); transition: transform .2s ease, background .2s ease, box-shadow .2s ease; }
    .league-start-action i { font-size: 1.05rem; }
    .league-start-action:hover { transform: translateY(-2px); background: #fff; box-shadow: 0 12px 28px rgba(125,237,25,.26); }
    .lobby-ready-icon { display: grid; width: 2.65rem; height: 2.65rem; place-items: center; border: 1px solid rgba(125,237,25,.42); border-radius: .9rem; background: rgba(125,237,25,.1); color: #7dec19; font-size: 1.45rem; box-shadow: inset 0 1px rgba(255,255,255,.1), 0 0 20px rgba(125,237,25,.08); }
    [data-spa-content] { position: relative; }
    [data-spa-progress] { position: fixed; top: 0; left: 0; z-index: 100; width: 0; height: 3px; background: #7dec19; box-shadow: 0 0 18px rgba(125,237,25,.8); opacity: 0; pointer-events: none; transition: width .25s ease, opacity .2s ease; }
    [data-spa-progress].is-loading { width: 72%; opacity: 1; }
    .spa-skeleton { min-height: 55vh; padding: 2rem 1rem; }
    .spa-skeleton-block { border: 1px solid rgba(255,255,255,.1); border-radius: 1.25rem; background: linear-gradient(110deg, rgba(255,255,255,.04) 25%, rgba(255,255,255,.1) 37%, rgba(255,255,255,.04) 63%); background-size: 400% 100%; animation: spa-shimmer 1.4s ease infinite; }
    @keyframes spa-shimmer { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
    @media (prefers-reduced-motion: reduce) { [data-spa-progress], .spa-skeleton-block { animation: none; transition: none; } }
    @media (min-width: 1024px) { .hud-results { padding-inline: 2rem; } }
    .font-arabic { font-family: "Tajawal", sans-serif; }
    [dir="auto"] { unicode-bidi: plaintext; }
    .team-avatar { display: grid; place-items: center; overflow: hidden; border-radius: 999px; background: linear-gradient(145deg, rgba(8,120,209,.35), rgba(125,237,25,.12)); color: #7dec19; }
    .team-avatar-sm { width: 2.25rem; height: 2.25rem; }
    .team-avatar-md { width: 4rem; height: 4rem; font-size: 1.5rem; }
    .team-avatar-lg { width: 3.75rem; height: 3.75rem; }
    .team-avatar img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .modal-open { overflow: hidden; }
    .formation-board { position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,.16); border-radius: 24px; background: linear-gradient(180deg, rgba(27,133,77,.95), rgba(5,91,54,.95)); }
    .formation-lines { position: absolute; inset: 1rem; border: 1px solid rgba(255,255,255,.2); border-radius: 16px; background: linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px); background-size: 35px 35px; }
    .fixture-shell { background: linear-gradient(145deg, rgba(8,39,70,.88), rgba(3,20,37,.8)); box-shadow: 0 22px 65px rgba(0,0,0,.2), inset 0 1px rgba(255,255,255,.06); }
    .fixture-nav { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; padding: .55rem .7rem; color: rgba(255,255,255,.65); font-size: .72rem; font-weight: 800; transition: border-color .2s ease, color .2s ease, background .2s ease; }
    .fixture-nav:hover:not(:disabled) { border-color: rgba(125,237,25,.55); background: rgba(125,237,25,.1); color: #d8ff5f; }
    .fixture-nav:disabled { cursor: not-allowed; opacity: .35; }
    .goal-timeline { position: relative; }
    .goal-timeline::before { content: ''; position: absolute; left: 50%; top: 4.15rem; bottom: 1rem; width: 1px; background: rgba(125,237,25,.28); transform: translateX(-50%); }
    .goal-event { position: relative; display: flex; align-items: center; justify-content: flex-end; gap: .55rem; min-height: 3.25rem; padding-left: 50%; }
    .goal-event::before { display: none; }
    .goal-event-away { justify-content: flex-start; padding-right: 50%; padding-left: 0; }
    .goal-event time { position: relative; z-index: 1; flex: 0 0 auto; font-size: .72rem; font-weight: 900; color: #7dec19; }
    .goal-event > span:last-child { min-width: 0; text-align: right; }
    .goal-event-away > span:last-child { text-align: left; }
    .goal-event-dot { position: absolute; z-index: 2; left: calc(50% - .275rem); width: .55rem; height: .55rem; border: 2px solid #031323; border-radius: 999px; background: #7dec19; box-shadow: 0 0 0 2px rgba(125,237,25,.25); }
    .goal-event-away .goal-event-dot { background: #55b8ff; box-shadow: 0 0 0 2px rgba(85,184,255,.25); }
    .match-details summary::-webkit-details-marker { display: none; }
    .match-details[open] summary { color: #d8ff5f; }
    .match-details[open] summary i { transform: rotate(180deg); }
    .results-table-wrap { overflow-x: auto; border: 1px solid rgba(255,255,255,.1); border-radius: 24px; background: rgba(255,255,255,.03); }
    .results-table { border-collapse: separate; border-spacing: 0; }
    .results-table thead { background: rgba(255,255,255,.05); color: rgba(255,255,255,.4); font-size: .7rem; text-transform: uppercase; letter-spacing: .12em; }
    .results-table th, .results-table td { padding: 1rem 1.25rem; white-space: nowrap; }
    .results-table tbody tr + tr td { border-top: 1px solid rgba(255,255,255,.08); }
    .results-table tbody tr:hover { background: rgba(255,255,255,.035); }
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
    .hud-secondary-action { border: 1px solid rgba(85,184,255,.35); border-radius: 14px; background: rgba(8,120,209,.12); color: #9bd7ff; font-weight: 900; transition: transform .2s ease, background .2s ease, border-color .2s ease; }
    .hud-secondary-action:hover { transform: translateY(-2px); border-color: rgba(125,237,25,.55); background: rgba(125,237,25,.12); color: #d8ff5f; }
    .hud-league-card { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 0 1rem; cursor: pointer; color: inherit; text-decoration: none; background: linear-gradient(145deg, rgba(8,39,70,.9), rgba(3,20,37,.86)); border: 1px solid rgba(255,255,255,.1); border-radius: 26px; box-shadow: 0 18px 50px rgba(0,0,0,.2), inset 0 1px rgba(255,255,255,.06); transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease; }
    .league-row-icon { grid-column: 1; grid-row: 1; }
    .league-row > .hud-status { grid-column: 2; grid-row: 1; justify-self: end; }
    .league-row-main, .league-row-progress, .league-row-action { grid-column: 1 / -1; }
    .leagues-list { display: none; }
    .leagues-mobile-table { display: block; }
    .dashboard-table-wrap { overflow-x: auto; border: 1px solid rgba(255,255,255,.1); border-radius: 14px; background: rgba(255,255,255,.02); }
    .dashboard-leagues-table { min-width: 620px; border-collapse: collapse; }
    .dashboard-leagues-table thead { background: rgba(255,255,255,.05); color: rgba(255,255,255,.48); font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; }
    .dashboard-leagues-table th, .dashboard-leagues-table td { padding: 1rem; white-space: nowrap; }
    .dashboard-leagues-table th { font-weight: 800; text-align: left; }
    .dashboard-leagues-table td { color: rgba(255,255,255,.82); }
    .dashboard-leagues-table tbody tr { border-bottom: 1px solid rgba(255,255,255,.1); transition: background .2s ease; }
    .dashboard-leagues-table tbody tr:last-child { border-bottom: 0; }
    .dashboard-leagues-table tbody tr:hover, .dashboard-leagues-table tbody tr:focus-visible { background: rgba(125,237,25,.08); outline: none; }
    .dashboard-league-name { display: inline-flex; min-width: 0; align-items: center; gap: .65rem; }
    .dashboard-league-icon { width: 2.25rem; height: 2.25rem; flex: 0 0 auto; font-size: 1.15rem; }
    .dashboard-ready-meter { min-width: 5rem; margin-top: .35rem; }
    .dashboard-row-arrow { color: #7dec19; font-size: 1.25rem; }
    .hud-league-card::after { content: ''; position: absolute; right: -36px; bottom: -54px; width: 150px; height: 150px; border-radius: 999px; background: rgba(17,151,239,.10); filter: blur(4px); pointer-events: none; }
    .hud-league-card:hover { transform: translateY(-6px); border-color: rgba(125,237,25,.42); box-shadow: 0 25px 70px rgba(0,0,0,.3), 0 0 35px rgba(125,237,25,.08); }
    .hud-card-action { position: relative; z-index: 1; }
    .hud-results details { position: relative; overflow: hidden; border-color: rgba(255,255,255,.1); background: linear-gradient(110deg, rgba(8,39,70,.82), rgba(3,20,37,.78)); transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .hud-results details:hover { transform: translateY(-2px); border-color: rgba(85,184,255,.35); box-shadow: 0 18px 45px rgba(0,0,0,.22); }
    .hud-results details summary { font-weight: 800; }
    .hud-results details summary::-webkit-details-marker { display: none; }
    .hud-results details[open] { border-color: rgba(125,237,25,.35); box-shadow: 0 0 28px rgba(125,237,25,.08); }
    .hud-results [data-winner] { animation: hud-winner-in .7s cubic-bezier(.2,.8,.2,1) both; }
    @keyframes hud-winner-in { from { opacity: 0; transform: translateY(18px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .hud-progress-strip { display: flex; align-items: center; gap: .7rem; border: 1px solid rgba(255,255,255,.09); border-radius: 20px; background: rgba(255,255,255,.035); padding: .75rem; }
    .hud-progress-step { display: grid; grid-template-columns: auto 1fr; column-gap: .55rem; align-items: center; min-width: 0; flex: 1; color: rgba(255,255,255,.35); }
    .hud-progress-step > span { grid-row: span 2; display: grid; place-items: center; width: 2rem; height: 2rem; border: 1px solid rgba(255,255,255,.13); border-radius: 10px; font-size: .65rem; font-weight: 900; }
    .hud-progress-step strong { overflow: hidden; color: inherit; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; text-overflow: ellipsis; white-space: nowrap; }
    .hud-progress-step small { color: rgba(255,255,255,.32); font-size: .65rem; }
    .hud-progress-step.is-current { color: #9bd7ff; }
    .hud-progress-step.is-current > span { border-color: #55b8ff; background: rgba(8,120,209,.2); color: #9bd7ff; box-shadow: 0 0 16px rgba(85,184,255,.2); }
    .hud-progress-step.is-complete { color: #d8ff5f; }
    .hud-progress-step.is-complete > span { border-color: #7dec19; background: #7dec19; color: #071d33; }
    .hud-progress-line { height: 1px; flex: .35; background: linear-gradient(90deg, rgba(125,237,25,.5), rgba(255,255,255,.1)); }
    @media (max-width: 520px) { .hud-progress-strip { gap: .35rem; padding: .55rem; } .hud-progress-step { display: flex; flex-direction: column; text-align: center; gap: .25rem; } .hud-progress-step > span { width: 1.8rem; height: 1.8rem; } .hud-progress-step strong { max-width: 5rem; font-size: .58rem; } .hud-progress-step small { font-size: .56rem; } .hud-progress-line { flex: 1; } }
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
    .legacy-power-cards { display: none; }
    .power-card-dock { position: fixed; right: 0; bottom: 1rem; left: 0; z-index: 40; pointer-events: none; }
    .power-card-dock-inner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: fit-content; margin: 0 auto; padding: .65rem .8rem .75rem; border: 1px solid rgba(255,255,255,.16); border-radius: 24px; background: rgba(3,19,35,.9); box-shadow: 0 18px 55px rgba(0,0,0,.5), inset 0 1px rgba(255,255,255,.08); backdrop-filter: blur(22px); pointer-events: auto; }
    .power-card-dock-list { display: flex; gap: .65rem; overflow-x: auto; }
    .power-card-dock-card { display: grid; min-width: 5.6rem; place-items: center; gap: .15rem; aspect-ratio: .72; padding: .55rem .45rem; border: 1px solid rgba(255,255,255,.28); border-radius: 14px; color: #fff; background: linear-gradient(145deg, rgba(255,255,255,.12), rgba(7,29,51,.88)); box-shadow: 0 6px 18px rgba(0,0,0,.25), inset 0 0 0 2px rgba(255,255,255,.04); transition: transform .25s ease, border-color .25s ease, color .25s ease, box-shadow .25s ease; }
    .power-card-dock-card:hover, .power-card-dock-card:focus-visible { transform: translateY(-8px) rotate(-2deg); border-color: #fff; color: #fff; box-shadow: 0 14px 28px rgba(0,0,0,.38), 0 0 20px rgba(255,255,255,.12); outline: none; }
    .power-card-dock-card i { font-size: 1.65rem; }
    .power-card-dock-card strong { font-size: .72rem; font-weight: 900; }
    .power-card-dock-card small { font-size: .5rem; text-transform: uppercase; letter-spacing: .08em; opacity: .55; }
    .power-card-dock-card.is-activated { border-color: rgba(125,237,25,.75); color: #7dec19; background: linear-gradient(145deg, rgba(125,237,25,.2), rgba(7,29,51,.92)); box-shadow: 0 0 24px rgba(125,237,25,.16), inset 0 0 0 2px rgba(125,237,25,.08); }
    .power-card-dock-card.is-activated:hover, .power-card-dock-card.is-activated:focus-visible { border-color: #7dec19; color: #7dec19; box-shadow: 0 14px 28px rgba(0,0,0,.38), 0 0 28px rgba(125,237,25,.26); }
    .power-card-modal-panel { border: 1px solid rgba(255,255,255,.16); background: linear-gradient(145deg, rgba(8,39,70,.96), rgba(3,20,37,.96)); }
    .power-card-modal-heading { display: flex; align-items: center; gap: .8rem; color: #fff; }
    .power-card-modal-heading i { font-size: 2.5rem; }
    .power-card-modal-heading h3 { font-size: 1.35rem; font-weight: 900; }
    .power-card-modal-heading p { margin-top: .2rem; color: rgba(255,255,255,.5); font-size: .75rem; }
    @keyframes hud-pulse { 50% { opacity: .35; transform: scale(.72); } }
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; } }
    @media (max-width: 639px) { .hud-panel { border-radius: 20px; } .hud-title { letter-spacing: -.045em; } }
    @media (max-width: 639px) { .league-header-actions { width: 100%; justify-content: flex-start; } .league-icon-action { min-height: 2.8rem; } .league-icon-action-label { display: none; } .league-icon-action { width: 3rem; justify-content: center; padding: .3rem; } .league-icon-action-glyph { width: 2.25rem; height: 2.25rem; } .league-status-pill { margin-left: auto; } .league-start-action { padding-inline: .95rem; } }
    .mobile-card-table { width: 100%; }
    .mobile-card-table thead { display: table-header-group; }
    @media (max-width: 720px) {
      .hud-results, .hud-squad { width: 100%; max-width: 100%; overflow: hidden; }
      .hud-results > div:first-child { align-items: flex-start; }
      .hud-results > div:first-child h1, .hud-squad h1 { font-size: clamp(2rem, 10vw, 3rem); line-height: 1; }
      .hud-results nav, .hud-squad nav { width: 100%; overflow-x: auto; white-space: nowrap; }
      .hud-results nav a, .hud-squad nav a { flex: 1 0 auto; text-align: center; }
      .hud-results .overflow-x-auto, .hud-squad .overflow-x-auto { overflow-x: visible; }
      .hud-results details summary { align-items: flex-start; padding: 1rem; }
      .hud-results details summary > span { max-width: 100%; }
      .hud-results details summary > span:nth-child(2) { order: 3; width: 100%; font-size: .9rem; line-height: 1.5; }
      .hud-results details summary > span:nth-child(3) { margin-left: auto; }
      .hud-squad section.glass-panel { padding: 1rem; }
      .hud-squad #pitch { min-height: 390px; padding: .4rem; border-radius: 22px; }
      .hud-squad #pitch .formation-slot { width: 4.6rem; padding: .35rem .2rem; border-radius: 12px; font-size: .58rem; }
      .hud-squad #pitch .formation-slot > div { width: 2rem; height: 2rem; }
      .hud-squad #pitch > .relative { min-height: 370px; gap: .15rem; }
      .hud-squad #slots > div { gap: .35rem; }
      .hud-squad #slots > div > button { border-radius: 12px; padding: .55rem .25rem; }
      .hud-squad .grid-cols-2 { grid-template-columns: 1fr; }
      .hud-squad [data-power-card] select { min-width: 0; font-size: .7rem; }
      .hud-squad aside { width: 100%; }
      .power-card-dock-inner { padding: .45rem; border-radius: 18px; }
      .power-card-dock-list { width: 100%; justify-content: center; }
      .power-card-dock-card { min-width: 5.1rem; }
      .dashboard-table-wrap { border-radius: 12px; }
      .dashboard-leagues-table { min-width: 620px; }
      .dashboard-leagues-table th, .dashboard-leagues-table td { padding: .8rem .75rem; font-size: .72rem; }
      .dashboard-leagues-table .hud-status { padding: .35rem .55rem; font-size: .58rem; letter-spacing: .05em; }
      .dashboard-league-name { gap: .4rem; }
      .dashboard-league-icon { width: 2rem; height: 2rem; font-size: 1rem; }
      .dashboard-league-name strong { max-width: 7rem; }
      .league-row { grid-template-columns: auto minmax(0, 1fr) auto; gap: .65rem; border: 0; border-bottom: 1px solid rgba(255,255,255,.08); border-radius: 0; padding: .75rem; box-shadow: none; }
      .league-row:last-child { border-bottom: 0; }
      .league-row-icon { grid-column: 1; grid-row: 1 / span 2; width: 2.75rem; height: 2.75rem; font-size: 1.25rem; }
      .league-row > .hud-status { grid-column: 3; grid-row: 1; max-width: 7rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .league-row-main { grid-column: 2; grid-row: 1 / span 2; }
      .league-row-main h3 { font-size: .95rem; line-height: 1.25; }
      .league-row-main > div:first-of-type { margin-top: .35rem; font-size: .65rem; }
      .league-row-progress { margin-top: .6rem; }
      .league-row-progress > div:first-child { font-size: .62rem; }
      .league-row-action { grid-column: 3; grid-row: 2; margin-top: .35rem; padding: .45rem .55rem; font-size: .65rem; white-space: nowrap; }
      .results-table-wrap { overflow-x: auto; border-radius: 20px; }
      .hud-results table.results-table { display: table; width: 100%; table-layout: auto; border-spacing: 0; }
      .hud-results table.standings-table { min-width: 620px !important; }
      .hud-results table.scorers-table { min-width: 560px !important; }
      .hud-results table.results-table thead { display: table-header-group; }
      .hud-results table.results-table tbody { display: table-row-group; }
      .hud-results table.results-table tr { display: table-row; border: 0; border-radius: 0; background: transparent; padding: 0; }
      .hud-results table.results-table th, .hud-results table.results-table td { display: table-cell; min-height: 0; padding: .75rem .85rem; white-space: nowrap; }
      .hud-results table.results-table td::before { content: none; }
    }
    @media (max-width: 420px) {
      header nav { padding-left: .75rem; padding-right: .75rem; }
      header nav > a { gap: .45rem; }
      header nav > a span:last-child { display: none; }
      header nav > div > a span { display: none; }
      .hud-results, .hud-squad { padding-left: .75rem; padding-right: .75rem; }
      .hud-results .grid-cols-2, .hud-results .sm\:grid-cols-2 { grid-template-columns: 1fr; }
      .hud-squad #pitch .formation-slot { width: 4rem; }
    }
  </style>
</head>
<body class="min-h-screen overflow-x-hidden selection:bg-uno-lime selection:text-uno-navy">
  <div class="stadium-glow pitch-grid hud-shell min-h-screen">
    <x-navigation />
    <x-toast.success />
    <x-toast.error />

    <div data-spa-content data-page-title="@yield('title', 'Amadara UNO | Football League')">
      @yield('content')
    </div>

    <x-footer />
  </div>
</body>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-9MSQVCNQ6L"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-9MSQVCNQ6L');
</script>
</html>
@endif
