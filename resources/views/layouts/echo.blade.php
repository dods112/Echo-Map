<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Echo-Map')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink: #172A33;
            --mist: #EDF1F2;
            --surface: #FFFFFF;
            --line: #D5DEE1;
            --muted: #5B6E76;
            --signal: #1F6F78;
            --signal-dark: #17565D;
            --display: 'Bricolage Grotesque', 'Figtree', system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            display: flex; flex-direction: column;
            font-family: 'Figtree', system-ui, sans-serif;
            color: var(--ink); background: var(--mist);
            font-size: 15px; line-height: 1.5;
        }
        :focus-visible { outline: 2px solid var(--signal); outline-offset: 2px; }

        /* Top bar */
        .bar {
            display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
            padding: 12px 22px; background: var(--surface);
            border-bottom: 1px solid var(--line);
        }
        .brand {
            display: flex; align-items: center; gap: 8px;
            font-family: var(--display); font-weight: 700; font-size: 20px;
            color: var(--ink); text-decoration: none;
        }
        .brand svg { color: var(--signal); }
        .bar-nav { display: flex; align-items: center; gap: 14px; margin-left: auto; font-size: 14px; }
        .bar-nav a, .bar-nav button {
            color: var(--ink); background: none; border: 0; font: inherit; cursor: pointer;
            text-decoration: underline; text-underline-offset: 3px; padding: 0;
        }
        .bar-nav .who { color: var(--muted); margin-right: 6px; }

        /* Page body */
        .page { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 32px 34px;
            width: 100%; max-width: 440px;
            box-shadow: 0 12px 32px rgba(17,42,51,.08);
        }
        .card.wide { max-width: 720px; }

        .card h1 {
            margin: 0 0 8px;
            font-family: var(--display); font-weight: 700; font-size: 24px; letter-spacing: -.01em;
        }
        .card .sub { color: var(--muted); font-size: 14px; margin: 0 0 22px; }

        /* Form controls */
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px 12px;
            border: 1px solid var(--line); border-radius: 9px;
            font: inherit; font-size: 15px; color: var(--ink);
            background: #fff; transition: border-color .15s ease, box-shadow .15s ease;
        }
        input:focus { outline: none; border-color: var(--signal); box-shadow: 0 0 0 3px rgba(31,111,120,.15); }
        .field { margin-bottom: 16px; }
        .error { color: #8a1f1f; font-size: 13px; margin-top: 4px; }

        .row { display: flex; align-items: center; justify-content: space-between; margin: 8px 0 20px; }
        .row label { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; color: var(--ink); margin: 0; }
        .row a { color: var(--signal); text-decoration: none; font-size: 14px; }
        .row a:hover { text-decoration: underline; text-underline-offset: 3px; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 11px 16px;
            border: 0; border-radius: 10px;
            background: var(--signal); color: #fff;
            font: inherit; font-size: 15px; font-weight: 500;
            cursor: pointer;
            transition: background .15s ease, transform .1s ease;
        }
        .btn:hover { background: var(--signal-dark); }
        .btn:active { transform: translateY(1px); }
        .btn.ghost { background: transparent; color: var(--ink); border: 1px solid var(--line); }
        .btn.ghost:hover { background: var(--mist); }
        .btn + .btn { margin-top: 10px; }

        .meta { text-align: center; margin-top: 20px; font-size: 14px; color: var(--muted); }
        .meta a { color: var(--signal); text-decoration: none; }
        .meta a:hover { text-decoration: underline; text-underline-offset: 3px; }

        .flash { padding: 10px 14px; border-radius: 9px; margin-bottom: 18px; font-size: 14px; }
        .flash.ok  { background:#e7f5ec; color:#1b6b3a; }
        .flash.err { background:#fdecec; color:#8a1f1f; }

        .tile-row { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-top: 8px; }
        .tile {
            display: block; padding: 16px 18px;
            background: #fff; border: 1px solid var(--line); border-radius: 12px;
            text-decoration: none; color: var(--ink);
            transition: transform .12s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .tile:hover { transform: translateY(-1px); border-color: var(--signal); box-shadow: 0 8px 20px rgba(31,111,120,.08); }
        .tile h3 { margin: 0 0 4px; font-family: var(--display); font-size: 16px; }
        .tile p  { margin: 0; color: var(--muted); font-size: 13px; }
    </style>
</head>
<body>
    <header class="bar">
        <a class="brand" href="{{ url('/map') }}">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <circle cx="12" cy="12" r="2.6" fill="currentColor" stroke="none"/>
                <circle cx="12" cy="12" r="6.2" stroke-width="1.6"/>
                <circle cx="12" cy="12" r="10" stroke-width="1.2" opacity=".5"/>
            </svg>
            Echo-Map
        </a>
        <nav class="bar-nav">
            @auth
                <span class="who">{{ auth()->user()->name }}</span>
                <a href="{{ url('/map') }}">Map</a>
                @unless (auth()->user()->is_admin)
                    <a href="{{ url('/friends') }}">Friends</a>
                @endunless
                <a href="{{ route('dashboard') }}">Dashboard</a>
                @if (auth()->user()->is_admin)
                    <a href="{{ url('/admin/reports') }}">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>

    <main class="page">
        <div class="card {{ $wide ?? '' }}">
            @if (session('status'))
                <div class="flash ok">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</body>
</html>