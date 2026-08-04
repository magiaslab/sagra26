<?php

namespace App\Livewire\Gestione;

use App\Models\Chiusura;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use App\Services\RiconciliazioneService;
use App\Services\SerataService;
use App\Services\StockService;
use Livewire\Component;

class Serate extends Component
{
    public string $data = '';

    public string $note = '';

    /** @var array<int, int> */
    public array $stockOverrides = [];

    /** @var array<int, string> */
    public array $fondiIniziali = [];

    /** @var array<int, string> qty da aggiungere in rifornimento mid-serata */
    public array $rifornimenti = [];

    public ?string $errore = null;

    /** @var list<string> Nomi punti cassa senza chiusura completata (chiusa_at). */
    public array $puntiCassaMancanti = [];

    public function mount(RiconciliazioneService $ric): void
    {
        $this->data = now()->toDateString();
        foreach (MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->get() as $item) {
            $this->stockOverrides[$item->id] = (int) $item->stock_default;
            $this->rifornimenti[$item->id] = '';
        }
        foreach (PuntoCassa::query()->where('attivo', true)->get() as $punto) {
            $suggerito = $ric->fondoInizialeSuggerito($punto);
            $this->fondiIniziali[$punto->id] = $suggerito !== null ? (string) $suggerito : '';
        }
    }

    public function rifornisciStock(int $menuItemId, StockService $stock): void
    {
        $this->errore = null;
        $serata = Serata::corrente();
        if (! $serata) {
            $this->errore = 'Nessuna serata aperta.';

            return;
        }

        $qty = (int) ($this->rifornimenti[$menuItemId] ?? 0);
        try {
            $stock->rifornisci($serata->id, $menuItemId, $qty);
            $this->rifornimenti[$menuItemId] = '';
            session()->flash('status', 'Stock aggiornato (+'.$qty.').');
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
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
        if (! $serata) {
            return;
        }

        $mancanti = $this->puntiCassaSenzaChiusura($serata);
        if ($mancanti !== []) {
            // Non chiude ancora: la UI chiede conferma esplicita (forzaChiusura).
            $this->puntiCassaMancanti = $mancanti;

            return;
        }

        $this->eseguiChiusura($service, $serata);
    }

    public function forzaChiusura(SerataService $service): void
    {
        $serata = Serata::corrente();
        if (! $serata) {
            return;
        }

        $this->eseguiChiusura($service, $serata);
    }

    public function annullaChiusura(): void
    {
        $this->puntiCassaMancanti = [];
    }

    public function riapri(int $serataId, SerataService $service): void
    {
        $this->errore = null;
        try {
            $serata = Serata::query()->findOrFail($serataId);
            $service->riapri($serata);
            session()->flash('status', 'Serata riaperta.');
            $this->redirect(route('gestione.serate', absolute: false), navigate: true);
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
        }
    }

    public function elimina(int $serataId, SerataService $service): void
    {
        $this->errore = null;
        try {
            $serata = Serata::query()->findOrFail($serataId);
            $service->elimina($serata);
            session()->flash('status', 'Serata eliminata.');
            $this->redirect(route('gestione.serate', absolute: false), navigate: true);
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
        }
    }

    private function eseguiChiusura(SerataService $service, Serata $serata): void
    {
        $service->chiudi($serata);
        $this->puntiCassaMancanti = [];
        session()->flash('status', 'Serata chiusa. I report restano stampabili; per correggere errori riapri la serata.');
    }

    /**
     * @return list<string>
     */
    private function puntiCassaSenzaChiusura(Serata $serata): array
    {
        $puntiAttivi = PuntoCassa::query()->where('attivo', true)->orderBy('id')->get();
        $mancanti = [];

        foreach ($puntiAttivi as $punto) {
            $chiusura = Chiusura::query()
                ->where('serata_id', $serata->id)
                ->where('punto_cassa_id', $punto->id)
                ->first();

            if (! $chiusura || $chiusura->chiusa_at === null) {
                $mancanti[] = $punto->nome;
            }
        }

        return $mancanti;
    }

    public function render()
    {
        $serata = Serata::corrente();
        $stockSerata = collect();
        if ($serata) {
            app(StockService::class)->assicuraStockLimitati($serata->id);
            $stockSerata = SerataStock::query()
                ->with('menuItem')
                ->where('serata_id', $serata->id)
                ->get()
                ->sortBy(fn (SerataStock $s) => $s->menuItem?->ordinamento ?? 0)
                ->values();
        }

        return view('livewire.gestione.serate', [
            'serata' => $serata,
            'stockSerata' => $stockSerata,
            'sogliaAlert' => Impostazione::corrente()->sogliaStockAlert(),
            'storico' => Serata::query()->orderByDesc('data')->limit(20)->get(),
            'limitati' => MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->orderBy('ordinamento')->get(),
            'punti' => PuntoCassa::query()->where('attivo', true)->get(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
