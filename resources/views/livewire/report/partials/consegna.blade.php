<div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    @if (!empty($dati['errore']))
        <x-ui.alert type="warn">{{ $dati['errore'] }}</x-ui.alert>
    @else
        @php $c = $dati['chiusura']; $ric = $dati['ric']; @endphp
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="m-0 text-xl font-semibold text-sagra-ink">Consegna incassi</h2>
                <div class="text-sm text-sagra-ink">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</div>
                <div class="text-sm text-sagra-muted">{{ $dati['punto']->nome }}</div>
                @if (! $serata->isAperta())
                    <div class="mt-1 text-xs font-medium text-sagra-amber">Serata chiusa — foglio in sola lettura (ristampa ok). Per correzioni: Riapri serata.</div>
                @endif
            </div>
            <div class="text-lg font-semibold text-sagra-ink">
                {{ $serata->data->format('d/m/Y') }}
            </div>
        </div>

        <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Riepilogo economico</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sagra-line text-sm">
                <tbody class="divide-y divide-sagra-line">
                    <tr><td class="px-3 py-2">Incasso totale atteso</td><td class="px-3 py-2">{{ number_format($ric['atteso_totale'], 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Atteso contante</td><td class="px-3 py-2">{{ number_format($ric['atteso_contante'], 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Atteso POS</td><td class="px-3 py-2">{{ number_format($ric['atteso_pos'], 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Fondo iniziale</td><td class="px-3 py-2">{{ number_format($c->fondo_iniziale, 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Contante contato</td><td class="px-3 py-2">{{ number_format($c->contante_contato, 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Fondo trattenuto</td><td class="px-3 py-2">{{ number_format($c->fondo_trattenuto, 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Contante consegnato</td><td class="px-3 py-2"><strong>{{ number_format($c->contante_consegnato, 2, ',', '.') }} €</strong></td></tr>
                    <tr><td class="px-3 py-2">Totale POS (terminale)</td><td class="px-3 py-2">{{ number_format($c->totale_pos, 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Totale Z</td><td class="px-3 py-2">{{ number_format($c->totale_z, 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Δ contante</td><td class="px-3 py-2">{{ number_format($ric['delta_contante'], 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Δ POS</td><td class="px-3 py-2">{{ number_format($ric['delta_pos'], 2, ',', '.') }} €</td></tr>
                    <tr><td class="px-3 py-2">Δ fiscale</td><td class="px-3 py-2">{{ number_format($ric['delta_fiscale'], 2, ',', '.') }} €</td></tr>
                </tbody>
            </table>
        </div>

        <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Dettaglio pezzi</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sagra-line text-sm">
                <thead>
                    <tr class="bg-sagra-softer">
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Taglio</th>
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">N°</th>
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Importo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sagra-line">
                @foreach ($dati['tagli'] as $campo => $valore)
                    @if ((int)$c->{$campo} > 0)
                        <tr>
                            <td class="px-3 py-2">{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</td>
                            <td class="px-3 py-2">{{ $c->{$campo} }}</td>
                            <td class="px-3 py-2">{{ number_format($c->{$campo} * $valore, 2, ',', '.') }} €</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <div class="text-sm text-sagra-ink">Firma cassiere</div>
                <div class="mt-6 min-h-10 border-b border-black"></div>
            </div>
            <div>
                <div class="text-sm text-sagra-ink">Firma responsabile</div>
                <div class="mt-6 min-h-10 border-b border-black"></div>
            </div>
        </div>
    @endif
</div>
