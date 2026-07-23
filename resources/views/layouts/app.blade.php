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
  <meta name="description" content="@yield('description', 'Amadara UNO Football League â€” every match, every moment.')">
  <title>@yield('title', 'Amadara UNO | Football League')</title>
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo.png') }}">

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
