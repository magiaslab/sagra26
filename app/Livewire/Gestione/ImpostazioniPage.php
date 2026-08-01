<?php

namespace App\Livewire\Gestione;

use App\Models\Impostazione;
use App\Models\Postazione;
use App\Models\PostazionePuntoCassa;
use App\Models\PuntoCassa;
use Livewire\Component;

class ImpostazioniPage extends Component
{
    public string $intestazione_nome = '';

    public string $intestazione_anno = '';

    public string $intestazione_sottotitolo = '';

    public string $pin_gestione = '';

    public string $nuovaPostazione = '';

    public string $nuovoPunto = '';

    public ?int $mapPostazione = null;

    public ?int $mapPunto = null;

    public string $mapValidoDa = '';

    public function mount(): void
    {
        $i = Impostazione::corrente();
        $this->intestazione_nome = $i->intestazione_nome;
        $this->intestazione_anno = $i->intestazione_anno;
        $this->intestazione_sottotitolo = (string) ($i->intestazione_sottotitolo ?? '');
        $this->pin_gestione = $i->pin_gestione;
        $this->mapValidoDa = now()->toDateString();
    }

    public function salvaIntestazione(): void
    {
        $i = Impostazione::corrente();
        $i->update([
            'intestazione_nome' => $this->intestazione_nome,
            'intestazione_anno' => $this->intestazione_anno,
            'intestazione_sottotitolo' => $this->intestazione_sottotitolo ?: null,
            'pin_gestione' => $this->pin_gestione,
        ]);
        session()->flash('status', 'Impostazioni salvate.');
    }

    public function aggiungiPostazione(): void
    {
        $this->validate(['nuovaPostazione' => 'required|string|max:255']);
        Postazione::query()->create(['nome' => $this->nuovaPostazione]);
        $this->nuovaPostazione = '';
    }

    public function aggiungiPunto(): void
    {
        $this->validate(['nuovoPunto' => 'required|string|max:255']);
        PuntoCassa::query()->create(['nome' => $this->nuovoPunto, 'attivo' => true]);
        $this->nuovoPunto = '';
    }

    public function mappa(): void
    {
        $this->validate([
            'mapPostazione' => 'required|exists:postazioni,id',
            'mapPunto' => 'required|exists:punti_cassa,id',
            'mapValidoDa' => 'required|date',
        ]);
        PostazionePuntoCassa::query()->create([
            'postazione_id' => $this->mapPostazione,
            'punto_cassa_id' => $this->mapPunto,
            'valido_da' => $this->mapValidoDa,
        ]);
        session()->flash('status', 'Mappatura salvata.');
    }

    public function render()
    {
        return view('livewire.gestione.impostazioni', [
            'postazioni' => Postazione::query()->orderBy('id')->get(),
            'punti' => PuntoCassa::query()->orderBy('id')->get(),
            'mappature' => PostazionePuntoCassa::query()->with(['postazione', 'puntoCassa'])->orderByDesc('valido_da')->get(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
