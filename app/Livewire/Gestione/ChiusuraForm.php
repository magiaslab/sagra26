<?php

namespace App\Livewire\Gestione;

use App\Livewire\Concerns\WithToast;
use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\ChiusuraService;
use App\Services\RiconciliazioneService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ChiusuraForm extends Component
{
    use WithToast;

    public ?int $serataId = null;

    public ?int $puntoCassaId = null;

    public float $fondo_iniziale = 0;

    public float $fondo_trattenuto = 0;

    public float $totale_pos = 0;

    public float $totale_z = 0;

    public string $note = '';

    /** @var array<string, int> */
    public array $pezzi = [];

    /** @var array<string, int> Pezzi lasciati in cassa come fondo sera dopo */
    public array $pezziFondo = [];

    public bool $syncFondoDaPezzi = true;

    public ?array $riconciliazione = null;

    public ?string $errore = null;

    public ?string $chiusaAtLabel = null;

    public bool $chiusuraCompletata = false;

    public function mount(): void
    {
        foreach (array_keys(Chiusura::TAGLI) as $campo) {
            $this->pezzi[$campo] = 0;
            $this->pezziFondo[$campo] = 0;
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

    public function updatedPezziFondo(): void
    {
        if ($this->syncFondoDaPezzi) {
            $this->fondo_trattenuto = Chiusura::totaleDaPezzi(Chiusura::normalizzaPezzi($this->pezziFondo));
        }
        $this->ricalcolaPreview();
    }

    public function updatedFondoIniziale(): void
    {
        $this->ricalcolaPreview();
    }

    public function updatedFondoTrattenuto(): void
    {
        $this->syncFondoDaPezzi = false;
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

    public function applicaTotalePezziFondo(): void
    {
        $this->syncFondoDaPezzi = true;
        $this->fondo_trattenuto = Chiusura::totaleDaPezzi(Chiusura::normalizzaPezzi($this->pezziFondo));
        $this->ricalcolaPreview();
    }

    public function carica(): void
    {
        $this->errore = null;
        $this->chiusaAtLabel = null;
        $this->chiusuraCompletata = false;
        $this->syncFondoDaPezzi = true;

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
            $this->pezziFondo = $chiusura->pezziFondoNormalizzati();
            $this->chiusuraCompletata = $chiusura->isCompletata();
            $this->chiusaAtLabel = $chiusura->chiusa_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
            if (array_sum($this->pezziFondo) > 0) {
                $this->syncFondoDaPezzi = true;
            }
        } else {
            foreach (array_keys(Chiusura::TAGLI) as $campo) {
                $this->pezzi[$campo] = 0;
                $this->pezziFondo[$campo] = 0;
            }
            $punto = PuntoCassa::query()->find($this->puntoCassaId);
            $sug = $punto ? app(RiconciliazioneService::class)->fondoInizialeSuggerito($punto) : null;
            $this->fondo_iniziale = $sug ?? 0;
            $this->fondo_trattenuto = 0;
            $this->totale_pos = 0;
            $this->totale_z = 0;
            $this->note = '';
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
        $this->riconciliazione['fondo_pezzi_totale'] = Chiusura::totaleDaPezzi(Chiusura::normalizzaPezzi($this->pezziFondo));
    }

    public function salva(ChiusuraService $service): void
    {
        $this->errore = null;
        $serata = Serata::query()->findOrFail($this->serataId);
        $punto = PuntoCassa::query()->findOrFail($this->puntoCassaId);
        try {
            if ($this->syncFondoDaPezzi) {
                $this->fondo_trattenuto = Chiusura::totaleDaPezzi(Chiusura::normalizzaPezzi($this->pezziFondo));
            }
            $service->salva($serata, $punto, array_merge($this->pezzi, [
                'fondo_iniziale' => $this->fondo_iniziale,
                'fondo_trattenuto' => $this->fondo_trattenuto,
                'pezzi_fondo' => $this->pezziFondo,
                'totale_pos' => $this->totale_pos,
                'totale_z' => $this->totale_z,
                'note' => $this->note,
            ]));
            $this->toastOk('Chiusura salvata.');
            $this->carica();
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
            $this->toastDanger($e->getMessage());
        }
    }

    public function riapriPerCorreggere(ChiusuraService $service): void
    {
        $this->errore = null;
        try {
            $serata = Serata::query()->findOrFail($this->serataId);
            $punto = PuntoCassa::query()->findOrFail($this->puntoCassaId);
            $service->riapriPerCorrezione($serata, $punto);
            $this->toastOk('Chiusura sbloccata: puoi correggere i conteggi e salvare di nuovo.');
            $this->carica();
        } catch (\Throwable $e) {
            $this->errore = $e->getMessage();
            $this->toastDanger($e->getMessage());
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

    /**
     * Conti sospesi ancora aperti per la serata/punto cassa selezionati (solo avviso UI).
     *
     * @return Collection<int, Comanda>
     */
    public function sospesiAperti(): Collection
    {
        if (! $this->serataId || ! $this->puntoCassaId) {
            return collect();
        }

        return Comanda::query()
            ->where('serata_id', $this->serataId)
            ->where('punto_cassa_id', $this->puntoCassaId)
            ->where('metodo_pagamento', 'sospeso')
            ->where('stato', 'stampata')
            ->orderBy('numero_progressivo')
            ->get();
    }

    public function render()
    {
        $fondoPezziTotale = Chiusura::totaleDaPezzi(Chiusura::normalizzaPezzi($this->pezziFondo));
        $sospesiAperti = $this->sospesiAperti();

        return view('livewire.gestione.chiusura-form', [
            'serate' => Serata::query()->orderByDesc('data')->get(),
            'punti' => PuntoCassa::query()->where('attivo', true)->get(),
            'tagli' => Chiusura::TAGLI,
            'bloccata' => $this->serataBloccata(),
            'fondoPezziTotale' => $fondoPezziTotale,
            'fondoPezziDescrizione' => Chiusura::descrizionePezzi(Chiusura::normalizzaPezzi($this->pezziFondo)),
            'sospesiAperti' => $sospesiAperti,
            'sospesiApertiTotale' => (float) $sospesiAperti->sum('totale'),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
