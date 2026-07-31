<?php

namespace App\Livewire\Gestione;

use App\Models\Chiusura;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\RiconciliazioneService;
use App\Services\SerataService;
use Livewire\Component;

class Serate extends Component
{
    public string $data = '';

    public string $note = '';

    /** @var array<int, int> */
    public array $stockOverrides = [];

    /** @var array<int, string> */
    public array $fondiIniziali = [];

    public ?string $errore = null;

    public function mount(RiconciliazioneService $ric): void
    {
        $this->data = now()->toDateString();
        foreach (MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->get() as $item) {
            $this->stockOverrides[$item->id] = (int) $item->stock_default;
        }
        foreach (PuntoCassa::query()->where('attivo', true)->get() as $punto) {
            $suggerito = $ric->fondoInizialeSuggerito($punto);
            $this->fondiIniziali[$punto->id] = $suggerito !== null ? (string) $suggerito : '';
        }
    }

    public function apri(SerataService $service): void
    {
        $this->errore = null;
        try {
            $fondi = [];
            foreach ($this->fondiIniziali as $id => $val) {
                if ($val === '' || $val === null) {
                    throw new \RuntimeException('Inserisci il fondo iniziale per tutti i punti cassa.');
                }
                $fondi[(int) $id] = (float) $val;
            }
            $service->apri($this->data, $this->note ?: null, $this->stockOverrides, $fondi);
            session()->flash('status', 'Serata aperta.');
            $this->redirect(route('gestione.serate', absolute: false), navigate: true);
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
        }
    }

    public function chiudi(SerataService $service): void
    {
        $serata = Serata::corrente();
        if ($serata) {
            $service->chiudi($serata);
            session()->flash('status', 'Serata chiusa.');
        }
    }

    public function render()
    {
        return view('livewire.gestione.serate', [
            'serata' => Serata::corrente(),
            'storico' => Serata::query()->orderByDesc('data')->limit(20)->get(),
            'limitati' => MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->orderBy('ordinamento')->get(),
            'punti' => PuntoCassa::query()->where('attivo', true)->get(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
