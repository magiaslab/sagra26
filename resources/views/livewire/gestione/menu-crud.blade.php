<div>
    <h1>Menù</h1>
    <div class="grid-2">
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
            <label><input type="checkbox" wire:model="attivo"> Attivo</label>
            <label style="margin-left:1rem"><input type="checkbox" wire:model="piatto_del_giorno"> Piatto del giorno</label>
            <div style="margin-top:1rem;display:flex;gap:.5rem">
                <button class="btn btn-primary" wire:click="salva">Salva</button>
                <button class="btn" wire:click="nuovo">Nuova</button>
            </div>

            <hr style="margin:1.5rem 0;border:1px solid #ccc">
            <h3>Nuova categoria</h3>
            <div class="field"><input class="input" placeholder="Nome" wire:model="catNome"></div>
            <div class="field">
                <select class="input" wire:model="catArea">
                    <option value="cliente">cliente</option>
                    <option value="cucina">cucina</option>
                    <option value="griglia">griglia</option>
                </select>
            </div>
            <button class="btn" wire:click="creaCategoria">Crea categoria</button>
        </div>

        <div class="panel" style="max-height:80vh;overflow:auto">
            @foreach ($categorie as $cat)
                <h3 style="margin-bottom:.35rem">{{ $cat->nome }} <span class="badge">{{ $cat->area_stampa }}</span></h3>
                <table class="table" style="margin-bottom:1rem">
                    <tbody>
                    @foreach ($cat->menuItems as $item)
                        <tr style="{{ $item->attivo ? '' : 'opacity:.5' }}">
                            <td>{{ $item->ordinamento }}</td>
                            <td>
                                <strong>{{ $item->nome }}</strong>
                                @if ($item->area_stampa)<span class="badge">{{ $item->area_stampa }}</span>@endif
                                @if ($item->stock_default !== null)<span class="meta-small"> stock {{ $item->stock_default }}</span>@endif
                            </td>
                            <td>{{ number_format($item->prezzo, 2, ',', '.') }} €</td>
                            <td style="white-space:nowrap">
                                <button class="btn btn-sm" wire:click="sposta({{ $item->id }}, 'up')">↑</button>
                                <button class="btn btn-sm" wire:click="sposta({{ $item->id }}, 'down')">↓</button>
                                <button class="btn btn-sm" wire:click="edit({{ $item->id }})">Mod</button>
                                @if ($item->attivo)
                                    <button class="btn btn-sm btn-danger" wire:click="disattiva({{ $item->id }})">Off</button>
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
