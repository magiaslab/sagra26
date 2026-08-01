<div class="panel">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="m-0 text-xl font-extrabold">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</h2>
            <div class="meta-small">Report {{ strtoupper($dati['area']) }} — {{ $serata->data->format('d/m/Y') }}</div>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <span class="badge">Coperti stasera {{ $dati['copertiStasera'] }}</span>
            <span class="badge">Cumulato {{ $dati['copertiCum'] }}</span>
        </div>
    </div>

    @forelse ($dati['categorie'] as $cat)
        <h3 class="mt-4 mb-2 border-b-2 border-sagra-ink pb-1 text-base font-extrabold">{{ $cat->nome }}</h3>
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
    @empty
        <p>Nessuna voce per questo reparto.</p>
    @endforelse
</div>
