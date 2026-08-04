@php
    $links = [
        [
            'label' => 'Dashboard',
            'route' => 'gestione.dashboard',
            'match' => 'gestione.dashboard',
            'icon' => 'home',
        ],
        [
            'label' => 'Serate',
            'route' => 'gestione.serate',
            'match' => 'gestione.serate',
            'icon' => 'calendar',
        ],
        [
            'label' => 'Menù',
            'route' => 'gestione.menu',
            'match' => 'gestione.menu*',
            'icon' => 'menu',
        ],
        [
            'label' => 'Chiusura',
            'route' => 'gestione.chiusura',
            'match' => 'gestione.chiusura',
            'icon' => 'cash',
        ],
        [
            'label' => 'Report',
            'route' => 'gestione.report',
            'match' => 'gestione.report',
            'icon' => 'chart',
        ],
        [
            'label' => 'Impostazioni',
            'route' => 'gestione.impostazioni',
            'match' => 'gestione.impostazioni',
            'icon' => 'cog',
        ],
        [
            'label' => 'Stato',
            'route' => 'gestione.stato',
            'match' => 'gestione.stato',
            'icon' => 'status',
        ],
        [
            'label' => 'Guida',
            'route' => 'gestione.guida',
            'match' => 'gestione.guida',
            'icon' => 'book',
        ],
    ];
@endphp
<nav class="no-print mb-6" aria-label="Sezioni gestione">
    <div class="flex flex-wrap gap-2 rounded-lg bg-white p-2 shadow-sm ring-1 ring-sagra-line/80">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['match']); @endphp
            <a
                href="{{ route($link['route'], absolute: false) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold no-underline transition',
                    'bg-sagra text-white shadow-sm hover:bg-sagra-dark hover:text-white' => $active,
                    'bg-sagra-softer text-sagra-ink ring-1 ring-inset ring-sagra-line/70 hover:bg-white hover:text-sagra-ink' => ! $active,
                ])
            >
                <span class="inline-flex size-4 shrink-0 items-center justify-center" aria-hidden="true">
                    @switch($link['icon'])
                        @case('home')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z"/></svg>
                            @break
                        @case('calendar')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 11h18"/></svg>
                            @break
                        @case('menu')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                            @break
                        @case('cash')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path stroke-linecap="round" d="M6 12h.01M18 12h.01"/></svg>
                            @break
                        @case('chart')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M4 19h16M8 17V11M12 17V7M16 17v-4"/></svg>
                            @break
                        @case('cog')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
                            @break
                        @case('status')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3M12 18v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M3 12h3M18 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/><circle cx="12" cy="12" r="3.5"/></svg>
                            @break
                        @case('book')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5V5.5Z"/><path stroke-linecap="round" d="M8 7h8M8 11h8"/></svg>
                            @break
                    @endswitch
                </span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
