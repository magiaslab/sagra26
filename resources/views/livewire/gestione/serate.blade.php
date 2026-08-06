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
        <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Serata aperta: {{ $serata->data->format('d/m/Y') }}</h2>
            @if (count($puntiCassaMancanti) > 0)
                <x-ui.alert type="warn" class="mt-2">
                    <strong>Chiusure cassa incomplete.</strong>
                    Questi punti cassa non hanno ancora una chiusura con <code class="rounded bg-white/60 px-1">chiusa_at</code>:
                    <ul class="mb-0 ml-4 mt-2 list-disc p-0">
                        @foreach ($puntiCassaMancanti as $nome)
                            <li>{{ $nome }}</li>
                        @endforeach
                    </ul>
                    <p class="mb-2 mt-3">Puoi chiudere comunque la serata, ma i totali di cassa resteranno incompleti.</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-danger ring-1 ring-sagra-danger/40 hover:bg-sagra-danger-soft" wire:click="forzaChiusura">Chiudi comunque</button>
                        <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="annullaChiusura">Annulla</button>
                        <a class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" href="{{ route('gestione.chiusura', absolute: false) }}">Vai a chiusura cassa</a>
                    </div>
                </x-ui.alert>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-danger ring-1 ring-sagra-danger/40 hover:bg-sagra-danger-soft" wire:click="chiudi">Chiudi serata</button>
                    <a class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" href="{{ route('gestione.chiusura', absolute: false) }}">Vai a chiusura cassa</a>
                </div>
            @endif
        </div>

        <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <h2 class="mb-1 mt-0 text-xl font-semibold text-sagra-ink">Stock in serata</h2>
            <p class="mb-3 text-sm text-sagra-muted">
                Correggi lo stock senza chiudere la serata: valori positivi aggiungono, negativi tolgono
                (non sotto zero). Soglia alert cassa: ≤ {{ $sogliaAlert }} residui (Impostazioni).
            </p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-sagra-line text-sm">
                    <thead>
                        <tr class="bg-sagra-softer text-left">
                            <th class="px-3 py-2 font-semibold">Voce</th>
                            <th class="px-3 py-2 font-semibold">Iniziale</th>
                            <th class="px-3 py-2 font-semibold">Residuo</th>
                            <th class="px-3 py-2 font-semibold">Correggi (±)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sagra-line">
                        @forelse ($stockSerata as $row)
                            <tr @class([
                                'bg-sagra-danger-soft/40' => (int) $row->stock_residuo <= 0,
                                'bg-amber-50' => (int) $row->stock_residuo > 0 && (int) $row->stock_residuo <= $sogliaAlert,
                            ])>
                                <td class="px-3 py-2 font-medium text-sagra-ink">{{ $row->menuItem?->nome ?? '#' }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->stock_iniziale }}</td>
                                <td class="px-3 py-2 tabular-nums font-semibold">{{ $row->stock_residuo }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input class="block w-24 rounded-md bg-white px-2 py-1.5 text-sm shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="1" wire:model="rifornimenti.{{ $row->menu_item_id }}" placeholder="es. -5">
                                        <button type="button" class="inline-flex items-center rounded-md bg-sagra px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-sagra-dark" wire:click="rifornisciStock({{ $row->menu_item_id }})">Applica</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-sagra-muted">Nessuna voce a stock limitato.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Apri nuova serata</h2>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Data</label>
                <input class="block max-w-[220px] rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="date" wire:model="data">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Note</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="text" wire:model="note">
            </div>

            <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Stock limitati</h3>
            @foreach ($limitati as $item)
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <label class="min-w-[220px] text-sm font-medium text-sagra-ink">{{ $item->nome }}</label>
                    <input class="block max-w-[120px] rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" min="0" wire:model="stockOverrides.{{ $item->id }}">
                </div>
            @endforeach

            <h3 class="mb-2 mt-4 text-base font-semibold text-sagra-ink">Fondo iniziale per punto cassa</h3>
            <p class="mb-3 text-sm text-sagra-muted">Suggerito dal fondo trattenuto (conteggio pezzi) della chiusura precedente.</p>
            @foreach ($punti as $punto)
                <div class="mb-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="min-w-[220px] text-sm font-medium text-sagra-ink">{{ $punto->nome }}</label>
                        <input class="block max-w-[140px] rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" min="0" wire:model="fondiIniziali.{{ $punto->id }}" placeholder="obbligatorio">
                    </div>
                    @if (! empty($fondiDescrizione[$punto->id] ?? null) && ($fondiDescrizione[$punto->id] ?? '') !== 'nessun pezzo')
                        <p class="mb-0 mt-1 pl-0 text-xs text-sagra-muted sm:pl-[228px]">Pezzi lasciati: {{ $fondiDescrizione[$punto->id] }}</p>
                    @endif
                </div>
            @endforeach

            <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="apri">Apri serata</button>
        </div>
    @endif

    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
        <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Storico</h2>
        <p class="mb-3 text-sm text-sagra-muted">
            Con serata chiusa puoi comunque ristampare i report.
            Per correggere comande usa <strong>Riapri</strong> qui; per i conteggi cassa puoi anche usare
            <a class="font-semibold underline" href="{{ route('gestione.chiusura', absolute: false) }}">Chiusura → Riapri per correggere</a>.
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sagra-line text-sm">
                <thead>
                    <tr class="bg-sagra-softer">
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Data</th>
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Stato</th>
                        <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Note</th>
                        <th class="px-3 py-2 text-right font-semibold text-sagra-ink">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sagra-line">
                @foreach ($storico as $s)
                    <tr>
                        <td class="px-3 py-2">{{ $s->data->format('d/m/Y') }}</td>
                        <td class="px-3 py-2"><span class="text-xs font-medium text-sagra-muted">{{ $s->stato }}</span></td>
                        <td class="px-3 py-2">{{ $s->note }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($s->stato === 'chiusa')
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md bg-sagra px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-sagra-dark disabled:opacity-50"
                                        wire:click="riapri({{ $s->id }})"
                                        @disabled((bool) $serata)
                                        title="{{ $serata ? 'Chiudi prima la serata aperta' : 'Riapri per correzioni' }}"
                                    >Riapri</button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-sagra-danger shadow-sm ring-1 ring-inset ring-sagra-danger/40 hover:bg-sagra-danger-soft"
                                        wire:click="elimina({{ $s->id }})"
                                        wire:confirm="Eliminare la serata del {{ $s->data->format('d/m/Y') }} e tutte le comande/chiusure collegate? Operazione irreversibile."
                                    >Elimina</button>
                                @else
                                    <span class="text-xs text-sagra-muted">in corso</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
