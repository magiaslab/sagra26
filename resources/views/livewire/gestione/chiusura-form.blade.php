<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Chiusura & riconciliazione" subtitle="Conta pezzi, fondo cassa e confronto a tre vie">
        <x-slot:actions>
            @if ($riconciliazione)
                <a
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer"
                    href="{{ route('gestione.report', [
                        'tipo' => 'consegna',
                        'serata_id' => $serataId,
                        'punto_cassa_id' => $puntoCassaId,
                        'print' => 1,
                    ], absolute: false) }}"
                >Stampa foglio consegna</a>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    @if ($errore)
        <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
    @endif

    @if ($bloccata)
        <x-ui.alert type="warn" class="mb-4">
            <p class="m-0">Serata chiusa: il foglio conteggi è in sola lettura.</p>
            <button
                type="button"
                class="mt-3 inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark"
                wire:click="riapriPerCorreggere"
            >Riapri per correggere conteggi</button>
            <p class="mt-2 mb-0 text-xs text-sagra-muted">
                Riapre la serata (se non ce n’è un’altra aperta) e sblocca questo foglio. Poi correggi e premi di nuovo <strong>Salva chiusura</strong>.
            </p>
        </x-ui.alert>
    @elseif ($chiusuraCompletata)
        <x-ui.alert type="ok" class="mb-4">
            Chiusura salvata{{ $chiusaAtLabel ? ' il '.$chiusaAtLabel : '' }}.
            Puoi ancora correggere i conteggi e risalvare finché la serata resta aperta.
        </x-ui.alert>
    @endif

    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-sagra-ink">Serata</label>
            <select class="block w-full min-w-0 rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="serataId">
                @foreach ($serate as $s)
                    <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }} ({{ $s->stato }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-sagra-ink">Punto cassa</label>
            <select class="block w-full min-w-0 rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="puntoCassaId">
                @foreach ($punti as $p)
                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($sospesiAperti->isNotEmpty())
        <div class="mb-3 rounded-lg bg-sagra-amber-soft px-3 py-3 text-sagra-ink ring-2 ring-inset ring-sagra-warn/50 sm:px-4" role="status">
            <p class="m-0 text-sm font-semibold text-sagra-warn sm:text-base">
                {{ $sospesiAperti->count() }} {{ $sospesiAperti->count() === 1 ? 'conto sospeso ancora aperto' : 'conti sospesi ancora aperti' }},
                per un totale di {{ number_format($sospesiApertiTotale, 2, ',', '.') }} €.
            </p>
            <p class="mt-1 mb-0 text-sm text-sagra-ink">
                Verificali prima di chiudere la cassa.
                <a class="font-semibold text-sagra underline hover:text-sagra-dark"
                   href="{{ route('gestione.sospesi', absolute: false) }}">Vai a Sospesi →</a>
            </p>
        </div>
    @endif

    {{-- Due colonne da lg (~1024px): copre notebook 15.6" anche con zoom/sidebar --}}
    <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-2 lg:gap-4" @if ($bloccata) aria-disabled="true" @endif>
        <div class="min-w-0 space-y-3 {{ $bloccata ? 'opacity-75' : '' }}">
            <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-sagra-line/80 sm:p-4">
                <h2 class="mb-0.5 mt-0 text-lg font-semibold text-sagra-ink">1. Conta pezzi cassetto</h2>
                <p class="mt-0 mb-2 text-xs leading-snug text-sagra-muted sm:text-sm">
                    Contante in cassa a fine serata. Clic sul campo: lo zero si seleziona e digiti subito.
                </p>
                <div class="mb-2">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Fondo iniziale (sera)</label>
                    <input
                        class="block w-full min-w-0 rounded-md bg-white px-2.5 py-1.5 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra sm:px-3 sm:py-2"
                        type="number" inputmode="decimal" step="0.01" min="0"
                        wire:model.live="fondo_iniziale"
                        x-on:focus="$el.select()"
                        @disabled($bloccata)
                    >
                </div>
                <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 sm:grid-cols-3">
                    @foreach ($tagli as $campo => $valore)
                        <div class="min-w-0">
                            <label class="mb-0.5 block truncate text-xs font-medium text-sagra-ink sm:text-sm">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</label>
                            <input
                                class="block w-full min-w-0 rounded-md bg-white px-2 py-1.5 text-sm tabular-nums text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra"
                                type="number" inputmode="numeric" step="1" min="0"
                                wire:model.live="pezzi.{{ $campo }}"
                                x-on:focus="$el.select()"
                                @disabled($bloccata)
                            >
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-col gap-2 rounded-md bg-sagra-softer px-3 py-2.5 sm:flex-row sm:items-end sm:justify-between sm:gap-3">
                    <div class="min-w-0">
                        <div class="text-[0.65rem] font-semibold uppercase tracking-wider text-sagra-muted">Totale pezzi contati</div>
                        <div class="text-xl font-bold tabular-nums text-sagra-ink sm:text-2xl">{{ number_format($contanteContato, 2, ',', '.') }} €</div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark disabled:opacity-50"
                        wire:click="copiaPezziNelFondo"
                        @disabled($bloccata)
                        title="Copia questi pezzi nei campi del fondo sera dopo"
                    >Copia nel fondo</button>
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-sagra-line/80 ring-sagra-amber/30 sm:p-4">
                <h2 class="mb-0.5 mt-0 text-lg font-semibold text-sagra-ink">2. Fondo cassa sera dopo</h2>
                <p class="mt-0 mb-2 text-xs leading-snug text-sagra-muted sm:text-sm">
                    Pezzi che <strong>lasci in cassa</strong>. Il totale diventa fondo trattenuto per la serata successiva.
                    Usa «Copia nel fondo» sopra se riutilizzi gli stessi pezzi.
                </p>
                <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 sm:grid-cols-3">
                    @foreach ($tagli as $campo => $valore)
                        <div class="min-w-0">
                            <label class="mb-0.5 block truncate text-xs font-medium text-sagra-ink sm:text-sm">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</label>
                            <input
                                class="block w-full min-w-0 rounded-md bg-amber-50/50 px-2 py-1.5 text-sm tabular-nums text-sagra-ink shadow-sm ring-1 ring-inset ring-amber-200/60 focus:ring-2 focus:ring-sagra"
                                type="number" inputmode="numeric" step="1" min="0"
                                wire:model.live="pezziFondo.{{ $campo }}"
                                x-on:focus="$el.select()"
                                @disabled($bloccata)
                            >
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-col gap-2 rounded-md bg-sagra-amber-soft px-3 py-2.5 sm:flex-row sm:items-end sm:justify-between sm:gap-3">
                    <div class="min-w-0">
                        <div class="text-[0.65rem] font-semibold uppercase tracking-wider text-sagra-muted">Totale pezzi fondo</div>
                        <div class="text-xl font-bold tabular-nums text-sagra-ink sm:text-2xl">{{ number_format($fondoPezziTotale, 2, ',', '.') }} €</div>
                        <div class="mt-0.5 break-words text-xs leading-snug text-sagra-muted">{{ $fondoPezziDescrizione }}</div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-white/80 disabled:opacity-50"
                        wire:click="applicaTotalePezziFondo"
                        @disabled($bloccata)
                        title="Imposta il fondo trattenuto uguale al totale pezzi fondo"
                    >Usa come fondo</button>
                </div>
                <div class="mb-0 mt-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Fondo trattenuto (€)</label>
                    <input
                        class="block w-full min-w-0 rounded-md bg-white px-2.5 py-1.5 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra sm:px-3 sm:py-2"
                        type="number" inputmode="decimal" step="0.01" min="0"
                        wire:model.live="fondo_trattenuto"
                        x-on:focus="$el.select()"
                        @disabled($bloccata)
                    >
                    <p class="mt-1 mb-0 text-xs leading-snug text-sagra-muted">
                        @if ($syncFondoDaPezzi)
                            Collegato al conteggio pezzi sopra.
                        @else
                            Modificato a mano — premi «Usa come fondo» per riallineare ai pezzi.
                        @endif
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-sagra-line/80 sm:p-4">
                <h2 class="mb-2 mt-0 text-lg font-semibold text-sagra-ink">3. POS, Z e note</h2>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <div class="min-w-0">
                        <label class="mb-1 block text-sm font-medium text-sagra-ink">Totale POS (terminale)</label>
                        <input
                            class="block w-full min-w-0 rounded-md bg-white px-2.5 py-1.5 text-sm tabular-nums text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra sm:px-3 sm:py-2"
                            type="number" inputmode="decimal" step="0.01" min="0"
                            wire:model.live="totale_pos"
                            x-on:focus="$el.select()"
                            @disabled($bloccata)
                        >
                        <p class="mt-1 mb-0 text-xs text-sagra-muted">Chiusura del terminale POS (scontrino bancario).</p>
                    </div>
                    <div class="min-w-0 sm:col-span-2">
                        <p class="mb-1.5 text-sm font-medium text-sagra-ink">Registratore di cassa (Z)</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div class="min-w-0">
                                <label class="mb-1 block text-xs font-medium text-sagra-muted">Z Contante</label>
                                <input
                                    class="block w-full min-w-0 rounded-md bg-white px-2.5 py-1.5 text-sm tabular-nums text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra sm:px-3 sm:py-2"
                                    type="number" inputmode="decimal" step="0.01" min="0"
                                    wire:model.live="totale_z_contante"
                                    x-on:focus="$el.select()"
                                    @disabled($bloccata)
                                >
                            </div>
                            <div class="min-w-0">
                                <label class="mb-1 block text-xs font-medium text-sagra-muted">Z POS</label>
                                <input
                                    class="block w-full min-w-0 rounded-md bg-white px-2.5 py-1.5 text-sm tabular-nums text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra sm:px-3 sm:py-2"
                                    type="number" inputmode="decimal" step="0.01" min="0"
                                    wire:model.live="totale_z_pos"
                                    x-on:focus="$el.select()"
                                    @disabled($bloccata)
                                >
                            </div>
                            <div class="min-w-0">
                                <label class="mb-1 block text-xs font-medium text-sagra-muted">Totale Z</label>
                                <input
                                    class="block w-full min-w-0 rounded-md bg-sagra-softer px-2.5 py-1.5 text-sm font-semibold tabular-nums text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line sm:px-3 sm:py-2"
                                    type="text"
                                    value="{{ number_format((float) str_replace(',', '.', $totale_z ?: '0'), 2, ',', '.') }} €"
                                    readonly
                                    tabindex="-1"
                                >
                            </div>
                        </div>
                        <p class="mt-1 mb-0 text-xs text-sagra-muted">I due totali del registratore; la somma è il Totale Z usato nei delta.</p>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Note</label>
                    <textarea class="block w-full min-w-0 rounded-md bg-white px-2.5 py-1.5 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra sm:px-3 sm:py-2" wire:model="note" rows="2" @disabled($bloccata)></textarea>
                </div>
                <button class="mt-3 inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark disabled:opacity-50" wire:click="salva" @disabled($bloccata)>
                    {{ $chiusuraCompletata ? 'Salva correzione chiusura' : 'Salva chiusura' }}
                </button>
            </div>
        </div>

        <div class="min-w-0 rounded-lg bg-white p-3 shadow-sm ring-1 ring-sagra-line/80 sm:p-4 lg:sticky lg:top-3 lg:self-start">
            <h2 class="mb-2 mt-0 text-lg font-semibold text-sagra-ink">Riconciliazione a tre vie</h2>
            @if ($riconciliazione)
                <p class="mt-0 mb-2 text-sm text-sagra-ink">Contante contato: <strong class="tabular-nums">{{ number_format($riconciliazione['contante_contato'], 2, ',', '.') }} €</strong></p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-0 table-fixed divide-y divide-sagra-line text-xs sm:text-sm">
                        <colgroup>
                            <col class="w-[28%]">
                            <col class="w-[24%]">
                            <col class="w-[24%]">
                            <col class="w-[24%]">
                        </colgroup>
                        <thead>
                            <tr class="bg-sagra-softer">
                                <th class="px-1.5 py-1.5 text-left font-semibold text-sagra-ink sm:px-2"></th>
                                <th class="px-1.5 py-1.5 text-right font-semibold text-sagra-ink sm:px-2">Contante</th>
                                <th class="px-1.5 py-1.5 text-right font-semibold text-sagra-ink sm:px-2">POS</th>
                                <th class="px-1.5 py-1.5 text-right font-semibold text-sagra-ink sm:px-2">Totale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sagra-line">
                            <tr>
                                <td class="px-1.5 py-1.5 sm:px-2">Atteso</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['atteso_contante'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['atteso_pos'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['atteso_totale'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-1.5 py-1.5 sm:px-2">Reale</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['reale_contante'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['reale_pos'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['reale_contante'] + $riconciliazione['reale_pos'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-1.5 py-1.5 sm:px-2">Fiscale (Z)</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['fiscale_contante'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['fiscale_pos'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['fiscale'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-1.5 py-1.5 sm:px-2">Δ reale</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['delta_contante'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['delta_pos'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['delta_contante'] + $riconciliazione['delta_pos'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-1.5 py-1.5 sm:px-2">Δ fiscale</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['delta_fiscale_contante'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['delta_fiscale_pos'], 2, ',', '.') }}</td>
                                <td class="px-1.5 py-1.5 text-right tabular-nums sm:px-2">{{ number_format($riconciliazione['delta_fiscale'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 space-y-1.5 text-sm leading-snug text-sagra-ink">
                    <p class="m-0">Consegnato: <strong class="tabular-nums">{{ number_format($riconciliazione['contante_consegnato'], 2, ',', '.') }} €</strong></p>
                    <p class="m-0">Incasso contante reale: <strong class="tabular-nums">{{ number_format($riconciliazione['incasso_contante_reale'], 2, ',', '.') }} €</strong></p>
                    <p class="m-0">
                        Fondo sera dopo: <strong class="tabular-nums">{{ number_format((float) $fondo_trattenuto, 2, ',', '.') }} €</strong>
                        @if ($fondoPezziTotale > 0)
                            <span class="text-sagra-muted">(pezzi {{ number_format($fondoPezziTotale, 2, ',', '.') }} €)</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
