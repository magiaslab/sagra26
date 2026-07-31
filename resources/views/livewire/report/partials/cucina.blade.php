<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:baseline">
        <div>
            <h2 style="margin:0">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</h2>
            <div class="meta-small">Report cucina — {{ $serata->data->format('d/m/Y') }}</div>
        </div>
        <div>
            <span class="badge">Coperti stasera {{ $dati['copertiStasera'] }}</span>
            <span class="badge">Cumulato {{ $dati['copertiCum'] }}</span>
        </div>
    </div>

    @foreach ($dati['categorie'] as $cat)
        <h3 style="margin-top:1rem;border-bottom:2px solid #000">{{ $cat->nome }}</h3>
        <table class="table">
            <thead><tr><th>Piatto</th><th>Stasera</th><th>Cumulato</th><th></th></tr></thead>
            <tbody>
            @foreach ($cat->menuItems as $item)
                @php
                    $qS = $dati['stasera'][$item->id] ?? 0;
                    $qC = $dati['cumulato'][$item->id] ?? 0;
                    $st = $dati['stock'][$item->id] ?? null;
                    $esaurito = $st && $st->stock_residuo <= 0;
                @endphp
                <tr>
                    <td>{{ $item->nome }}</td>
                    <td>{{ $qS }}</td>
                    <td>{{ $qC }}</td>
                    <td>@if($esaurito)<span class="badge badge-esaurito">ESAURITO</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
</div>
