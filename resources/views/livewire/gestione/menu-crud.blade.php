<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Menù" subtitle="Voci, prezzi, stock e aree stampa">
        <x-slot:actions>
            <a class="btn" href="{{ route('gestione.menu.facsimile', ['print' => 1], absolute: false) }}" target="_blank">Stampa facsimile</a>
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="grid-2">
        <div class="stack-panels">
            <div class="panel">
                <h2>{{ $editingId ? 'Modifica voce' : 'Nuova voce' }}</h2>
                <div class="field"><label class="label">Nome</label><input class="input" wire:model="nome"></div>
                <div class="field"><label class="label">Prezzo</label><input class="input" type="number" step="0.01" wire:model="prezzo"></div>
                <div class="field">
                    <label class="label">Categoria</label>
                    <select class="input" wire:model="categoria_id">
                        @foreach ($categorie as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label">Area stampa (override)</label>
                    <select class="input" wire:model="area_stampa">
                        <option value="">(ereditata dalla categoria)</option>
                        <option value="cucina">cucina</option>
                        <option value="griglia">griglia</option>
                        <option value="cliente">cliente</option>
                    </select>
                </div>
                <div class="field"><label class="label">Stock default (vuoto = illimitato)</label><input class="input" type="number" wire:model="stock_default"></div>
                <div class="check-row">
                    <label><input type="checkbox" wire:model="attivo"> Attivo</label>
                    <label><input type="checkbox" wire:model="piatto_del_giorno"> Piatto del giorno</label>
                    <label><input type="checkbox" wire:model="bar"> Voce Bar</label>
                    <label><input type="checkbox" wire:model="is_coperto"> Voce Coperto</label>
                </div>
                @error('is_coperto') <div class="alert alert-danger mt-tight">{{ $message }}</div> @enderror
                <div class="actions-row">
                    <button class="btn btn-primary" wire:click="salva">Salva</button>
                    <button class="btn" wire:click="nuovo">Nuova</button>
                </div>
            </div>

            <div class="panel">
                <h2>Nuova categoria</h2>
                <div class="field"><label class="label">Nome</label><input class="input" placeholder="Nome" wire:model="catNome"></div>
                <div class="field">
                    <label class="label">Area stampa</label>
                    <select class="input" wire:model="catArea">
                        <option value="cliente">cliente</option>
                        <option value="cucina">cucina</option>
                        <option value="griglia">griglia</option>
                    </select>
                </div>
                <button class="btn" wire:click="creaCategoria">Crea categoria</button>
            </div>
        </div>

        <div class="panel panel-scroll">
            @foreach ($categorie as $cat)
                <h3 class="cat-title">{{ $cat->nome }} <span class="badge">{{ $cat->area_stampa }}</span></h3>
                <table class="table table-block">
                    <tbody>
                    @foreach ($cat->menuItems as $item)
                        <tr @class(['is-disattivo' => ! $item->attivo])>
                            <td>{{ $item->ordinamento }}</td>
                            <td>
                                <strong>{{ $item->nome }}</strong>
                                @if ($item->area_stampa)<span class="badge">{{ $item->area_stampa }}</span>@endif
                                @if ($item->bar)<span class="badge">BAR</span>@endif
                                @if ($item->is_coperto)<span class="badge">COPERTI</span>@endif
                                @if ($item->stock_default !== null)<span class="meta-small"> stock {{ $item->stock_default }}</span>@endif
                            </td>
                            <td>{{ number_format($item->prezzo, 2, ',', '.') }} €</td>
                            <td class="cell-nowrap">
                                <button class="btn btn-sm" wire:click="sposta({{ $item->id }}, 'up')">↑</button>
                                <button class="btn btn-sm" wire:click="sposta({{ $item->id }}, 'down')">↓</button>
                                <button class="btn btn-sm" wire:click="edit({{ $item->id }})">Mod</button>
                                @if ($item->attivo)
                                    <button
                                        class="btn btn-sm btn-danger"
                                        wire:click="disattiva({{ $item->id }})"
                                        wire:confirm="Disattivare «{{ $item->nome }}»? Non comparirà più in cassa."
                                    >Off</button>
                                @else
                                    <button class="btn btn-sm" wire:click="attiva({{ $item->id }})">On</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
</div>
