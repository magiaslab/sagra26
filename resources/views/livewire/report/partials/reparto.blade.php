<div class="report-sheet rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="m-0 text-xl font-semibold text-sagra-ink">{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</h2>
            <div class="report-meta text-xs text-sagra-muted">Report {{ \App\Models\MenuItem::etichettaArea($dati['area']) }} — {{ $serata->data->format('d/m/Y') }}</div>
        </div>
        <div class="flex flex-wrap gap-2 report-meta">
            <span class="text-xs font-medium text-sagra-muted">Coperti stasera {{ $dati['copertiStasera'] }}</span>
            <span class="text-xs font-medium text-sagra-muted">Cumulato {{ $dati['copertiCum'] }}</span>
        </div>
    </div>

    @forelse ($dati['categorie'] as $cat)
        <h3 class="mb-2 mt-4 border-b border-sagra-line pb-1 text-base font-semibold text-sagra-ink">{{ $cat->nome }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sagra-line text-sm">
                <thead>
                    <tr class="bg-sagra-softer">
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Piatto</th>
                        <th class="w-24 px-3 py-2 text-right font-semibold text-sagra-ink">Stasera</th>
                        <th class="w-24 px-3 py-2 text-right font-semibold text-sagra-ink">Cumulato</th>
                        <th class="w-28 px-3 py-2 text-left font-semibold text-sagra-ink"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sagra-line">
                @foreach ($cat->menuItems as $item)
                    @php
                        $qS = $dati['stasera'][$item->id] ?? 0;
                        $qC = $dati['cumulato'][$item->id] ?? 0;
                        $st = $dati['stock'][$item->id] ?? null;
                        $esaurito = $st && $st->stock_residuo <= 0;
                    @endphp
                    <tr>
                        <td class="px-3 py-2">{{ $item->nome }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $qS }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $qC }}</td>
                        <td class="px-3 py-2">@if($esaurito)<span class="rounded bg-sagra-danger-soft px-1.5 py-0.5 text-xs font-medium text-sagra-danger">ESAURITO</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="text-sm text-sagra-muted">Nessuna voce per questo reparto.</p>
    @endforelse
</div>
