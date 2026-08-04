@php
    $a = $dati['a'] ?? null;
    $b = $dati['b'] ?? null;
    $piatti = $dati['piatti'] ?? [];
@endphp
<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <h2 class="mt-0 text-xl font-semibold text-sagra-ink">
        {{ $impostazioni->intestazione_nome }} — Confronto serate
    </h2>

    <div class="my-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="report-card rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Serata A (riferimento)</div>
            <div class="text-lg font-bold text-sagra-dark">{{ $a['label'] ?? '—' }}</div>
            @if ($a)
                <ul class="mt-2 space-y-1 text-sm text-sagra-ink">
                    <li>Comande: <strong class="tabular-nums">{{ $a['comande'] }}</strong></li>
                    <li>Coperti: <strong class="tabular-nums">{{ $a['coperti'] }}</strong></li>
                    <li>Incasso: <strong class="tabular-nums">{{ number_format($a['incasso'], 2, ',', '.') }} €</strong></li>
                    <li>Contante: {{ number_format($a['contante'], 2, ',', '.') }} € · POS: {{ number_format($a['pos'], 2, ',', '.') }} €</li>
                </ul>
            @endif
        </div>
        <div class="report-card rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Serata B (confronto)</div>
            <div class="text-lg font-bold text-sagra-dark">{{ $b['label'] ?? '— nessuna' }}</div>
            @if ($b)
                <ul class="mt-2 space-y-1 text-sm text-sagra-ink">
                    <li>Comande: <strong class="tabular-nums">{{ $b['comande'] }}</strong></li>
                    <li>Coperti: <strong class="tabular-nums">{{ $b['coperti'] }}</strong></li>
                    <li>Incasso: <strong class="tabular-nums">{{ number_format($b['incasso'], 2, ',', '.') }} €</strong></li>
                    <li>Contante: {{ number_format($b['contante'], 2, ',', '.') }} € · POS: {{ number_format($b['pos'], 2, ',', '.') }} €</li>
                </ul>
            @else
                <p class="mt-2 text-sm text-sagra-muted print:hidden">Scegli una serata B dal selettore sopra.</p>
            @endif
        </div>
    </div>

    @if ($a && $b)
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="report-card rounded-md ring-1 ring-sagra-line px-3 py-2 text-sm">
                Δ coperti: <strong class="tabular-nums">{{ $a['coperti'] - $b['coperti'] }}</strong>
            </div>
            <div class="report-card rounded-md ring-1 ring-sagra-line px-3 py-2 text-sm">
                Δ comande: <strong class="tabular-nums">{{ $a['comande'] - $b['comande'] }}</strong>
            </div>
            <div class="report-card rounded-md ring-1 ring-sagra-line px-3 py-2 text-sm">
                Δ incasso: <strong class="tabular-nums">{{ number_format($a['incasso'] - $b['incasso'], 2, ',', '.') }} €</strong>
            </div>
        </div>
    @endif

    <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Piatti (Δ quantità A − B)</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sagra-line text-sm">
            <thead>
                <tr class="bg-sagra-softer text-left">
                    <th class="px-3 py-2">Piatto</th>
                    <th class="px-3 py-2 text-right">A</th>
                    <th class="px-3 py-2 text-right">B</th>
                    <th class="px-3 py-2 text-right">Δ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sagra-line">
                @forelse ($piatti as $p)
                    <tr>
                        <td class="px-3 py-2">{{ $p['nome'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $p['qta_a'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $p['qta_b'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ $p['delta'] > 0 ? '+'.$p['delta'] : $p['delta'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-sagra-muted">Nessun dato da confrontare.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
