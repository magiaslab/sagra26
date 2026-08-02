@php
    $links = [
        ['label' => 'Dashboard', 'route' => 'gestione.dashboard', 'match' => 'gestione.dashboard'],
        ['label' => 'Serate', 'route' => 'gestione.serate', 'match' => 'gestione.serate'],
        ['label' => 'Menù', 'route' => 'gestione.menu', 'match' => 'gestione.menu*'],
        ['label' => 'Chiusura', 'route' => 'gestione.chiusura', 'match' => 'gestione.chiusura'],
        ['label' => 'Report', 'route' => 'gestione.report', 'match' => 'gestione.report'],
        ['label' => 'Impostazioni', 'route' => 'gestione.impostazioni', 'match' => 'gestione.impostazioni'],
        ['label' => 'Stato', 'route' => 'gestione.stato', 'match' => 'gestione.stato'],
    ];
@endphp
<nav class="no-print mb-6 flex gap-6 border-b border-sagra-line" aria-label="Sezioni gestione">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route'], absolute: false) }}"
            @class([
                'inline-flex items-center border-b-2 px-0.5 py-2.5 text-sm font-medium no-underline',
                'border-sagra text-sagra-ink' => request()->routeIs($link['match']),
                'border-transparent text-sagra-muted hover:border-sagra-line hover:text-sagra-ink' => ! request()->routeIs($link['match']),
            ])
        >{{ $link['label'] }}</a>
    @endforeach
</nav>
