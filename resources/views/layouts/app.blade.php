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
<body class="min-h-screen bg-sagra-bg">
<div class="flex min-h-screen flex-col">
    <header class="no-print border-b border-sagra-dark/20 bg-sagra">
        <div class="mx-auto flex h-14 max-w-[1400px] items-center justify-between gap-6 px-4">
            <a href="{{ route('home', absolute: false) }}" class="truncate text-sm font-semibold tracking-wide text-white no-underline hover:text-white">
                {{ $impostazioni->intestazione_nome ?? 'Sagra' }} {{ $impostazioni->intestazione_anno ?? '' }}
            </a>
            <nav class="flex h-full items-stretch gap-1" aria-label="Principale">
                @foreach ([
                    ['Home', 'home', request()->routeIs('home')],
                    ['Cassa', 'cassa', request()->routeIs('cassa*')],
                    ['Riepilogo', 'riepilogo', request()->routeIs('riepilogo')],
                    ['Gestione', 'gestione.dashboard', request()->is('gestione*')],
                ] as [$label, $route, $active])
                    <a href="{{ route($route, absolute: false) }}"
                       @class([
                           'inline-flex items-center border-b-2 px-3 text-sm font-medium no-underline transition',
                           'border-white text-white' => $active,
                           'border-transparent text-white/75 hover:border-white/40 hover:text-white' => ! $active,
                       ])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>
    <main class="mx-auto w-full max-w-[1400px] flex-1 px-4 py-6 @yield('main_class')">
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
            window.sagraToast?.(data.message ?? data[0] ?? '', data.type ?? 'ok', data.timeout ?? 3600);
        });
    });
</script>
@stack('scripts')
</body>
</html>
