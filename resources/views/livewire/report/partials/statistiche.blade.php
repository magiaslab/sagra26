@php
    $bar = function (int $v, int $max, int $width = 20): string {
        $filled = $max > 0 ? (int) round($v / $max * $width) : 0;
        $filled = max(0, min($width, $filled));
        return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
    };
@endphp
<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <h2 class="mt-0 text-xl font-semibold text-sagra-ink">{{ $impostazioni->intestazione_nome }} — Statistiche {{ $completo ? '(tutta l’edizione)' : 'serata '.$serata->data->format('d/m/Y') }}</h2>
    <div class="my-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="report-kpi rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Coperti</div>
            <div class="text-2xl font-bold tabular-nums text-sagra-dark">{{ $dati['coperti'] }}</div>
        </div>
        <div class="report-kpi rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Incasso</div>
            <div class="text-2xl font-bold tabular-nums text-sagra-dark">{{ number_format($dati['incasso'], 2, ',', '.') }} €</div>
        </div>
        <div class="report-kpi rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Media coperti/sera</div>
            <div class="text-2xl font-bold tabular-nums text-sagra-dark">{{ $dati['mediaCoperti'] }}</div>
        </div>
    </div>

    <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Coperti per serata</h3>
    @foreach ($dati['perSerata'] as $r)
        <div class="font-mono text-sm text-sagra-ink">{{ $r['data'] }} {{ $bar($r['coperti'], $dati['maxCoperti']) }} {{ $r['coperti'] }}</div>
    @endforeach

    <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Flusso orario (n° comande)</h3>
    @forelse ($dati['ore'] as $h => $n)
        <div class="font-mono text-sm text-sagra-ink">{{ $h }}:00 {{ $bar($n, $dati['maxOre']) }} {{ $n }}</div>
    @empty
        <p class="text-sm text-sagra-muted">Nessun dato.</p>
    @endforelse

    <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Piatti più venduti</h3>
    <ol class="list-decimal pl-5 text-sm text-sagra-ink">
        @foreach ($dati['top'] as $t)
            <li>{{ $t->menuItem?->nome ?? '—' }} — {{ $t->qta }}</li>
        @endforeach
    </ol>

    @if ($dati['record'])
        <p class="mt-3 text-sm text-sagra-ink">Serata record: <strong>{{ $dati['record']['data'] }}</strong> — {{ number_format($dati['record']['incasso'], 2, ',', '.') }} €</p>
    @endif
</div>
