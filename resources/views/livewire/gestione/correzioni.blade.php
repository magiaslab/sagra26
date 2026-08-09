<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Comande modificate"
        subtitle="Storico correzioni della serata — voci e pagamento prima → dopo"
    >
        <x-slot:actions>
            @if ($serata)
                <span class="inline-flex min-h-9 items-center rounded-md bg-sky-50 px-3 py-1.5 text-sm font-medium text-sky-900 ring-1 ring-sky-200">
                    {{ $correzioni->count() }} correzioni · {{ $comandeUniche }} comande
                </span>
            @else
                <span class="inline-flex min-h-9 items-center rounded-md bg-sagra-amber-soft px-3 py-1.5 text-sm font-medium text-sagra-warn">
                    Nessuna serata
                </span>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    @if ($serate->isEmpty())
        <p class="text-sm text-sagra-muted">Nessuna serata registrata.</p>
    @else
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-sagra-muted" for="serata-correzioni">Serata</label>
                <select id="serata-correzioni"
                        class="block min-w-[12rem] rounded-md bg-white px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line"
                        wire:model.live="serataId">
                    @foreach ($serate as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->data->format('d/m/Y') }}
                            @if ($s->stato === 'aperta') · aperta @endif
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($serata)
                <p class="pb-2 text-sm text-sagra-muted">
                    Ogni riga è una correzione. La comanda attuale è quella dopo la modifica
                    (chiusura usa sempre lo stato corrente).
                </p>
            @endif
        </div>

        @if ($correzioni->isEmpty())
            <p class="rounded-lg bg-white px-5 py-8 text-center text-sm text-sagra-muted shadow-sm ring-1 ring-sagra-line/80">
                Nessuna comanda modificata in questa serata.
            </p>
        @else
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="bg-sagra-softer text-xs uppercase tracking-wide text-sagra-muted">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Ora</th>
                            <th class="px-4 py-2.5 font-semibold">Comanda</th>
                            <th class="px-4 py-2.5 font-semibold">Da cassa</th>
                            <th class="px-4 py-2.5 font-semibold">Pagamento</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Totale</th>
                            <th class="px-4 py-2.5 font-semibold">Motivo</th>
                            <th class="px-4 py-2.5 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sagra-line">
                        @foreach ($correzioni as $corr)
                            @php
                                $c = $corr->comanda;
                                $prec = $corr->pagamento_precedente ?? [];
                                $totPrec = (float) $corr->totale_precedente;
                                $totAtt = $c ? (float) $c->totale : 0.0;
                                $delta = round($totAtt - $totPrec, 2);
                            @endphp
                            <tr wire:key="corr-{{ $corr->id }}">
                                <td class="px-4 py-3 text-sagra-muted tabular-nums">
                                    {{ optional($corr->created_at)->format('H:i') ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono font-semibold">#{{ $c?->numero_progressivo ?? '—' }}</div>
                                    @if ($c?->isAnnullata())
                                        <div class="text-xs font-medium text-sagra-warn">annullata</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sagra-muted">
                                    {{ $corr->postazione?->nome ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs text-sagra-muted">prima</div>
                                    <div class="font-medium text-sagra-ink">{{ $corr->etichettaPagamentoPrecedente() }}</div>
                                    <div class="mt-1 text-xs text-sagra-muted">ora</div>
                                    <div class="font-semibold text-sagra-ink">{{ $c?->etichettaPagamento() ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <div class="text-xs text-sagra-muted">{{ number_format($totPrec, 2, ',', '.') }} €</div>
                                    <div class="font-mono font-semibold">{{ number_format($totAtt, 2, ',', '.') }} €</div>
                                    @if (abs($delta) >= 0.005)
                                        <div @class([
                                            'text-xs font-semibold',
                                            'text-sagra-amber' => $delta > 0,
                                            'text-sky-800' => $delta < 0,
                                        ])>
                                            {{ $delta > 0 ? '+' : '−' }}{{ number_format(abs($delta), 2, ',', '.') }} €
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sagra-muted">
                                    {{ $corr->motivo ?: '—' }}
                                    @if ($c && ($c->importoContanteEffettivo() != 0 || $c->importoPosEffettivo() != 0) && $c->metodo_pagamento === 'misto')
                                        <div class="mt-1 text-xs">
                                            € {{ number_format($c->importoContanteEffettivo(), 2, ',', '.') }}
                                            + ▭ {{ number_format($c->importoPosEffettivo(), 2, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($c && ! $c->isAnnullata())
                                        <div class="flex flex-col items-end gap-1.5 sm:flex-row sm:justify-end">
                                            <a class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-sagra-ink ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer"
                                               href="{{ route('cassa.stampa', $c, absolute: false) }}" target="_blank">Stampa</a>
                                            <a class="inline-flex items-center rounded-md bg-sagra px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-sagra-dark"
                                               href="{{ route('cassa', absolute: false) }}?richiamo={{ $c->numero_progressivo }}">Richiamo</a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
