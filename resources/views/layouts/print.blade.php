<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Stampa')</title>
    <link rel="stylesheet" href="/css/print.css">
    @stack('head')
</head>
<body>
@yield('content')
@stack('scripts')
</body>
</html>
