<div class="panel">
    <h2>{{ $impostazioni->intestazione_nome }} — Economico</h2>
    <table class="table">
        <thead><tr><th>Serata</th><th>Contante</th><th>POS</th><th>Totale</th></tr></thead>
        <tbody>
        @foreach ($dati['righe'] as $r)
            <tr>
                <td>{{ $r['data'] }}</td>
                <td>{{ number_format($r['contante'], 2, ',', '.') }} €</td>
                <td>{{ number_format($r['pos'], 2, ',', '.') }} €</td>
                <td>{{ number_format($r['totale'], 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        <tr>
            <th>TOTALE</th>
            <th>{{ number_format($dati['tot_contante'], 2, ',', '.') }} €</th>
            <th>{{ number_format($dati['tot_pos'], 2, ',', '.') }} €</th>
            <th>{{ number_format($dati['totale'], 2, ',', '.') }} €</th>
        </tr>
        </tbody>
    </table>
    <p>Ripartizione: contante {{ $dati['pct_contante'] }}% · POS {{ $dati['pct_pos'] }}%</p>
</div>
