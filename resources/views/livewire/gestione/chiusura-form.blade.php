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

    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="mb-3">
            <label class="mb-1 block text-sm font-medium text-sagra-ink">Serata</label>
            <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="serataId">
                @foreach ($serate as $s)
                    <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }} ({{ $s->stato }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="mb-1 block text-sm font-medium text-sagra-ink">Punto cassa</label>
            <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="puntoCassaId">
                @foreach ($punti as $p)
                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2" @if ($bloccata) aria-disabled="true" @endif>
        <div class="space-y-4 {{ $bloccata ? 'opacity-75' : '' }}">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-1 mt-0 text-xl font-semibold text-sagra-ink">1. Conta pezzi cassetto</h2>
                <p class="mt-0 mb-3 text-sm text-sagra-muted">Tutto il contante presente in cassa a fine serata.</p>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Fondo iniziale (sera)</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="fondo_iniziale" @disabled($bloccata)>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($tagli as $campo => $valore)
                        <div class="mb-2">
                            <label class="mb-1 block text-sm font-medium text-sagra-ink">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</label>
                            <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" min="0" wire:model.live="pezzi.{{ $campo }}" @disabled($bloccata)>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 ring-sagra-amber/30">
                <h2 class="mb-1 mt-0 text-xl font-semibold text-sagra-ink">2. Fondo cassa sera dopo</h2>
                <p class="mt-0 mb-3 text-sm text-sagra-muted">
                    Conta i pezzi che <strong>lasci in cassa</strong> (es. tutte le monete). Il totale diventa il fondo trattenuto e verrà suggerito all’apertura della serata successiva.
                </p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($tagli as $campo => $valore)
                        <div class="mb-2">
                            <label class="mb-1 block text-sm font-medium text-sagra-ink">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</label>
                            <input class="block w-full rounded-md bg-amber-50/50 px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-amber-200/60 focus:ring-2 focus:ring-sagra" type="number" min="0" wire:model.live="pezziFondo.{{ $campo }}" @disabled($bloccata)>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap items-end justify-between gap-3 rounded-md bg-sagra-amber-soft px-3 py-3">
                    <div>
                        <div class="text-[0.65rem] font-semibold uppercase tracking-wider text-sagra-muted">Totale pezzi fondo</div>
                        <div class="text-2xl font-bold tabular-nums text-sagra-ink">{{ number_format($fondoPezziTotale, 2, ',', '.') }} €</div>
                        <div class="mt-0.5 text-xs text-sagra-muted">{{ $fondoPezziDescrizione }}</div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-white/80 disabled:opacity-50"
                        wire:click="applicaTotalePezziFondo"
                        @disabled($bloccata)
                    >Usa come fondo trattenuto</button>
                </div>
                <div class="mb-0 mt-4">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Fondo trattenuto (€)</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="fondo_trattenuto" @disabled($bloccata)>
                    <p class="mt-1 mb-0 text-xs text-sagra-muted">
                        @if ($syncFondoDaPezzi)
                            Collegato al conteggio pezzi sopra.
                        @else
                            Modificato a mano — premi «Usa come fondo trattenuto» per riallineare ai pezzi.
                        @endif
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">3. POS, Z e note</h2>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Totale POS (da terminale)</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="totale_pos" @disabled($bloccata)>
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Totale Z (registratore)</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="totale_z" @disabled($bloccata)>
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Note</label>
                    <textarea class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="note" rows="2" @disabled($bloccata)></textarea>
                </div>
                <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark disabled:opacity-50" wire:click="salva" @disabled($bloccata)>
                    {{ $chiusuraCompletata ? 'Salva correzione chiusura' : 'Salva chiusura' }}
                </button>
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 xl:sticky xl:top-4 xl:self-start">
            <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Riconciliazione a tre vie</h2>
            @if ($riconciliazione)
                <p class="mt-0 text-sm text-sagra-ink">Contante contato: <strong>{{ number_format($riconciliazione['contante_contato'], 2, ',', '.') }} €</strong></p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sagra-line text-sm">
                        <thead>
                            <tr class="bg-sagra-softer">
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink"></th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Contante</th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">POS</th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Totale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sagra-line">
                            <tr>
                                <td class="px-3 py-2">Atteso (app)</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['atteso_contante'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['atteso_pos'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['atteso_totale'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2">Reale (fisico)</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['reale_contante'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['reale_pos'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['reale_contante'] + $riconciliazione['reale_pos'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2">Fiscale (Z)</td>
                                <td class="px-3 py-2" colspan="2">—</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['fiscale'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2">Δ</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['delta_contante'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ number_format($riconciliazione['delta_pos'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2">Δfisc {{ number_format($riconciliazione['delta_fiscale'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-sm text-sagra-ink">Consegnato: <strong>{{ number_format($riconciliazione['contante_consegnato'], 2, ',', '.') }} €</strong>
                    · Incasso contante reale: <strong>{{ number_format($riconciliazione['incasso_contante_reale'], 2, ',', '.') }} €</strong></p>
                <p class="text-sm text-sagra-ink">Fondo sera dopo: <strong>{{ number_format($fondo_trattenuto, 2, ',', '.') }} €</strong>
                    @if ($fondoPezziTotale > 0)
                        <span class="text-sagra-muted">(da pezzi {{ number_format($fondoPezziTotale, 2, ',', '.') }} €)</span>
                    @endif
                </p>
            @endif
        </div>
    </div>
</div>
