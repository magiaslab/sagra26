@php
    $bar = function (int $v, int $max, int $width = 20): string {
        $filled = $max > 0 ? (int) round($v / $max * $width) : 0;
        $filled = max(0, min($width, $filled));
        return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
    };
@endphp
<div class="panel">
    <h2>{{ $impostazioni->intestazione_nome }} — Statistiche {{ $completo ? '(completo)' : 'fino al '.$serata->data->format('d/m/Y') }}</h2>
    <div class="grid-3" style="margin:1rem 0">
        <div class="kpi"><div class="lbl">Coperti</div><div class="val">{{ $dati['coperti'] }}</div></div>
        <div class="kpi"><div class="lbl">Incasso</div><div class="val">{{ number_format($dati['incasso'], 2, ',', '.') }} €</div></div>
        <div class="kpi"><div class="lbl">Media coperti/sera</div><div class="val">{{ $dati['mediaCoperti'] }}</div></div>
    </div>

    <h3>Coperti per serata</h3>
    @foreach ($dati['perSerata'] as $r)
        <div class="bar-text">{{ $r['data'] }} {{ $bar($r['coperti'], $dati['maxCoperti']) }} {{ $r['coperti'] }}</div>
    @endforeach

    <h3 style="margin-top:1rem">Flusso orario (n° comande)</h3>
    @forelse ($dati['ore'] as $h => $n)
        <div class="bar-text">{{ $h }}:00 {{ $bar($n, $dati['maxOre']) }} {{ $n }}</div>
    @empty
        <p>Nessun dato.</p>
    @endforelse

    <h3 style="margin-top:1rem">Piatti più venduti</h3>
    <ol>
        @foreach ($dati['top'] as $t)
            <li>{{ $t->menuItem->nome }} — {{ $t->qta }}</li>
        @endforeach
    </ol>

    @if ($dati['record'])
        <p>Serata record: <strong>{{ $dati['record']['data'] }}</strong> — {{ number_format($dati['record']['incasso'], 2, ',', '.') }} €</p>
    @endif
</div>
