<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Chiusura & riconciliazione" subtitle="Conta pezzi e confronto a tre vie">
        <x-slot:actions>
            @if ($riconciliazione)
                <a class="btn" href="{{ route('gestione.report', absolute: false) }}?tipo=consegna">Foglio consegna</a>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="field">
            <label class="label">Serata</label>
            <select class="input" wire:model.live="serataId">
                @foreach ($serate as $s)
                    <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }} ({{ $s->stato }})</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label class="label">Punto cassa</label>
            <select class="input" wire:model.live="puntoCassaId">
                @foreach ($punti as $p)
                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="panel">
            <h2 class="mt-0 mb-3 text-xl font-extrabold">Conta pezzi</h2>
            <div class="field">
                <label class="label">Fondo iniziale</label>
                <input class="input" type="number" step="0.01" wire:model.live="fondo_iniziale">
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($tagli as $campo => $valore)
                    <div class="field mb-0">
                        <label class="label">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</label>
                        <input class="input" type="number" min="0" wire:model.live="pezzi.{{ $campo }}">
                    </div>
                @endforeach
            </div>
            <div class="field mt-4">
                <label class="label">Fondo trattenuto</label>
                <input class="input" type="number" step="0.01" wire:model.live="fondo_trattenuto">
            </div>
            <div class="field">
                <label class="label">Totale POS (da terminale)</label>
                <input class="input" type="number" step="0.01" wire:model.live="totale_pos">
            </div>
            <div class="field">
                <label class="label">Totale Z (registratore)</label>
                <input class="input" type="number" step="0.01" wire:model.live="totale_z">
            </div>
            <div class="field">
                <label class="label">Note</label>
                <textarea class="input" wire:model="note" rows="2"></textarea>
            </div>
            <button class="btn btn-primary" wire:click="salva">Salva chiusura</button>
        </div>

        <div class="panel">
            <h2 class="mt-0 mb-3 text-xl font-extrabold">Riconciliazione a tre vie</h2>
            @if ($riconciliazione)
                <p class="mt-0">Contante contato: <strong>{{ number_format($riconciliazione['contante_contato'], 2, ',', '.') }} €</strong></p>
                <table class="table">
                    <thead><tr><th></th><th>Contante</th><th>POS</th><th>Totale</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>Atteso (app)</td>
                            <td>{{ number_format($riconciliazione['atteso_contante'], 2, ',', '.') }}</td>
                            <td>{{ number_format($riconciliazione['atteso_pos'], 2, ',', '.') }}</td>
                            <td>{{ number_format($riconciliazione['atteso_totale'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Reale (fisico)</td>
                            <td>{{ number_format($riconciliazione['reale_contante'], 2, ',', '.') }}</td>
                            <td>{{ number_format($riconciliazione['reale_pos'], 2, ',', '.') }}</td>
                            <td>{{ number_format($riconciliazione['reale_contante'] + $riconciliazione['reale_pos'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Fiscale (Z)</td>
                            <td colspan="2">—</td>
                            <td>{{ number_format($riconciliazione['fiscale'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Δ</td>
                            <td>{{ number_format($riconciliazione['delta_contante'], 2, ',', '.') }}</td>
                            <td>{{ number_format($riconciliazione['delta_pos'], 2, ',', '.') }}</td>
                            <td>Δfisc {{ number_format($riconciliazione['delta_fiscale'], 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
                <p>Consegnato: <strong>{{ number_format($riconciliazione['contante_consegnato'], 2, ',', '.') }} €</strong>
                    · Incasso contante reale: <strong>{{ number_format($riconciliazione['incasso_contante_reale'], 2, ',', '.') }} €</strong></p>
            @endif
        </div>
    </div>
</div>
