<div class="panel">
    @if (!empty($dati['errore']))
        <div class="alert alert-warn">{{ $dati['errore'] }}</div>
    @else
        @php $c = $dati['chiusura']; $ric = $dati['ric']; @endphp
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h2 style="margin:0">Consegna incassi</h2>
                <div>{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</div>
                <div>{{ $dati['punto']->nome }}</div>
            </div>
            <div class="badge badge-double" style="font-size:1.2rem;padding:.5rem .8rem">
                {{ $serata->data->format('d/m/Y') }}
            </div>
        </div>

        <h3>Riepilogo economico</h3>
        <table class="table">
            <tr><td>Incasso totale atteso</td><td>{{ number_format($ric['atteso_totale'], 2, ',', '.') }} €</td></tr>
            <tr><td>Atteso contante</td><td>{{ number_format($ric['atteso_contante'], 2, ',', '.') }} €</td></tr>
            <tr><td>Atteso POS</td><td>{{ number_format($ric['atteso_pos'], 2, ',', '.') }} €</td></tr>
            <tr><td>Fondo iniziale</td><td>{{ number_format($c->fondo_iniziale, 2, ',', '.') }} €</td></tr>
            <tr><td>Contante contato</td><td>{{ number_format($c->contante_contato, 2, ',', '.') }} €</td></tr>
            <tr><td>Fondo trattenuto</td><td>{{ number_format($c->fondo_trattenuto, 2, ',', '.') }} €</td></tr>
            <tr><td>Contante consegnato</td><td><strong>{{ number_format($c->contante_consegnato, 2, ',', '.') }} €</strong></td></tr>
            <tr><td>Totale POS (terminale)</td><td>{{ number_format($c->totale_pos, 2, ',', '.') }} €</td></tr>
            <tr><td>Totale Z</td><td>{{ number_format($c->totale_z, 2, ',', '.') }} €</td></tr>
            <tr><td>Δ contante</td><td>{{ number_format($ric['delta_contante'], 2, ',', '.') }} €</td></tr>
            <tr><td>Δ POS</td><td>{{ number_format($ric['delta_pos'], 2, ',', '.') }} €</td></tr>
            <tr><td>Δ fiscale</td><td>{{ number_format($ric['delta_fiscale'], 2, ',', '.') }} €</td></tr>
        </table>

        <h3>Dettaglio pezzi</h3>
        <table class="table">
            <thead><tr><th>Taglio</th><th>N°</th><th>Importo</th></tr></thead>
            <tbody>
            @foreach ($dati['tagli'] as $campo => $valore)
                @if ((int)$c->{$campo} > 0)
                    <tr>
                        <td>{{ number_format($valore, $valore < 1 ? 2 : 0, ',', '.') }} €</td>
                        <td>{{ $c->{$campo} }}</td>
                        <td>{{ number_format($c->{$campo} * $valore, 2, ',', '.') }} €</td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>

        <div class="grid-2" style="margin-top:2rem">
            <div>
                <div>Firma cassiere</div>
                <div class="linea" style="border-bottom:1px solid #000;min-height:40px;margin-top:1.5rem"></div>
            </div>
            <div>
                <div>Firma responsabile</div>
                <div class="linea" style="border-bottom:1px solid #000;min-height:40px;margin-top:1.5rem"></div>
            </div>
        </div>
    @endif
</div>
