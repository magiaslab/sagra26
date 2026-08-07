<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Edizione sagra"
        subtitle="Contenitore annuale delle serate. A fine sagra archivia l’edizione: i dati restano per i confronti futuri."
    />

    @if ($corrente)
        <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-sagra">Edizione aperta</div>
                    <h2 class="mt-1 mb-0 text-2xl font-semibold text-sagra-ink">{{ $corrente->etichetta() }}</h2>
                    <p class="mt-1 mb-0 text-sm text-sagra-muted">
                        Anno {{ $corrente->anno }}
                        · {{ $serateCount }} {{ $serateCount === 1 ? 'serata' : 'serate' }}
                        @if ($corrente->aperta_at)
                            · aperta il {{ $corrente->aperta_at->timezone(config('app.timezone'))->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
                <a
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer"
                    href="{{ route('gestione.serate', absolute: false) }}"
                >Vai alle serate →</a>
            </div>

            @if ($serataAperta)
                <x-ui.alert type="warn" class="mt-4 mb-0">
                    C’è una serata aperta ({{ $serataAperta->data->format('d/m/Y') }}).
                    Chiudila prima di archiviare l’edizione.
                </x-ui.alert>
            @elseif (! $chiediChiusura)
                <div class="mt-4">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark"
                        wire:click="preparaChiusura"
                    >Chiudi / archivia edizione</button>
                    <p class="mt-2 mb-0 text-xs text-sagra-muted">
                        Non cancella nulla: le serate restano in archivio. Poi potrai aprire l’edizione dell’anno successivo.
                    </p>
                </div>
            @endif

            @if ($chiediChiusura)
                <div class="mt-4 rounded-lg bg-sagra-warn-soft px-4 py-4 ring-2 ring-inset ring-sagra-warn/40">
                    <p class="m-0 text-base font-semibold text-sagra-warn">Conferma chiusura edizione {{ $corrente->anno }}</p>
                    <p class="mt-1 mb-3 text-sm text-sagra-ink">
                        Digita <strong>CHIUDI</strong> per archiviare. I dati non vengono eliminati.
                    </p>
                    <div class="mb-3">
                        <label class="mb-1 block text-sm font-medium">Note (opzionale)</label>
                        <input class="block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-sagra-line" type="text" wire:model="noteChiusura" placeholder="es. fine sagra 23/08">
                    </div>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-[10rem] flex-1">
                            <label class="mb-1 block text-sm font-medium">Conferma</label>
                            <input class="block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-sagra-line" type="text" wire:model="confermaChiusura" placeholder="CHIUDI" autocomplete="off">
                        </div>
                        <button type="button" class="inline-flex items-center rounded-md bg-sagra-danger px-3 py-2 text-sm font-semibold text-white" wire:click="chiudiEdizione">Archivia</button>
                        <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line" wire:click="annullaChiusura">Annulla</button>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <h2 class="mt-0 mb-1 text-xl font-semibold text-sagra-ink">Nessuna edizione aperta</h2>
            <p class="mt-0 mb-4 text-sm text-sagra-muted">Apri l’edizione dell’anno per poter gestire le serate e i report cumulativi.</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Anno</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-sagra-line" type="number" min="2000" max="2100" wire:model="nuovoAnno">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Nome</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-sagra-line" type="text" wire:model="nuovoNome">
                </div>
            </div>
            <button type="button" class="mt-4 inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="apriNuova">
                Apri nuova edizione
            </button>
        </div>
    @endif

    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
        <h2 class="mt-0 mb-3 text-lg font-semibold text-sagra-ink">Storico edizioni</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sagra-line text-sm">
                <thead>
                    <tr class="bg-sagra-softer text-left">
                        <th class="px-3 py-2 font-semibold">Anno</th>
                        <th class="px-3 py-2 font-semibold">Nome</th>
                        <th class="px-3 py-2 font-semibold">Stato</th>
                        <th class="px-3 py-2 font-semibold">Serate</th>
                        <th class="px-3 py-2 font-semibold">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sagra-line">
                @foreach ($storico as $e)
                    <tr>
                        <td class="px-3 py-2 tabular-nums font-semibold">{{ $e->anno }}</td>
                        <td class="px-3 py-2">{{ $e->etichetta() }}</td>
                        <td class="px-3 py-2">
                            @if ($e->isAperta())
                                <span class="rounded bg-sagra-soft px-1.5 py-0.5 text-xs font-semibold text-sagra-dark">aperta</span>
                            @else
                                <span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-semibold text-sagra-muted">archiviata</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 tabular-nums">{{ $e->serate()->count() }}</td>
                        <td class="px-3 py-2">
                            @if ($e->isArchiviata() && ! $corrente)
                                <button type="button" class="text-sm font-semibold text-sagra underline" wire:click="riapri({{ $e->id }})">Riapri</button>
                            @elseif ($e->isAperta())
                                <span class="text-xs text-sagra-muted">in uso</span>
                            @else
                                <span class="text-xs text-sagra-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 mb-0 text-xs text-sagra-muted">
            Il confronto anno per anno nei report arriverà a sagra terminata; per ora i cumulativi restano limitati all’edizione aperta.
        </p>
    </div>
</div>
