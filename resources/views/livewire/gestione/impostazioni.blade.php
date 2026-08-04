<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Impostazioni"
        subtitle="Intestazione, PIN, postazioni e punti cassa"
    />

    @if ($errore !== '')
        <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Intestazione / sistema</h2>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Nome</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="intestazione_nome">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Anno</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="intestazione_anno">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Sottotitolo</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="intestazione_sottotitolo">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">PIN gestione</label>
                <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="pin_gestione">
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Soglia alert stock (cassa)</label>
                <input class="block w-full max-w-[140px] rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" min="0" wire:model="stock_soglia_alert">
                <p class="mt-1 text-xs text-sagra-muted">Avviso «quasi esaurito» quando il residuo è ≤ questo valore.</p>
            </div>
            <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="salvaIntestazione">Salva</button>
        </div>

        <div class="flex flex-col gap-4">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Postazioni</h2>
                <ul class="mb-3 mt-0 space-y-2 p-0 text-sm text-sagra-ink">
                    @foreach ($postazioni as $p)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $p->nome }}</span>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-danger ring-1 ring-sagra-danger/40 hover:bg-sagra-danger-soft"
                                wire:click="eliminaPostazione({{ $p->id }})"
                            >Elimina</button>
                        </li>
                    @endforeach
                </ul>
                <div class="flex flex-wrap items-stretch gap-2">
                    <input class="block min-w-0 flex-1 basis-40 rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="nuovaPostazione" placeholder="Nuova postazione">
                    <button class="inline-flex shrink-0 items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="aggiungiPostazione">Aggiungi</button>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Punti cassa</h2>
                <ul class="mb-3 mt-0 space-y-2 p-0 text-sm text-sagra-ink">
                    @foreach ($punti as $p)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $p->nome }} @unless($p->attivo)(disattivo)@endunless</span>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-danger ring-1 ring-sagra-danger/40 hover:bg-sagra-danger-soft"
                                wire:click="eliminaPunto({{ $p->id }})"
                            >Elimina</button>
                        </li>
                    @endforeach
                </ul>
                <div class="flex flex-wrap items-stretch gap-2">
                    <input class="block min-w-0 flex-1 basis-40 rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="nuovoPunto" placeholder="Nuovo punto cassa">
                    <button class="inline-flex shrink-0 items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="aggiungiPunto">Aggiungi</button>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Mappatura postazione → punto cassa</h2>
                <div class="mb-3">
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="mapPostazione">
                        <option value="">Postazione…</option>
                        @foreach ($postazioni as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="mapPunto">
                        <option value="">Punto cassa…</option>
                        @foreach ($punti as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="date" wire:model="mapValidoDa">
                </div>
                <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="mappa">Salva mappatura</button>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-sagra-line text-sm">
                        <thead>
                            <tr class="bg-sagra-softer">
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Da</th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Postazione</th>
                                <th class="px-3 py-2 text-left font-semibold text-sagra-ink">Punto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sagra-line">
                        @foreach ($mappature as $m)
                            <tr>
                                <td class="px-3 py-2">{{ $m->valido_da->format('d/m/Y') }}</td>
                                <td class="px-3 py-2">{{ $m->postazione->nome }}</td>
                                <td class="px-3 py-2">{{ $m->puntoCassa->nome }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
