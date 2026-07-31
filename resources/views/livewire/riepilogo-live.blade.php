<div wire:poll.8s>
    <h1>Riepilogo live</h1>
    @if (!$serata)
        <div class="alert alert-warn">Nessuna serata aperta.</div>
    @else
        <p>Serata {{ $serata->data->format('d/m/Y') }} · aggiornamento automatico</p>
        <div class="grid-3" style="margin-bottom:1rem">
            <div class="kpi"><div class="lbl">Coperti</div><div class="val">{{ $dati['coperti'] }}</div></div>
            <div class="kpi">
                <div class="lbl">Incasso</div>
                <div class="val">{{ number_format($dati['incasso'], 2, ',', '.') }} €</div>
                <div class="meta-small" style="margin-top:.25rem">di cui Bar: {{ number_format($dati['di_cui_bar'], 2, ',', '.') }} €</div>
            </div>
            <div class="kpi"><div class="lbl">Contante / POS</div><div class="val" style="font-size:1.2rem">{{ number_format($dati['contante'], 2, ',', '.') }} / {{ number_format($dati['pos'], 2, ',', '.') }}</div></div>
        </div>

        <div class="grid-2">
            <div class="panel">
                <h2>Vendite per piatto</h2>
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
            <div>
                <div class="panel" style="margin-bottom:1rem">
                    <h2>Per postazione</h2>
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
                    <h2>Annullate</h2>
                    @forelse ($dati['annullate'] as $a)
                        <div>#{{ $a->numero_progressivo }} — {{ $a->motivo_annullo }} ({{ number_format($a->totale, 2, ',', '.') }} €)</div>
                    @empty
                        <p>Nessuna.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
