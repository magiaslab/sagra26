<?php

namespace App\Livewire\Gestione;

use App\Models\Chiusura;
use App\Models\Impostazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\ChiusuraService;
use App\Services\RiconciliazioneService;
use Livewire\Component;

class ChiusuraForm extends Component
{
    public ?int $serataId = null;

    public ?int $puntoCassaId = null;

    public float $fondo_iniziale = 0;

    public float $fondo_trattenuto = 0;

    public float $totale_pos = 0;

    public float $totale_z = 0;

    public string $note = '';

    /** @var array<string, int> */
    public array $pezzi = [];

    public ?array $riconciliazione = null;

    public ?string $errore = null;

    public function mount(RiconciliazioneService $ric): void
    {
        foreach (array_keys(Chiusura::TAGLI) as $campo) {
            $this->pezzi[$campo] = 0;
        }
        $serata = Serata::corrente() ?? Serata::query()->orderByDesc('data')->first();
        $this->serataId = $serata?->id;
        $punto = PuntoCassa::query()->where('attivo', true)->first();
        $this->puntoCassaId = $punto?->id;
        $this->carica();
    }

    public function updatedSerataId(): void
    {
        $this->carica();
    }

    public function updatedPuntoCassaId(): void
    {
        $this->carica();
    }

    public function updatedPezzi(): void
    {
        $this->ricalcolaPreview();
    }

    public function updatedFondoIniziale(): void
    {
        $this->ricalcolaPreview();
    }

    public function updatedFondoTrattenuto(): void
    {
        $this->ricalcolaPreview();
    }

    public function updatedTotalePos(): void
    {
        $this->ricalcolaPreview();
    }

    public function updatedTotaleZ(): void
    {
        $this->ricalcolaPreview();
    }

    public function carica(): void
    {
        if (! $this->serataId || ! $this->puntoCassaId) {
            return;
        }
        $chiusura = Chiusura::query()
            ->where('serata_id', $this->serataId)
            ->where('punto_cassa_id', $this->puntoCassaId)
            ->first();

        if ($chiusura) {
            $this->fondo_iniziale = (float) $chiusura->fondo_iniziale;
            $this->fondo_trattenuto = (float) $chiusura->fondo_trattenuto;
            $this->totale_pos = (float) $chiusura->totale_pos;
            $this->totale_z = (float) $chiusura->totale_z;
            $this->note = (string) ($chiusura->note ?? '');
            foreach (array_keys(Chiusura::TAGLI) as $campo) {
                $this->pezzi[$campo] = (int) $chiusura->{$campo};
            }
        } else {
            $punto = PuntoCassa::query()->find($this->puntoCassaId);
            $sug = $punto ? app(RiconciliazioneService::class)->fondoInizialeSuggerito($punto) : null;
            $this->fondo_iniziale = $sug ?? 0;
        }
        $this->ricalcolaPreview();
    }

    public function ricalcolaPreview(): void
    {
        if (! $this->serataId || ! $this->puntoCassaId) {
            $this->riconciliazione = null;

            return;
        }
        $tmp = new Chiusura($this->pezzi);
        $tmp->fondo_iniziale = $this->fondo_iniziale;
        $tmp->fondo_trattenuto = $this->fondo_trattenuto;
        $tmp->totale_pos = $this->totale_pos;
        $tmp->totale_z = $this->totale_z;
        $tmp->contante_contato = $tmp->calcolaContanteContato();

        $this->riconciliazione = app(RiconciliazioneService::class)->calcola(
            Serata::query()->findOrFail($this->serataId),
            PuntoCassa::query()->findOrFail($this->puntoCassaId),
            $tmp,
        );
        $this->riconciliazione['contante_contato'] = $tmp->contante_contato;
    }

    public function salva(ChiusuraService $service): void
    {
        $this->errore = null;
        $serata = Serata::query()->findOrFail($this->serataId);
        $punto = PuntoCassa::query()->findOrFail($this->puntoCassaId);
        try {
            $service->salva($serata, $punto, array_merge($this->pezzi, [
                'fondo_iniziale' => $this->fondo_iniziale,
                'fondo_trattenuto' => $this->fondo_trattenuto,
                'totale_pos' => $this->totale_pos,
                'totale_z' => $this->totale_z,
                'note' => $this->note,
            ]));
            session()->flash('status', 'Chiusura salvata.');
            $this->carica();
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
        }
    }

    public function serataBloccata(): bool
    {
        if (! $this->serataId) {
            return false;
        }
        $serata = Serata::query()->find($this->serataId);

        return $serata !== null && ! $serata->isAperta();
    }

    public function render()
    {
        return view('livewire.gestione.chiusura-form', [
            'serate' => Serata::query()->orderByDesc('data')->get(),
            'punti' => PuntoCassa::query()->where('attivo', true)->get(),
            'tagli' => Chiusura::TAGLI,
            'bloccata' => $this->serataBloccata(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
