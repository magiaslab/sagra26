<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Menù" subtitle="Voci, prezzi, stock e aree stampa">
        <x-slot:actions>
            <a class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" href="{{ route('gestione.menu.facsimile', ['print' => 1], absolute: false) }}" target="_blank">Stampa facsimile</a>
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
        <h2 class="mb-1 mt-0 text-xl font-semibold text-sagra-ink">Comunicazione sulle comande</h2>
        <p class="mb-3 mt-0 text-sm text-sagra-muted">Testo libero stampato in grassetto sul talloncino Cliente, tra l’elenco piatti e il totale. Lascia vuoto per non mostrare nulla.</p>
        <textarea
            class="mb-3 block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra"
            rows="3"
            maxlength="2000"
            wire:model="comunicazione_comanda"
            placeholder="Es. Grazie e buon appetito! · Ritirare al banco bevande…"
        ></textarea>
        @error('comunicazione_comanda')
            <x-ui.alert type="danger" class="mb-3">{{ $message }}</x-ui.alert>
        @enderror
        <button type="button" class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="salvaComunicazione">Salva comunicazione</button>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="flex flex-col gap-4">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">{{ $editingId ? 'Modifica voce' : 'Nuova voce' }}</h2>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Nome</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="nome">
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Prezzo</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" step="0.01" wire:model="prezzo">
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Categoria</label>
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="categoria_id">
                        @foreach ($categorie as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Area stampa (override)</label>
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="area_stampa">
                        <option value="">(ereditata dalla categoria)</option>
                        <option value="cucina_1">cucina 1</option>
                        <option value="cucina_2">cucina 2</option>
                        <option value="griglia">griglia</option>
                        <option value="cliente">cliente</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Stock default (vuoto = illimitato)</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" wire:model="stock_default">
                </div>
                <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <label class="text-sm font-medium text-sagra-ink"><input type="checkbox" wire:model="attivo"> Attivo</label>
                    <label class="text-sm font-medium text-sagra-ink"><input type="checkbox" wire:model="piatto_del_giorno"> Piatto del giorno</label>
                    <label class="text-sm font-medium text-sagra-ink"><input type="checkbox" wire:model="bar"> Voce Bar</label>
                    <label class="text-sm font-medium text-sagra-ink"><input type="checkbox" wire:model="congelato"> Possibile congelato (*)</label>
                    <label class="text-sm font-medium text-sagra-ink"><input type="checkbox" wire:model="is_coperto"> Voce Coperto</label>
                </div>
                @error('is_coperto')
                    <x-ui.alert type="danger" class="mt-2">{{ $message }}</x-ui.alert>
                @enderror
                <div class="mt-4 flex flex-wrap gap-2">
                    <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="salva">Salva</button>
                    <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="nuovo">Nuova</button>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
                <h2 class="mb-3 mt-0 text-xl font-semibold text-sagra-ink">Nuova categoria</h2>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Nome</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" placeholder="Nome" wire:model="catNome">
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Area stampa</label>
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model="catArea">
                        <option value="cliente">cliente</option>
                        <option value="cucina_1">cucina 1</option>
                        <option value="cucina_2">cucina 2</option>
                        <option value="griglia">griglia</option>
                    </select>
                </div>
                <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="creaCategoria">Crea categoria</button>
            </div>
        </div>

        <div class="max-h-[80vh] overflow-auto rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80">
            @foreach ($categorie as $cat)
                <h3 class="mb-1.5 mt-0 text-base font-semibold text-sagra-ink">
                    {{ $cat->nome }}
                    <span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-medium text-sagra">{{ $cat->area_stampa }}</span>
                </h3>
                <div class="mb-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-sagra-line text-sm">
                        <tbody class="divide-y divide-sagra-line">
                        @foreach ($cat->menuItems as $item)
                            <tr @class(['opacity-50' => ! $item->attivo])>
                                <td class="px-2 py-2">{{ $item->ordinamento }}</td>
                                <td class="px-2 py-2">
                                    <strong>{{ $item->nome }}</strong>
                                    @if ($item->area_stampa)<span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-medium text-sagra">{{ $item->area_stampa }}</span>@endif
                                    @if ($item->bar)<span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-medium text-sagra">BAR</span>@endif
                                    @if ($item->congelato)<span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-medium text-sagra">*</span>@endif
                                    @if ($item->is_coperto)<span class="rounded bg-sagra-softer px-1.5 py-0.5 text-xs font-medium text-sagra">COPERTI</span>@endif
                                    @if ($item->stock_default !== null)<span class="text-sm text-sagra-muted"> stock {{ $item->stock_default }}</span>@endif
                                </td>
                                <td class="px-2 py-2">{{ number_format($item->prezzo, 2, ',', '.') }} €</td>
                                <td class="whitespace-nowrap px-2 py-2">
                                    <button class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="sposta({{ $item->id }}, 'up')">↑</button>
                                    <button class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="sposta({{ $item->id }}, 'down')">↓</button>
                                    <button class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="edit({{ $item->id }})">Mod</button>
                                    @if ($item->attivo)
                                        <button
                                            class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-danger ring-1 ring-sagra-danger/40 hover:bg-sagra-danger-soft"
                                            wire:click="disattiva({{ $item->id }})"
                                            wire:confirm="Disattivare «{{ $item->nome }}»? Non comparirà più in cassa."
                                        >Off</button>
                                    @else
                                        <button class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="attiva({{ $item->id }})">On</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</div>
