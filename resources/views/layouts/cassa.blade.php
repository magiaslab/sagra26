<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cassa') — {{ $impostazioni->intestazione_nome ?? 'Cassa' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Anteprima A4 in modale: regole mm (non Tailwind) --}}
    <link rel="stylesheet" href="/css/print.css">
    @livewireStyles
    @stack('head')
</head>
<body class="h-dvh overflow-hidden bg-sagra-bg">
@yield('content')
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
