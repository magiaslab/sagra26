<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cassa') — {{ $impostazioni->intestazione_nome ?? 'Cassa' }}</title>
    <link rel="stylesheet" href="/css/app.css">
    @livewireStyles
    @stack('head')
</head>
<body class="cassa-body">
@yield('content')
@livewireScripts
@stack('scripts')
</body>
</html>
