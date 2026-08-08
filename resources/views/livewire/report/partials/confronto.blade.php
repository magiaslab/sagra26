@php
    $righe = $dati['righe'] ?? [];
    $totale = $dati['totale'] ?? null;
    $media = $dati['media'] ?? null;
    $delta = $dati['delta_estremi'] ?? null;
    $piatti = $dati['piatti'] ?? [];
@endphp
<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <h2 class="mt-0 text-xl font-semibold text-sagra-ink">
        {{ $impostazioni->intestazione_nome }} — Confronto serate
    </h2>
    <p class="mt-1 text-sm text-sagra-muted">
        Range {{ $dati['label_da'] ?? '—' }} → {{ $dati['label_a'] ?? '—' }}
        · {{ count($righe) }} {{ count($righe) === 1 ? 'serata' : 'serate' }}
    </p>

    @if ($totale && $media)
        <div class="my-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Tot. coperti</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ $totale['coperti'] }}</div>
                <div class="text-xs text-sagra-muted">media {{ $media['coperti'] }}/sera</div>
            </div>
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Tot. comande</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ $totale['comande'] }}</div>
                <div class="text-xs text-sagra-muted">media {{ $media['comande'] }}/sera</div>
            </div>
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Tot. incasso</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ number_format($totale['incasso'], 2, ',', '.') }} €</div>
                <div class="text-xs text-sagra-muted">media {{ number_format($media['incasso'], 2, ',', '.') }} €</div>
            </div>
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Contante / POS</div>
                <div class="text-sm font-bold tabular-nums text-sagra-dark">
                    {{ number_format($totale['contante'], 2, ',', '.') }} /
                    {{ number_format($totale['pos'], 2, ',', '.') }}
                </div>
                @if ($delta)
                    <div class="mt-1 text-xs text-sagra-muted">
                        Δ ultima−prima: cop. {{ $delta['coperti'] > 0 ? '+'.$delta['coperti'] : $delta['coperti'] }}
                        · {{ number_format($delta['incasso'], 2, ',', '.') }} €
                    </div>
                @endif
            </div>
        </div>
    @endif

    <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Dettaglio per serata</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sagra-line text-sm">
            <thead>
                <tr class="bg-sagra-softer text-left">
                    <th class="px-3 py-2">Data</th>
                    <th class="px-3 py-2 text-right">Comande</th>
                    <th class="px-3 py-2 text-right">Coperti</th>
                    <th class="px-3 py-2 text-right">Incasso</th>
                    <th class="px-3 py-2 text-right">Contante</th>
                    <th class="px-3 py-2 text-right">POS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sagra-line">
                @forelse ($righe as $r)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $r['label'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $r['comande'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $r['coperti'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ number_format($r['incasso'], 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['contante'], 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['pos'], 2, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-4 text-sagra-muted">Nessuna serata nel range.</td></tr>
                @endforelse
            </tbody>
            @if ($totale && count($righe) > 1)
                <tfoot>
                    <tr class="bg-sagra-softer font-semibold">
                        <td class="px-3 py-2">Totale</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $totale['comande'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $totale['coperti'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totale['incasso'], 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totale['contante'], 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totale['pos'], 2, ',', '.') }} €</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <h3 class="mb-2 mt-5 text-base font-semibold text-sagra-ink">Piatti più venduti nel range</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sagra-line text-sm">
            <thead>
                <tr class="bg-sagra-softer text-left">
                    <th class="px-3 py-2">Piatto</th>
                    <th class="px-3 py-2 text-right">Q.tà</th>
                    <th class="px-3 py-2 text-right">Incasso</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sagra-line">
                @forelse ($piatti as $p)
                    <tr>
                        <td class="px-3 py-2">{{ $p['nome'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $p['qta'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($p['incasso'], 2, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-3 py-4 text-sagra-muted">Nessun piatto nel range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
