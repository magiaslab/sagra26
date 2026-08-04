<?php

namespace App\Livewire\Gestione;

use App\Models\Categoria;
use App\Models\Impostazione;
use App\Models\MenuItem;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MenuCrud extends Component
{
    public ?int $editingId = null;

    public string $nome = '';

    public string $prezzo = '';

    public ?int $categoria_id = null;

    public bool $attivo = true;

    public bool $piatto_del_giorno = false;

    public bool $bar = false;

    public bool $congelato = false;

    public bool $is_coperto = false;

    public string $stock_default = '';

    public string $area_stampa = '';

    public string $catNome = '';

    public string $catArea = 'cliente';

    public string $comunicazione_comanda = '';

    public function mount(): void
    {
        $this->comunicazione_comanda = (string) (Impostazione::corrente()->comunicazione_comanda ?? '');
        $this->categoria_id = Categoria::query()->orderBy('ordinamento')->value('id');
    }

    public function salvaComunicazione(): void
    {
        $this->validate([
            'comunicazione_comanda' => 'nullable|string|max:2000',
        ]);

        Impostazione::corrente()->update([
            'comunicazione_comanda' => trim($this->comunicazione_comanda) !== ''
                ? trim($this->comunicazione_comanda)
                : null,
        ]);

        session()->flash('status', 'Comunicazione comanda salvata.');
        $this->dispatch('toast', message: 'Comunicazione comanda salvata.', type: 'ok');
    }

    public function edit(int $id): void
    {
        $item = MenuItem::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->nome = $item->nome;
        $this->prezzo = (string) $item->prezzo;
        $this->categoria_id = $item->categoria_id;
        $this->attivo = $item->attivo;
        $this->piatto_del_giorno = $item->piatto_del_giorno;
        $this->bar = (bool) $item->bar;
        $this->congelato = (bool) $item->congelato;
        $this->is_coperto = (bool) $item->is_coperto;
        $this->stock_default = $item->stock_default !== null ? (string) $item->stock_default : '';
        $this->area_stampa = $item->area_stampa ?? '';
    }

    public function nuovo(): void
    {
        $this->editingId = null;
        $this->nome = '';
        $this->prezzo = '';
        $this->categoria_id = Categoria::query()->orderBy('ordinamento')->value('id');
        $this->attivo = true;
        $this->piatto_del_giorno = false;
        $this->bar = false;
        $this->congelato = false;
        $this->is_coperto = false;
        $this->stock_default = '';
        $this->area_stampa = '';
    }

    public function salva(): void
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'prezzo' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorie,id',
        ]);

        $this->assertUnicoCopertoAttivo($this->editingId, $this->is_coperto, $this->attivo);

        $maxOrd = (int) MenuItem::query()->max('ordinamento');

        $data = [
            'nome' => $this->nome,
            'prezzo' => $this->prezzo,
            'categoria_id' => $this->categoria_id,
            'attivo' => $this->attivo,
            'piatto_del_giorno' => $this->piatto_del_giorno,
            'bar' => $this->bar,
            'congelato' => $this->congelato,
            'is_coperto' => $this->is_coperto,
            'stock_default' => $this->stock_default === '' ? null : (int) $this->stock_default,
            'area_stampa' => $this->area_stampa === '' ? null : $this->area_stampa,
        ];

        if ($this->editingId) {
            MenuItem::query()->whereKey($this->editingId)->update($data);
        } else {
            $data['ordinamento'] = $maxOrd + 1;
            MenuItem::query()->create($data);
        }

        session()->flash('status', 'Voce menù salvata.');
        $this->nuovo();
    }

    public function disattiva(int $id): void
    {
        MenuItem::query()->whereKey($id)->update(['attivo' => false]);
    }

    public function attiva(int $id): void
    {
        $item = MenuItem::query()->findOrFail($id);
        $this->assertUnicoCopertoAttivo($item->id, (bool) $item->is_coperto, true);
        $item->update(['attivo' => true]);
    }

    /**
     * Al massimo una voce attiva con is_coperto: il conteggio coperti in cassa/report
     * somma tutte le quantità flaggate; più voci attive sarebbero ambigue in UI.
     */
    private function assertUnicoCopertoAttivo(?int $escludiId, bool $isCoperto, bool $attivo): void
    {
        if (! $isCoperto || ! $attivo) {
            return;
        }

        $altro = MenuItem::query()
            ->where('is_coperto', true)
            ->where('attivo', true)
            ->when($escludiId, fn ($q) => $q->where('id', '!=', $escludiId))
            ->exists();

        if ($altro) {
            throw ValidationException::withMessages([
                'is_coperto' => 'Esiste già una voce attiva marcata come Coperto.',
            ]);
        }
    }

    public function sposta(int $id, string $dir): void
    {
        $item = MenuItem::query()->findOrFail($id);
        $swap = MenuItem::query()
            ->when($dir === 'up', fn ($q) => $q->where('ordinamento', '<', $item->ordinamento)->orderByDesc('ordinamento'))
            ->when($dir === 'down', fn ($q) => $q->where('ordinamento', '>', $item->ordinamento)->orderBy('ordinamento'))
            ->first();
        if (! $swap) {
            return;
        }
        $tmp = $item->ordinamento;
        $item->ordinamento = $swap->ordinamento;
        $swap->ordinamento = $tmp;
        $item->save();
        $swap->save();
    }

    public function creaCategoria(): void
    {
        $this->validate([
            'catNome' => 'required|string|max:255',
            'catArea' => 'required|in:cliente,cucina_1,cucina_2,griglia',
        ]);
        $ord = (int) Categoria::query()->max('ordinamento') + 1;
        Categoria::query()->create([
            'nome' => $this->catNome,
            'area_stampa' => $this->catArea,
            'ordinamento' => $ord,
        ]);
        $this->catNome = '';
        session()->flash('status', 'Categoria creata.');
    }

    public function render()
    {
        return view('livewire.gestione.menu-crud', [
            'categorie' => Categoria::query()->with(['menuItems' => fn ($q) => $q->orderBy('ordinamento')])->orderBy('ordinamento')->get(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
