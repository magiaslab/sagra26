<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Serate"
        subtitle="Apertura, stock limitati e chiusura della serata"
    />

    @if ($errore)
        <div class="alert alert-danger">{{ $errore }}</div>
    @endif

    @if ($serata)
        <div class="panel panel-stack">
            <h2>Serata aperta: {{ $serata->data->format('d/m/Y') }}</h2>
            @if (count($puntiCassaMancanti) > 0)
                <div class="alert alert-warn mt-tight">
                    <strong>Chiusure cassa incomplete.</strong>
                    Questi punti cassa non hanno ancora una chiusura con <code>chiusa_at</code>:
                    <ul class="list-plain">
                        @foreach ($puntiCassaMancanti as $nome)
                            <li>{{ $nome }}</li>
                        @endforeach
                    </ul>
                    <p class="alert-lead">Puoi chiudere comunque la serata, ma i totali di cassa resteranno incompleti.</p>
                    <div class="stack-gap">
                        <button class="btn btn-danger" wire:click="forzaChiusura">Chiudi comunque</button>
                        <button class="btn" wire:click="annullaChiusura">Annulla</button>
                        <a class="btn" href="{{ route('gestione.chiusura', absolute: false) }}">Vai a chiusura cassa</a>
                    </div>
                </div>
            @else
                <div class="stack-gap">
                    <button class="btn btn-danger" wire:click="chiudi">Chiudi serata</button>
                    <a class="btn" href="{{ route('gestione.chiusura', absolute: false) }}">Vai a chiusura cassa</a>
                </div>
            @endif
        </div>
    @else
        <div class="panel panel-stack">
            <h2>Apri nuova serata</h2>
            <div class="field">
                <label class="label">Data</label>
                <input class="input input-date" type="date" wire:model="data">
            </div>
            <div class="field">
                <label class="label">Note</label>
                <input class="input" type="text" wire:model="note">
            </div>

            <h3>Stock limitati</h3>
            @foreach ($limitati as $item)
                <div class="field field-row">
                    <label class="label-fixed">{{ $item->nome }}</label>
                    <input class="input input-stock" type="number" min="0" wire:model="stockOverrides.{{ $item->id }}">
                </div>
            @endforeach

            <h3>Fondo iniziale per punto cassa</h3>
            @foreach ($punti as $punto)
                <div class="field field-row">
                    <label class="label-fixed">{{ $punto->nome }}</label>
                    <input class="input input-narrow" type="number" step="0.01" min="0" wire:model="fondiIniziali.{{ $punto->id }}" placeholder="obbligatorio">
                </div>
            @endforeach

            <button class="btn btn-primary" wire:click="apri">Apri serata</button>
        </div>
    @endif

    <div class="panel">
        <h2>Storico</h2>
        <table class="table">
            <thead><tr><th>Data</th><th>Stato</th><th>Note</th></tr></thead>
            <tbody>
            @foreach ($storico as $s)
                <tr>
                    <td>{{ $s->data->format('d/m/Y') }}</td>
                    <td><span class="badge">{{ $s->stato }}</span></td>
                    <td>{{ $s->note }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
