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
            'label' => 'Sospesi',
            'route' => 'gestione.sospesi',
            'match' => 'gestione.sospesi',
            'icon' => 'sospesi',
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
                <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center" aria-hidden="true">
                    @switch($link['icon'])
                        @case('home')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8.5Z"/></svg>
                            @break
                        @case('calendar')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 11h18"/></svg>
                            @break
                        @case('menu')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                            @break
                        @case('cash')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path stroke-linecap="round" d="M6 12h.01M18 12h.01"/></svg>
                            @break
                        @case('sospesi')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5"/><path stroke-linecap="round" d="M4 4h16v16H4z"/></svg>
                            @break
                        @case('chart')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M4 19h16M8 17V11M12 17V7M16 17v-4"/></svg>
                            @break
                        @case('cog')
                            {{-- Impostazioni: ingranaggio semplice --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v2.5M12 19.5V22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M2 12h2.5M19.5 12H22M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>
                            @break
                        @case('status')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            @break
                        @case('book')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path stroke-linecap="round" d="M8 7h8M8 11h6"/></svg>
                            @break
                    @endswitch
                </span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
