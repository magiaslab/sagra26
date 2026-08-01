<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sagra26') — {{ $impostazioni->intestazione_nome ?? 'Cassa' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen">
<div class="flex min-h-screen flex-col">
    <header class="no-print flex items-center gap-4 bg-sagra px-4 py-3 text-white">
        <div class="text-lg font-extrabold tracking-wide">
            {{ $impostazioni->intestazione_nome ?? 'Sagra' }} {{ $impostazioni->intestazione_anno ?? '' }}
        </div>
        <nav class="ml-auto flex flex-wrap gap-2">
            <a href="{{ route('home', absolute: false) }}"
               @class([
                   'inline-flex min-h-9 items-center rounded-md border border-white/45 px-3 py-1.5 text-sm font-semibold text-white no-underline hover:bg-white/15',
                   'bg-white/15' => request()->routeIs('home'),
               ])>Home</a>
            <a href="{{ route('cassa', absolute: false) }}"
               @class([
                   'inline-flex min-h-9 items-center rounded-md border border-white/45 px-3 py-1.5 text-sm font-semibold text-white no-underline hover:bg-white/15',
                   'bg-white/15' => request()->routeIs('cassa*'),
               ])>Cassa</a>
            <a href="{{ route('riepilogo', absolute: false) }}"
               @class([
                   'inline-flex min-h-9 items-center rounded-md border border-white/45 px-3 py-1.5 text-sm font-semibold text-white no-underline hover:bg-white/15',
                   'bg-white/15' => request()->routeIs('riepilogo'),
               ])>Riepilogo</a>
            <a href="{{ route('gestione.dashboard', absolute: false) }}"
               @class([
                   'inline-flex min-h-9 items-center rounded-md border border-white/45 px-3 py-1.5 text-sm font-semibold text-white no-underline hover:bg-white/15',
                   'bg-white/15' => request()->is('gestione*'),
               ])>Gestione</a>
        </nav>
    </header>
    <main class="mx-auto w-full max-w-[1400px] flex-1 p-4 @yield('main_class')">
        @yield('content')
        {{ $slot ?? '' }}
    </main>
</div>
<x-ui.toast-host />
<x-ui.flash-toasts />
@livewireScripts
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('toast', (payload) => {
            const data = Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
            window.sagraToast?.(data.message ?? data[0] ?? '', data.type ?? 'ok', data.timeout ?? 4200);
        });
    });
</script>
@stack('scripts')
</body>
</html>
