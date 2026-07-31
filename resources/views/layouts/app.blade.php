<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sagra26') — {{ $impostazioni->intestazione_nome ?? 'Cassa' }}</title>
    <link rel="stylesheet" href="/css/app.css">
    @livewireStyles
    @stack('head')
</head>
<body>
<div class="app-shell">
    <header class="topbar no-print">
        <div class="brand">{{ $impostazioni->intestazione_nome ?? 'Sagra' }} {{ $impostazioni->intestazione_anno ?? '' }}</div>
        <nav>
            <a href="{{ route('home', absolute: false) }}" @class(['active' => request()->routeIs('home')])>Home</a>
            <a href="{{ route('cassa', absolute: false) }}" @class(['active' => request()->routeIs('cassa*')])>Cassa</a>
            <a href="{{ route('riepilogo', absolute: false) }}" @class(['active' => request()->routeIs('riepilogo')])>Riepilogo</a>
            <a href="{{ route('gestione.dashboard', absolute: false) }}" @class(['active' => request()->is('gestione*')])>Gestione</a>
        </nav>
    </header>
    <main class="main @yield('main_class')">
        @if (session('status'))
            <div class="alert alert-ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @yield('content')
        {{ $slot ?? '' }}
    </main>
</div>
@livewireScripts
@stack('scripts')
</body>
</html>
