<div wire:poll.8s>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
        }
    </style>

    <x-gestione.page-header
        class="print:hidden"
        title="Riepilogo live"
        :subtitle="$serata ? ('Serata '.$serata->data->format('d/m/Y').' · aggiornamento automatico') : 'Nessuna serata aperta'"
    >
        @if ($serata)
            <x-slot:actions>
                <button
                    class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark"
                    type="button"
                    onclick="window.print()"
                >Stampa veloce</button>
            </x-slot:actions>
        @endif
    </x-gestione.page-header>

    @if (!$serata)
        <x-ui.alert type="warn">Nessuna serata aperta.</x-ui.alert>
    @else
        <div class="report-sheet report-print">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-sagra-line pb-3">
                <div class="min-w-0">
                    <h2 class="m-0 text-xl font-semibold text-sagra-ink">Riepilogo live</h2>
                    <div class="report-meta text-sm text-sagra-ink">
                        {{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}
                    </div>
                    <div class="report-meta text-sm text-sagra-muted">
                        Serata {{ $serata->data->format('d/m/Y') }}
                        @if ($dati['correzioni_per_postazione']->isNotEmpty())
                            · Correzioni:
                            {{ $dati['correzioni_per_postazione']->map(fn ($c) => $c['nome'].' '.$c['n'])->implode(' · ') }}
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-semibold tabular-nums text-sagra-ink">
                        Stampato alle {{ $stampatoAt->format('H:i') }}
                    </div>
                    <div class="report-meta text-sm text-sagra-muted">{{ $stampatoAt->format('d/m/Y') }}</div>
                </div>
            </div>

            @if ($dati['correzioni_per_postazione']->isNotEmpty())
                <p class="mb-4 text-sm text-sagra-muted print:hidden">
                    Correzioni oggi:
                    {{ $dati['correzioni_per_postazione']->map(fn ($c) => $c['nome'].' '.$c['n'])->implode(' · ') }}
                </p>
            @endif

            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3 print:grid-cols-3 print:gap-2">
                <div class="report-kpi rounded-lg bg-white p-4 shadow-sm ring-1 ring-sagra-line/80">
                    <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Coperti</div>
                    <div class="text-3xl font-bold tabular-nums text-sagra-dark print:text-2xl">{{ $dati['coperti'] }}</div>
                </div>
                <div class="report-kpi rounded-lg bg-white p-4 shadow-sm ring-1 ring-sagra-line/80">
                    <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Incasso</div>
                    <div class="text-3xl font-bold tabular-nums text-sagra-dark print:text-2xl">{{ number_format($dati['incasso'], 2, ',', '.') }} €</div>
                    <div class="mt-1 text-sm text-sagra-muted">di cui Bar: {{ number_format($dati['di_cui_bar'], 2, ',', '.') }} €</div>
                </div>
                <div class="report-kpi rounded-lg bg-white p-4 shadow-sm ring-1 ring-sagra-line/80">
                    <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Contante / POS</div>
                    <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ number_format($dati['contante'], 2, ',', '.') }} / {{ number_format($dati['pos'], 2, ',', '.') }}</div>
                    @if ($dati['omaggi'] > 0 || $dati['sospesi'] > 0)
                        <div class="mt-1 text-xs text-sagra-muted">
                            @if ($dati['omaggi'] > 0)
                                Omaggi {{ number_format($dati['omaggi'], 2, ',', '.') }} €
                            @endif
                            @if ($dati['omaggi'] > 0 && $dati['sospesi'] > 0)
                                ·
                            @endif
                            @if ($dati['sospesi'] > 0)
                                Sospesi {{ number_format($dati['sospesi'], 2, ',', '.') }} €
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 print:grid-cols-2 print:gap-3">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 print:p-0 print:shadow-none print:ring-0">
                    <h3 class="mb-3 mt-0 text-base font-semibold text-sagra-ink lg:text-xl">Vendite per piatto</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-sagra-line text-sm">
                            <thead>
                                <tr class="bg-sagra-softer">
                                    <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Piatto</th>
                                    <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Q.tà</th>
                                    <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Incasso</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sagra-line">
                            @forelse ($dati['per_piatto'] as $r)
                                <tr>
                                    <td class="px-3 py-2">{{ $r->menuItem->nome }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $r->qta }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r->incasso, 2, ',', '.') }} €</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-2 text-sagra-muted">Nessuna vendita.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex flex-col gap-4">
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 print:p-0 print:shadow-none print:ring-0">
                        <h3 class="mb-3 mt-0 text-base font-semibold text-sagra-ink lg:text-xl">Comande per postazione</h3>
                        <p class="mb-3 text-xs text-sagra-muted print:hidden">Quante comande ha emesso ciascuna cassa (incasso al netto di omaggi e sospesi aperti).</p>
                        @if ($dati['per_postazione']->isEmpty())
                            <p class="m-0 text-sm text-sagra-muted">Nessuna comanda ancora.</p>
                        @else
                            <div class="mb-3 grid grid-cols-1 gap-2 sm:grid-cols-2 print:hidden">
                                @foreach ($dati['per_postazione'] as $p)
                                    <div class="rounded-md bg-sagra-softer px-3 py-2.5 ring-1 ring-inset ring-sagra-line/70">
                                        <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">{{ $p['nome'] }}</div>
                                        <div class="mt-0.5 flex items-baseline justify-between gap-2">
                                            <span class="text-2xl font-bold tabular-nums text-sagra-dark">{{ $p['n'] }}</span>
                                            <span class="text-sm text-sagra-muted">comande</span>
                                            <span class="ml-auto font-mono text-sm font-semibold tabular-nums text-sagra-ink">{{ number_format($p['totale'], 2, ',', '.') }} €</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-sagra-line text-sm">
                                    <thead>
                                        <tr class="bg-sagra-softer">
                                            <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Postazione</th>
                                            <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Comande</th>
                                            <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Incasso</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-sagra-line">
                                    @foreach ($dati['per_postazione'] as $p)
                                        <tr>
                                            <td class="px-3 py-2 font-medium">{{ $p['nome'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono tabular-nums">{{ $p['n'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono tabular-nums">{{ number_format($p['totale'], 2, ',', '.') }} €</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 print:p-0 print:shadow-none print:ring-0">
                        <h3 class="mb-3 mt-0 text-base font-semibold text-sagra-ink lg:text-xl">Annullate</h3>
                        @forelse ($dati['annullate'] as $a)
                            <div class="text-sm text-sagra-ink">#{{ $a->numero_progressivo }} — {{ $a->motivo_annullo }} ({{ number_format($a->totale, 2, ',', '.') }} €)</div>
                        @empty
                            <p class="m-0 text-sm text-sagra-muted">Nessuna.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($autoPrint)
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 150);
            });
        </script>
    @endif
</div>
