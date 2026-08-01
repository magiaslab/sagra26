<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Serate"
        subtitle="Apertura, stock limitati e chiusura della serata"
    />

    @if ($errore)
        <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
    @endif

    @if ($serata)
        <div class="panel mb-4">
            <h2 class="mt-0 mb-3 text-xl font-extrabold">Serata aperta: {{ $serata->data->format('d/m/Y') }}</h2>
            @if (count($puntiCassaMancanti) > 0)
                <x-ui.alert type="warn" class="mt-2">
                    <strong>Chiusure cassa incomplete.</strong>
                    Questi punti cassa non hanno ancora una chiusura con <code class="rounded bg-white/60 px-1">chiusa_at</code>:
                    <ul class="mt-2 mb-0 ml-4 list-disc p-0">
                        @foreach ($puntiCassaMancanti as $nome)
                            <li>{{ $nome }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-3 mb-2">Puoi chiudere comunque la serata, ma i totali di cassa resteranno incompleti.</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <button class="btn btn-danger" wire:click="forzaChiusura">Chiudi comunque</button>
                        <button class="btn" wire:click="annullaChiusura">Annulla</button>
                        <a class="btn" href="{{ route('gestione.chiusura', absolute: false) }}">Vai a chiusura cassa</a>
                    </div>
                </x-ui.alert>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    <button class="btn btn-danger" wire:click="chiudi">Chiudi serata</button>
                    <a class="btn" href="{{ route('gestione.chiusura', absolute: false) }}">Vai a chiusura cassa</a>
                </div>
            @endif
        </div>
    @else
        <div class="panel mb-4">
            <h2 class="mt-0 mb-3 text-xl font-extrabold">Apri nuova serata</h2>
            <div class="field">
                <label class="label">Data</label>
                <input class="input max-w-[220px]" type="date" wire:model="data">
            </div>
            <div class="field">
                <label class="label">Note</label>
                <input class="input" type="text" wire:model="note">
            </div>

            <h3 class="mt-4 mb-2 text-base font-extrabold">Stock limitati</h3>
            @foreach ($limitati as $item)
                <div class="field flex flex-wrap items-center gap-2">
                    <label class="min-w-[220px] text-sm font-bold">{{ $item->nome }}</label>
                    <input class="input max-w-[120px]" type="number" min="0" wire:model="stockOverrides.{{ $item->id }}">
                </div>
            @endforeach

            <h3 class="mt-4 mb-2 text-base font-extrabold">Fondo iniziale per punto cassa</h3>
            @foreach ($punti as $punto)
                <div class="field flex flex-wrap items-center gap-2">
                    <label class="min-w-[220px] text-sm font-bold">{{ $punto->nome }}</label>
                    <input class="input max-w-[140px]" type="number" step="0.01" min="0" wire:model="fondiIniziali.{{ $punto->id }}" placeholder="obbligatorio">
                </div>
            @endforeach

            <button class="btn btn-primary" wire:click="apri">Apri serata</button>
        </div>
    @endif

    <div class="panel">
        <h2 class="mt-0 mb-3 text-xl font-extrabold">Storico</h2>
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
