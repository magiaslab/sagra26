<?php

namespace App\Livewire\Gestione;

use App\Livewire\Concerns\WithToast;
use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\Serata;
use App\Services\ComandaService;
use Livewire\Component;
use RuntimeException;
use Throwable;

class Sospesi extends Component
{
    use WithToast;

    public ?int $chiudiId = null;

    public string $metodo = 'contante';

    public string $importoContante = '';

    public string $importoPos = '';

    public string $pin = '';

    public string $autorizzatoDa = '';

    public string $nominativo = '';

    public string $pagamentoNote = '';

    public ?string $errore = null;

    public function apriChiusura(int $comandaId): void
    {
        $this->errore = null;
        $comanda = Comanda::query()->find($comandaId);
        if (! $comanda || ! $comanda->isSospesoAperto()) {
            $this->errore = 'Sospeso non trovato o già chiuso.';

            return;
        }

        $this->chiudiId = $comandaId;
        $this->metodo = 'contante';
        $this->importoContante = (string) $comanda->totale;
        $this->importoPos = '0';
        $this->pin = '';
        $this->autorizzatoDa = (string) ($comanda->autorizzato_da ?? '');
        $this->nominativo = (string) ($comanda->nominativo ?? '');
        $this->pagamentoNote = (string) ($comanda->pagamento_note ?? '');
    }

    public function annullaChiusura(): void
    {
        $this->chiudiId = null;
        $this->errore = null;
        $this->pin = '';
    }

    public function updatedMetodo(): void
    {
        if ($this->chiudiId && $this->metodo === 'misto') {
            $comanda = Comanda::query()->find($this->chiudiId);
            if ($comanda) {
                $this->importoContante = (string) $comanda->totale;
                $this->importoPos = '0';
            }
        }
    }

    public function confermaChiusura(ComandaService $service): void
    {
        $this->errore = null;
        $serata = Serata::corrente();
        if (! $serata) {
            $this->errore = 'Nessuna serata aperta.';
            $this->toastWarn($this->errore);

            return;
        }

        $comanda = Comanda::query()->with('righe')->find($this->chiudiId);
        if (! $comanda || ! $comanda->isSospesoAperto()) {
            $this->errore = 'Sospeso non trovato o già chiuso.';
            $this->toastWarn($this->errore);

            return;
        }

        if (! in_array($this->metodo, ['contante', 'pos', 'misto', 'omaggio'], true)) {
            $this->errore = 'Metodo non valido.';
            $this->toastWarn($this->errore);

            return;
        }

        if ($this->metodo === 'omaggio') {
            $atteso = (string) Impostazione::corrente()->pin_gestione;
            if ($this->pin === '' || ! hash_equals($atteso, $this->pin)) {
                $this->errore = 'PIN non valido.';
                $this->toastWarn($this->errore);

                return;
            }
            if (trim($this->autorizzatoDa) === '' || trim($this->nominativo) === '') {
                $this->errore = 'Per omaggio servono autorizzato da e nome ospite.';
                $this->toastWarn($this->errore);

                return;
            }
        }

        $righe = $comanda->righe->map(fn ($r) => [
            'menu_item_id' => (int) $r->menu_item_id,
            'quantita' => (int) $r->quantita,
        ])->all();

        try {
            $service->confermaEStampa(
                $serata,
                $comanda->postazione,
                $righe,
                (int) $comanda->coperti,
                $this->metodo,
                $this->metodo === 'misto' ? (float) $this->importoContante : null,
                $this->metodo === 'misto' ? (float) $this->importoPos : null,
                $comanda,
                'Chiusura sospeso da Gestione',
                (int) $comanda->version,
                $comanda->tavolo,
                $comanda->note,
                $this->metodo === 'omaggio' ? $this->autorizzatoDa : null,
                $this->metodo === 'omaggio' ? $this->nominativo : null,
                $this->metodo === 'omaggio' ? ($this->pagamentoNote ?: null) : null,
            );

            $this->toastOk('Sospeso #'.$comanda->numero_progressivo.' chiuso ('.$this->metodo.').');
            $this->annullaChiusura();
        } catch (RuntimeException|Throwable $e) {
            $this->errore = $e->getMessage();
            $this->toastDanger($e->getMessage());
        }
    }

    public function render()
    {
        $serata = Serata::corrente();
        $sospesi = collect();
        if ($serata) {
            $sospesi = Comanda::query()
                ->with(['postazione', 'righe'])
                ->where('serata_id', $serata->id)
                ->where('stato', 'stampata')
                ->where('metodo_pagamento', 'sospeso')
                ->orderByDesc('numero_progressivo')
                ->get();
        }

        return view('livewire.gestione.sospesi', [
            'serata' => $serata,
            'sospesi' => $sospesi,
            'impostazioni' => Impostazione::corrente(),
            'totaleAperto' => round($sospesi->sum('totale'), 2),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
