<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <h2 class="mt-0 text-xl font-semibold text-sagra-ink">{{ $impostazioni->intestazione_nome }} — Economico</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sagra-line text-sm">
            <thead>
                <tr class="bg-sagra-softer">
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Serata</th>
                    <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Contante</th>
                    <th class="px-3 py-2 text-right font-semibold text-sagra-ink">POS</th>
                    <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Totale</th>
                    <th class="px-3 py-2 text-right font-semibold text-sagra-ink">di cui Bar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sagra-line">
            @foreach ($dati['righe'] as $r)
                <tr>
                    <td class="px-3 py-2">{{ $r['data'] }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['contante'], 2, ',', '.') }} €</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['pos'], 2, ',', '.') }} €</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['totale'], 2, ',', '.') }} €</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['bar'] ?? 0, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            <tr class="bg-sagra-softer font-semibold">
                <td class="px-3 py-2">TOTALE</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($dati['tot_contante'], 2, ',', '.') }} €</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($dati['tot_pos'], 2, ',', '.') }} €</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($dati['totale'], 2, ',', '.') }} €</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($dati['di_cui_bar'], 2, ',', '.') }} €</td>
            </tr>
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-sm text-sagra-ink">Ripartizione: contante {{ $dati['pct_contante'] }}% · POS {{ $dati['pct_pos'] }}%</p>
</div>
