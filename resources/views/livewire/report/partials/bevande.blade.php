<div class="panel">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="m-0 text-xl font-extrabold">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</h2>
            <div class="meta-small">Report BEVANDE — {{ $serata->data->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="grid-2 my-4">
        <div class="kpi">
            <div class="lbl">Stasera — Bar / Non Bar</div>
            <div class="val text-[1.2rem]">
                {{ number_format($dati['riepilogo']['bar_stasera'], 2, ',', '.') }} €
                /
                {{ number_format($dati['riepilogo']['non_bar_stasera'], 2, ',', '.') }} €
            </div>
        </div>
        <div class="kpi">
            <div class="lbl">Cumulato — Bar / Non Bar</div>
            <div class="val text-[1.2rem]">
                {{ number_format($dati['riepilogo']['bar_cumulato'], 2, ',', '.') }} €
                /
                {{ number_format($dati['riepilogo']['non_bar_cumulato'], 2, ',', '.') }} €
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Voce</th>
                <th>Bar</th>
                <th>Q.tà stasera</th>
                <th>Incasso stasera</th>
                <th>Q.tà cumulato</th>
                <th>Incasso cumulato</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($dati['items'] as $item)
            <tr>
                <td>{{ $item->nome }}@unless($item->attivo) <span class="badge">off</span>@endunless</td>
                <td>@if($item->bar)<span class="badge">BAR</span>@else — @endif</td>
                <td>{{ $dati['stasera_qta'][$item->id] ?? 0 }}</td>
                <td>{{ number_format($dati['stasera_incasso'][$item->id] ?? 0, 2, ',', '.') }} €</td>
                <td>{{ $dati['cumulato_qta'][$item->id] ?? 0 }}</td>
                <td>{{ number_format($dati['cumulato_incasso'][$item->id] ?? 0, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
