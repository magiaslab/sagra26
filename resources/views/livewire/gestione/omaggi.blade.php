<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Omaggi"
        subtitle="Comande omaggio della serata — rendiconto per i responsabili"
    >
        <x-slot:actions>
            @if ($serata)
                <span class="inline-flex min-h-9 items-center rounded-md bg-sky-50 px-3 py-1.5 text-sm font-medium text-sky-900 ring-1 ring-sky-200">
                    {{ $omaggi->count() }} omaggi · {{ number_format($totaleValore, 2, ',', '.') }} €
                </span>
                @if ($omaggi->isNotEmpty())
                    <button type="button"
                            class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark"
                            wire:click="exportCsv">
                        Export riepilogo CSV
                    </button>
                @endif
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
                <label class="mb-1 block text-xs font-medium text-sagra-muted" for="serata-omaggi">Serata</label>
                <select id="serata-omaggi"
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
                    Coperti omaggio: <span class="font-semibold text-sagra-ink">{{ $totaleCoperti }}</span>
                    · non conteggiati negli incassi
                </p>
            @endif
        </div>

        @if ($omaggi->isEmpty())
            <p class="rounded-lg bg-white px-5 py-8 text-center text-sm text-sagra-muted shadow-sm ring-1 ring-sagra-line/80">
                Nessuna comanda omaggio in questa serata.
            </p>
        @else
            @if ($perAutorizzatore->isNotEmpty())
                <div class="mb-4 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
                    <div class="border-b border-sagra-line bg-sagra-softer px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-sagra-muted">
                        Riepilogo per chi ha autorizzato
                    </div>
                    <ul class="divide-y divide-sagra-line text-sm">
                        @foreach ($perAutorizzatore as $row)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                                <span class="font-medium text-sagra-ink">{{ $row['nome'] }}</span>
                                <span class="text-sagra-muted">
                                    {{ $row['count'] }} {{ $row['count'] === 1 ? 'comanda' : 'comande' }}
                                    · <span class="font-mono font-semibold tabular-nums text-sagra-ink">{{ number_format($row['totale'], 2, ',', '.') }} €</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="bg-sagra-softer text-xs uppercase tracking-wide text-sagra-muted">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">N.</th>
                            <th class="px-4 py-2.5 font-semibold">Ora</th>
                            <th class="px-4 py-2.5 font-semibold">Ospite</th>
                            <th class="px-4 py-2.5 font-semibold">Autorizzato da</th>
                            <th class="px-4 py-2.5 font-semibold">Cassa</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Valore</th>
                            <th class="px-4 py-2.5 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sagra-line">
                        @foreach ($omaggi as $c)
                            <tr wire:key="omaggio-{{ $c->id }}">
                                <td class="px-4 py-3 font-mono font-semibold">#{{ $c->numero_progressivo }}</td>
                                <td class="px-4 py-3 text-sagra-muted tabular-nums">
                                    {{ optional($c->created_at)->format('H:i') ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sagra-ink">{{ $c->nominativo ?: '—' }}</div>
                                    @if ($c->pagamento_note)
                                        <div class="text-xs text-sagra-muted">{{ $c->pagamento_note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sagra-muted">{{ $c->autorizzato_da ?: '—' }}</td>
                                <td class="px-4 py-3 text-sagra-muted">{{ $c->postazione?->nome }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums">{{ number_format($c->totale, 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-right">
                                    <a class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-sagra-ink ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer"
                                       href="{{ route('cassa.stampa', $c, absolute: false) }}" target="_blank">Stampa</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-sagra-line bg-sagra-softer text-sm font-semibold">
                        <tr>
                            <td class="px-4 py-3" colspan="5">Totale valore omaggi (non in cassa)</td>
                            <td class="px-4 py-3 text-right font-mono tabular-nums">{{ number_format($totaleValore, 2, ',', '.') }} €</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endif
</div>
