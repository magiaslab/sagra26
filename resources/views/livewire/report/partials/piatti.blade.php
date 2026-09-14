<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <h2 class="mt-0 text-xl font-semibold text-sagra-ink">
        {{ $impostazioni->intestazione_nome }} — Cumulativo menù (tutti i reparti)
    </h2>
    <p class="mt-1 text-sm text-sagra-muted">
        Periodo {{ $dati['label_da'] ?? '—' }} → {{ $dati['label_a'] ?? '—' }}
        · {{ $dati['n_serate'] ?? 0 }} {{ ($dati['n_serate'] ?? 0) === 1 ? 'serata' : 'serate' }}
        @if (!empty($dati['serate_label']))
            ({{ implode(', ', $dati['serate_label']) }})
        @endif
    </p>
    <p class="mt-1 text-sm text-sagra-muted">
        Quantità vendute di <strong>tutto il menù</strong>: cucina, griglia, bevande/bibite e bar.
    </p>

    @if (!empty($dati['errore']))
        <x-ui.alert type="warn" class="mt-3">{{ $dati['errore'] }}</x-ui.alert>
    @else
        <div class="my-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Pezzi venduti</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ number_format($dati['totale_qta'], 0, ',', '.') }}</div>
            </div>
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Incasso voci</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ number_format($dati['totale_incasso'], 2, ',', '.') }} €</div>
            </div>
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Coperti / comande</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ $dati['coperti'] }} / {{ $dati['comande'] }}</div>
            </div>
            <div class="report-kpi rounded-lg bg-sagra-softer px-3 py-2">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Contante / POS</div>
                <div class="text-sm font-bold tabular-nums text-sagra-dark">
                    {{ number_format($dati['contante'], 2, ',', '.') }} /
                    {{ number_format($dati['pos'], 2, ',', '.') }}
                </div>
            </div>
        </div>

        @if (!empty($dati['reparti']))
            <h3 class="mb-2 mt-2 text-base font-semibold text-sagra-ink">Riepilogo per reparto</h3>
            <div class="mb-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-sagra-line text-sm">
                    <thead>
                        <tr class="bg-sagra-softer text-left">
                            <th class="px-3 py-2">Reparto</th>
                            <th class="px-3 py-2 text-right">Voci</th>
                            <th class="px-3 py-2 text-right">Q.tà</th>
                            <th class="px-3 py-2 text-right">Incasso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sagra-line">
                        @foreach ($dati['reparti'] as $rep)
                            <tr>
                                <td class="px-3 py-1.5 font-medium">{{ $rep['nome'] }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ count($rep['items']) }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums font-semibold">{{ number_format($rep['qta'], 0, ',', '.') }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($rep['incasso'], 2, ',', '.') }} €</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="mb-3 text-xs text-sagra-muted print:hidden">
            Incasso voci = somme nette (omaggi/sospesi esclusi, sconti % applicati). Contante/POS dalle comande del periodo.
        </p>

        @forelse ($dati['reparti'] ?? $dati['categorie'] ?? [] as $rep)
            <h3 class="mb-2 mt-5 text-base font-semibold text-sagra-ink">{{ $rep['nome'] }}</h3>
            <div class="overflow-x-auto">
                <table class="mb-1 min-w-full divide-y divide-sagra-line text-sm">
                    <thead>
                        <tr class="bg-sagra-softer text-left">
                            <th class="px-3 py-2">Voce menù</th>
                            <th class="px-3 py-2">Categoria</th>
                            <th class="px-3 py-2 text-right">Q.tà</th>
                            <th class="px-3 py-2 text-right">Incasso</th>
                            <th class="px-3 py-2 text-right">Media €</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sagra-line">
                        @foreach ($rep['items'] as $item)
                            <tr>
                                <td class="px-3 py-1.5 font-medium">{{ $item['nome'] }}</td>
                                <td class="px-3 py-1.5 text-sagra-muted">{{ $item['categoria'] ?? $item['area'] ?? '—' }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums font-semibold">{{ $item['qta'] }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($item['incasso'], 2, ',', '.') }} €</td>
                                <td class="px-3 py-1.5 text-right tabular-nums text-sagra-muted">
                                    {{ $item['qta'] > 0 ? number_format($item['incasso'] / $item['qta'], 2, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-sagra-softer font-semibold">
                            <td class="px-3 py-2" colspan="2">Totale {{ $rep['nome'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $rep['qta'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($rep['incasso'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @empty
            <p class="mt-4 text-sm text-sagra-muted">Nessuna vendita nel periodo selezionato.</p>
        @endforelse

        @if (!empty($dati['reparti']) || !empty($dati['categorie']))
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <tfoot>
                        <tr class="bg-sagra-ink text-white font-semibold">
                            <td class="px-3 py-2.5">TOTALE PERIODO (tutti i reparti)</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($dati['totale_qta'], 0, ',', '.') }} pz</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($dati['totale_incasso'], 2, ',', '.') }} €</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endif
</div>
