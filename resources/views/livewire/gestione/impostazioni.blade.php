<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Impostazioni"
        subtitle="Intestazione, PIN, postazioni e punti cassa"
    />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="panel">
            <h2 class="mt-0 mb-3 text-xl font-extrabold">Intestazione / sistema</h2>
            <div class="field"><label class="label">Nome</label><input class="input" wire:model="intestazione_nome"></div>
            <div class="field"><label class="label">Anno</label><input class="input" wire:model="intestazione_anno"></div>
            <div class="field"><label class="label">Sottotitolo</label><input class="input" wire:model="intestazione_sottotitolo"></div>
            <div class="field"><label class="label">PIN gestione</label><input class="input" wire:model="pin_gestione"></div>
            <button class="btn btn-primary" wire:click="salvaIntestazione">Salva</button>
        </div>

        <div class="flex flex-col gap-4">
            <div class="panel">
                <h2 class="mt-0 mb-3 text-xl font-extrabold">Postazioni</h2>
                <ul class="mb-3 mt-0 list-disc pl-5">
                    @foreach ($postazioni as $p)
                        <li>{{ $p->nome }}</li>
                    @endforeach
                </ul>
                <div class="flex flex-wrap items-stretch gap-2">
                    <input class="input min-w-0 flex-1 basis-40" wire:model="nuovaPostazione" placeholder="Nuova postazione">
                    <button class="btn shrink-0" wire:click="aggiungiPostazione">Aggiungi</button>
                </div>
            </div>

            <div class="panel">
                <h2 class="mt-0 mb-3 text-xl font-extrabold">Punti cassa</h2>
                <ul class="mb-3 mt-0 list-disc pl-5">
                    @foreach ($punti as $p)
                        <li>{{ $p->nome }} @unless($p->attivo)(disattivo)@endunless</li>
                    @endforeach
                </ul>
                <div class="flex flex-wrap items-stretch gap-2">
                    <input class="input min-w-0 flex-1 basis-40" wire:model="nuovoPunto" placeholder="Nuovo punto cassa">
                    <button class="btn shrink-0" wire:click="aggiungiPunto">Aggiungi</button>
                </div>
            </div>

            <div class="panel">
                <h2 class="mt-0 mb-3 text-xl font-extrabold">Mappatura postazione → punto cassa</h2>
                <div class="field">
                    <select class="input" wire:model="mapPostazione">
                        <option value="">Postazione…</option>
                        @foreach ($postazioni as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <select class="input" wire:model="mapPunto">
                        <option value="">Punto cassa…</option>
                        @foreach ($punti as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                    </select>
                </div>
                <div class="field"><input class="input" type="date" wire:model="mapValidoDa"></div>
                <button class="btn" wire:click="mappa">Salva mappatura</button>

                <table class="table mt-4">
                    <thead><tr><th>Da</th><th>Postazione</th><th>Punto</th></tr></thead>
                    <tbody>
                    @foreach ($mappature as $m)
                        <tr>
                            <td>{{ $m->valido_da->format('d/m/Y') }}</td>
                            <td>{{ $m->postazione->nome }}</td>
                            <td>{{ $m->puntoCassa->nome }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
