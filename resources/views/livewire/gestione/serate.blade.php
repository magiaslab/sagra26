<div>
    <h1>Serate</h1>
    @if ($errore)
        <div class="alert alert-danger">{{ $errore }}</div>
    @endif

    @if ($serata)
        <div class="panel" style="margin-bottom:1rem">
            <h2>Serata aperta: {{ $serata->data->format('d/m/Y') }}</h2>
            <button class="btn btn-danger" wire:click="chiudi" wire:confirm="Chiudere la serata?">Chiudi serata</button>
            <a class="btn" href="{{ route('gestione.chiusura') }}">Vai a chiusura cassa</a>
        </div>
    @else
        <div class="panel" style="margin-bottom:1rem">
            <h2>Apri nuova serata</h2>
            <div class="field">
                <label class="label">Data</label>
                <input class="input" type="date" wire:model="data" style="max-width:220px">
            </div>
            <div class="field">
                <label class="label">Note</label>
                <input class="input" type="text" wire:model="note">
            </div>

            <h3>Stock limitati</h3>
            @foreach ($limitati as $item)
                <div class="field" style="display:flex;gap:.5rem;align-items:center">
                    <label style="min-width:220px">{{ $item->nome }}</label>
                    <input class="input" type="number" min="0" wire:model="stockOverrides.{{ $item->id }}" style="max-width:120px">
                </div>
            @endforeach

            <h3>Fondo iniziale per punto cassa</h3>
            @foreach ($punti as $punto)
                <div class="field" style="display:flex;gap:.5rem;align-items:center">
                    <label style="min-width:220px">{{ $punto->nome }}</label>
                    <input class="input" type="number" step="0.01" min="0" wire:model="fondiIniziali.{{ $punto->id }}" style="max-width:140px" placeholder="obbligatorio">
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
