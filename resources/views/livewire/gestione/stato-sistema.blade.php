@php
    $overall = $report['overall'] ?? 'warn';
    $overallLabel = match ($overall) {
        'ok' => 'Tutto funzionante',
        'danger' => 'Problemi critici',
        default => 'Attenzione',
    };
    $overallType = match ($overall) {
        'ok' => 'ok',
        'danger' => 'danger',
        default => 'warn',
    };
    $statusDot = [
        'ok' => 'bg-sagra',
        'warn' => 'bg-sagra-warn',
        'danger' => 'bg-sagra-danger',
    ];
    $statusText = [
        'ok' => 'text-sagra-dark',
        'warn' => 'text-sagra-warn',
        'danger' => 'text-sagra-danger',
    ];
@endphp
<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Stato sistema"
        subtitle="Verifica rapida di database, backup e spazio disco"
    >
        <x-slot:actions>
            <button
                type="button"
                class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark"
                wire:click="aggiorna"
            >Aggiorna</button>
        </x-slot:actions>
    </x-gestione.page-header>

    <x-ui.alert :type="$overallType">
        <span class="font-semibold">{{ $overallLabel }}</span>
        @if (! empty($report['checked_at']))
            <span class="font-normal opacity-80"> · controllato {{ \Illuminate\Support\Carbon::parse($report['checked_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</span>
        @endif
    </x-ui.alert>

    <div class="space-y-3">
        @foreach ($report['checks'] ?? [] as $check)
            @php
                $st = $check['status'] ?? 'warn';
            @endphp
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80" wire:key="check-{{ $check['key'] }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full {{ $statusDot[$st] ?? $statusDot['warn'] }}"></span>
                            <h2 class="m-0 text-lg font-semibold text-sagra-ink">{{ $check['label'] }}</h2>
                            <span class="text-xs font-semibold uppercase tracking-wide {{ $statusText[$st] ?? $statusText['warn'] }}">{{ $st }}</span>
                        </div>
                        <p class="mt-1 mb-0 text-sm text-sagra-ink">{{ $check['summary'] }}</p>
                    </div>
                </div>

                @if (($check['key'] ?? '') === 'database')
                    <dl class="mt-3 grid grid-cols-1 gap-1 text-sm text-sagra-muted sm:grid-cols-2">
                        <div><dt class="inline font-medium text-sagra-ink">Driver:</dt> {{ $check['detail']['driver'] ?? '—' }}</div>
                        <div class="min-w-0 break-all"><dt class="inline font-medium text-sagra-ink">File:</dt> {{ $check['detail']['path'] ?? '—' }}</div>
                    </dl>
                @elseif (($check['key'] ?? '') === 'backup')
                    <dl class="mt-3 grid grid-cols-1 gap-1 text-sm text-sagra-muted sm:grid-cols-2">
                        <div><dt class="inline font-medium text-sagra-ink">Directory:</dt> {{ $check['detail']['directory'] ?? '—' }}</div>
                        <div><dt class="inline font-medium text-sagra-ink">File:</dt> {{ $check['detail']['count'] ?? 0 }}</div>
                        <div><dt class="inline font-medium text-sagra-ink">Ultimo:</dt> {{ $check['detail']['latest'] ?? '—' }}</div>
                        <div><dt class="inline font-medium text-sagra-ink">Script:</dt> {{ ($check['detail']['script_exists'] ?? false) ? 'presente' : 'mancante' }}</div>
                    </dl>
                @elseif (($check['key'] ?? '') === 'app')
                    <dl class="mt-3 grid grid-cols-1 gap-1 text-sm text-sagra-muted sm:grid-cols-2">
                        <div><dt class="inline font-medium text-sagra-ink">Env:</dt> {{ $check['detail']['app_env'] ?? '—' }}</div>
                        <div><dt class="inline font-medium text-sagra-ink">Laravel:</dt> {{ $check['detail']['laravel'] ?? '—' }}</div>
                        <div><dt class="inline font-medium text-sagra-ink">PHP:</dt> {{ $check['detail']['php'] ?? '—' }}</div>
                        <div><dt class="inline font-medium text-sagra-ink">Health:</dt> <a class="text-sagra underline" href="{{ $check['detail']['health_url'] ?? '/up' }}" target="_blank" rel="noopener">/up</a></div>
                    </dl>
                @elseif (($check['key'] ?? '') === 'disk')
                    <dl class="mt-3 grid grid-cols-1 gap-1 text-sm text-sagra-muted sm:grid-cols-2">
                        <div><dt class="inline font-medium text-sagra-ink">Path:</dt> {{ $check['detail']['path'] ?? '—' }}</div>
                    </dl>
                @endif
            </div>
        @endforeach
    </div>
</div>
