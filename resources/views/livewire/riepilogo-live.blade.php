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
            <div class="rounded-md border border-sagra-line bg-white p-4 shadow-sm">
                <div class="text-sm font-bold uppercase tracking-wide text-sagra-muted">Coperti</div>
                <div class="text-3xl font-extrabold tabular-nums text-sagra-dark">{{ $dati['coperti'] }}</div>
            </div>
            <div class="rounded-md border border-sagra-line bg-white p-4 shadow-sm">
                <div class="text-sm font-bold uppercase tracking-wide text-sagra-muted">Incasso</div>
                <div class="text-3xl font-extrabold tabular-nums text-sagra-dark">{{ number_format($dati['incasso'], 2, ',', '.') }} €</div>
                <div class="mt-1 text-sm text-sagra-muted">di cui Bar: {{ number_format($dati['di_cui_bar'], 2, ',', '.') }} €</div>
            </div>
            <div class="rounded-md border border-sagra-line bg-white p-4 shadow-sm">
                <div class="text-sm font-bold uppercase tracking-wide text-sagra-muted">Contante / POS</div>
                <div class="text-xl font-extrabold tabular-nums text-sagra-dark">{{ number_format($dati['contante'], 2, ',', '.') }} / {{ number_format($dati['pos'], 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="panel">
                <h2 class="mt-0 mb-3 text-xl font-extrabold">Vendite per piatto</h2>
                <table class="table">
                    <thead><tr><th>Piatto</th><th>Q.tà</th><th>Incasso</th></tr></thead>
                    <tbody>
                    @foreach ($dati['per_piatto'] as $r)
                        <tr>
                            <td>{{ $r->menuItem->nome }}</td>
                            <td>{{ $r->qta }}</td>
                            <td>{{ number_format($r->incasso, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-4">
                <div class="panel">
                    <h2 class="mt-0 mb-3 text-xl font-extrabold">Per postazione</h2>
                    <table class="table">
                        <thead><tr><th>Postazione</th><th>N°</th><th>Totale</th></tr></thead>
                        <tbody>
                        @foreach ($dati['per_postazione'] as $p)
                            <tr>
                                <td>{{ $p['nome'] }}</td>
                                <td>{{ $p['n'] }}</td>
                                <td>{{ number_format($p['totale'], 2, ',', '.') }} €</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="panel">
                    <h2 class="mt-0 mb-3 text-xl font-extrabold">Annullate</h2>
                    @forelse ($dati['annullate'] as $a)
                        <div>#{{ $a->numero_progressivo }} — {{ $a->motivo_annullo }} ({{ number_format($a->totale, 2, ',', '.') }} €)</div>
                    @empty
                        <p class="m-0">Nessuna.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
