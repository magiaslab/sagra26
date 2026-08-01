<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Chiusura & riconciliazione" subtitle="Conta pezzi e confronto a tre vie">
        <x-slot:actions>
            @if ($riconciliazione)
                <a class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" href="{{ route('gestione.report', absolute: false) }}?tipo=consegna">Foglio consegna</a>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

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

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Conta pezzi</h2>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Fondo iniziale</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="fondo_iniziale">
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($tagli as $campo => $valore)
                    <div class="mb-3">
                        <label class="mb-1 block text-sm font-medium text-sagra-ink">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</label>
                        <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" min="0" wire:model.live="pezzi.{{ $campo }}">
                    </div>
                @endforeach
            </div>
            <div class="mb-3 mt-4">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Fondo trattenuto</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="fondo_trattenuto">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Totale POS (da terminale)</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="totale_pos">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Totale Z (registratore)</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model.live="totale_z">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Note</label>
                <textarea class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="note" rows="2"></textarea>
            </div>
            <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="salva">Salva chiusura</button>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
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
            @endif
        </div>
    </div>
</div>
