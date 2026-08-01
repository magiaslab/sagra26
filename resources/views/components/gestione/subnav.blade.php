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
<nav class="no-print mb-4 flex flex-wrap gap-1 border-b border-sagra-line pb-3" aria-label="Sezioni gestione">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route'], absolute: false) }}"
            @class([
                'inline-flex min-h-9 items-center rounded-full px-3.5 py-1.5 text-sm font-bold no-underline transition',
                'bg-sagra text-white' => request()->routeIs($link['match']),
                'text-sagra-muted hover:bg-sagra/10 hover:text-sagra-ink' => ! request()->routeIs($link['match']),
            ])
        >{{ $link['label'] }}</a>
    @endforeach
</nav>
