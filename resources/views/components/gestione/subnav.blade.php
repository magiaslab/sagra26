@php
    $links = [
        ['label' => 'Dashboard', 'route' => 'gestione.dashboard', 'match' => 'gestione.dashboard'],
        ['label' => 'Serate', 'route' => 'gestione.serate', 'match' => 'gestione.serate'],
        ['label' => 'Menù', 'route' => 'gestione.menu', 'match' => 'gestione.menu*'],
        ['label' => 'Chiusura', 'route' => 'gestione.chiusura', 'match' => 'gestione.chiusura'],
        ['label' => 'Report', 'route' => 'gestione.report', 'match' => 'gestione.report'],
        ['label' => 'Impostazioni', 'route' => 'gestione.impostazioni', 'match' => 'gestione.impostazioni'],
    ];
@endphp
<nav class="gestione-subnav no-print" aria-label="Sezioni gestione">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route'], absolute: false) }}"
            @class(['is-active' => request()->routeIs($link['match'])])
        >{{ $link['label'] }}</a>
    @endforeach
</nav>
