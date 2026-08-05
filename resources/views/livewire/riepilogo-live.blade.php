<div wire:poll.8s>
    <x-gestione.page-header
        title="Riepilogo live"
        :subtitle="$serata ? ('Serata '.$serata->data->format('d/m/Y').' · aggiornamento automatico') : 'Nessuna serata aperta'"
    />

    @if (!$serata)
        <x-ui.alert type="warn">Nessuna serata aperta.</x-ui.alert>
    @else
        @if ($dati['correzioni_per_postazione']->isNotEmpty())
            <p class="mb-4 text-sm text-sagra-muted">
                Correzioni oggi:
                {{ $dati['correzioni_per_postazione']->map(fn ($c) => $c['nome'].' '.$c['n'])->implode(' · ') }}
            </p>
        @endif
        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-sagra-line/80">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Coperti</div>
                <div class="text-3xl font-bold tabular-nums text-sagra-dark">{{ $dati['coperti'] }}</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-sagra-line/80">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Incasso</div>
                <div class="text-3xl font-bold tabular-nums text-sagra-dark">{{ number_format($dati['incasso'], 2, ',', '.') }} €</div>
                <div class="mt-1 text-sm text-sagra-muted">di cui Bar: {{ number_format($dati['di_cui_bar'], 2, ',', '.') }} €</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-sagra-line/80">
                <div class="text-xs font-medium uppercase tracking-wide text-sagra-muted">Contante / POS</div>
                <div class="text-xl font-bold tabular-nums text-sagra-dark">{{ number_format($dati['contante'], 2, ',', '.') }} / {{ number_format($dati['pos'], 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Vendite per piatto</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sagra-line text-sm">
                        <thead>
                            <tr class="bg-sagra-softer">
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Piatto</th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Q.tà</th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Incasso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sagra-line">
                        @foreach ($dati['per_piatto'] as $r)
                            <tr>
                                <td class="px-3 py-2">{{ $r->menuItem->nome }}</td>
                                <td class="px-3 py-2">{{ $r->qta }}</td>
                                <td class="px-3 py-2">{{ number_format($r->incasso, 2, ',', '.') }} €</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                    <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Comande per postazione</h2>
                    <p class="mb-3 text-xs text-sagra-muted">Quante comande ha emesso ciascuna cassa (incasso al netto di omaggi e sospesi aperti).</p>
                    @if ($dati['per_postazione']->isEmpty())
                        <p class="m-0 text-sm text-sagra-muted">Nessuna comanda ancora.</p>
                    @else
                        <div class="mb-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
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
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                    <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Annullate</h2>
                    @forelse ($dati['annullate'] as $a)
                        <div class="text-sm text-sagra-ink">#{{ $a->numero_progressivo }} — {{ $a->motivo_annullo }} ({{ number_format($a->totale, 2, ',', '.') }} €)</div>
                    @empty
                        <p class="m-0 text-sm text-sagra-muted">Nessuna.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
