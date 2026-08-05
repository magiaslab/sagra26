<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="m-0 text-xl font-semibold text-sagra-ink">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</h2>
            <div class="report-meta text-xs text-sagra-muted">Report BEVANDE / BAR — {{ $serata->data->format('d/m/Y') }}</div>
        </div>
    </div>

    @foreach ($dati['sezioni'] as $sez)
        <section class="mt-6 break-inside-avoid" wire:key="sez-{{ $sez['key'] }}">
            <div @class([
                'rounded-md border-l-4 px-3 py-2',
                'border-sagra bg-sagra-softer' => $sez['key'] === 'bevande',
                'border-sagra-amber bg-sagra-amber-soft' => $sez['key'] === 'bar',
            ])>
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3 class="m-0 text-lg font-bold uppercase tracking-wide text-sagra-ink">{{ $sez['label'] }}</h3>
                        <p class="m-0 text-xs text-sagra-muted">{{ $sez['descrizione'] }}</p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-right">
                        <div>
                            <div class="text-[0.65rem] font-semibold uppercase tracking-wider text-sagra-muted">Stasera</div>
                            <div class="text-xl font-bold tabular-nums text-sagra-ink">{{ number_format($sez['stasera'], 2, ',', '.') }} €</div>
                            <div class="text-xs tabular-nums text-sagra-muted">{{ $sez['stasera_qta'] }} pz</div>
                        </div>
                        <div>
                            <div class="text-[0.65rem] font-semibold uppercase tracking-wider text-sagra-muted">Cumulativo</div>
                            <div class="text-xl font-bold tabular-nums text-sagra-ink">{{ number_format($sez['cumulato'], 2, ',', '.') }} €</div>
                            <div class="text-xs tabular-nums text-sagra-muted">{{ $sez['cumulato_qta'] }} pz</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($sez['items']->isEmpty())
                <p class="mt-3 text-sm text-sagra-muted">Nessuna voce in {{ $sez['label'] }}.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-sagra-line text-sm">
                        <thead>
                            <tr class="bg-sagra-softer">
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Voce</th>
                                <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Q.tà stasera</th>
                                <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Incasso stasera</th>
                                <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Q.tà cumulato</th>
                                <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Incasso cumulato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sagra-line">
                        @foreach ($sez['items'] as $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->nome }}@unless($item->attivo) <span class="text-xs font-medium text-sagra-muted">off</span>@endunless</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $dati['stasera_qta'][$item->id] ?? 0 }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($dati['stasera_incasso'][$item->id] ?? 0, 2, ',', '.') }} €</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $dati['cumulato_qta'][$item->id] ?? 0 }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($dati['cumulato_incasso'][$item->id] ?? 0, 2, ',', '.') }} €</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-sagra-softer font-semibold">
                                <td class="px-3 py-2 text-sagra-ink">Totale {{ $sez['label'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $sez['stasera_qta'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($sez['stasera'], 2, ',', '.') }} €</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $sez['cumulato_qta'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($sez['cumulato'], 2, ',', '.') }} €</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
</div>
