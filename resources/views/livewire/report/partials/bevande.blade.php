<div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="m-0 text-xl font-semibold text-sagra-ink">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</h2>
            <div class="text-xs text-sagra-muted">Report BEVANDE — {{ $serata->data->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="my-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Stasera — Bar / Non Bar</div>
            <div class="text-xl font-bold tabular-nums text-sagra-dark">
                {{ number_format($dati['riepilogo']['bar_stasera'], 2, ',', '.') }} €
                /
                {{ number_format($dati['riepilogo']['non_bar_stasera'], 2, ',', '.') }} €
            </div>
        </div>
        <div class="rounded-lg bg-sagra-softer px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Cumulato — Bar / Non Bar</div>
            <div class="text-xl font-bold tabular-nums text-sagra-dark">
                {{ number_format($dati['riepilogo']['bar_cumulato'], 2, ',', '.') }} €
                /
                {{ number_format($dati['riepilogo']['non_bar_cumulato'], 2, ',', '.') }} €
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sagra-line text-sm">
            <thead>
                <tr class="bg-sagra-softer">
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Voce</th>
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Bar</th>
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Q.tà stasera</th>
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Incasso stasera</th>
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Q.tà cumulato</th>
                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Incasso cumulato</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sagra-line">
            @foreach ($dati['items'] as $item)
                <tr>
                    <td class="px-3 py-2">{{ $item->nome }}@unless($item->attivo) <span class="text-xs font-medium text-sagra-muted">off</span>@endunless</td>
                    <td class="px-3 py-2">@if($item->bar)<span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-medium text-sagra">BAR</span>@else — @endif</td>
                    <td class="px-3 py-2">{{ $dati['stasera_qta'][$item->id] ?? 0 }}</td>
                    <td class="px-3 py-2">{{ number_format($dati['stasera_incasso'][$item->id] ?? 0, 2, ',', '.') }} €</td>
                    <td class="px-3 py-2">{{ $dati['cumulato_qta'][$item->id] ?? 0 }}</td>
                    <td class="px-3 py-2">{{ number_format($dati['cumulato_incasso'][$item->id] ?? 0, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
