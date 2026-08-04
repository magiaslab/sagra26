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

    public int $stock_soglia_alert = 10;

    public string $nuovaPostazione = '';

    public string $nuovoPunto = '';

    public ?int $mapPostazione = null;

    public ?int $mapPunto = null;

    public string $mapValidoDa = '';

    public string $errore = '';

    public function mount(): void
    {
        $i = Impostazione::corrente();
        $this->intestazione_nome = $i->intestazione_nome;
        $this->intestazione_anno = $i->intestazione_anno;
        $this->intestazione_sottotitolo = (string) ($i->intestazione_sottotitolo ?? '');
        $this->pin_gestione = $i->pin_gestione;
        $this->stock_soglia_alert = $i->sogliaStockAlert();
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
            'stock_soglia_alert' => max(0, (int) $this->stock_soglia_alert),
        ]);
        $this->errore = '';
        session()->flash('status', 'Impostazioni salvate.');
        $this->dispatch('toast', message: 'Impostazioni salvate.', type: 'ok');
    }

    public function aggiungiPostazione(): void
    {
        $this->validate(['nuovaPostazione' => 'required|string|max:255']);
        Postazione::query()->create(['nome' => $this->nuovaPostazione]);
        $this->nuovaPostazione = '';
        $this->errore = '';
    }

    public function eliminaPostazione(int $id): void
    {
        $postazione = Postazione::query()->findOrFail($id);

        $nComande = $postazione->comande()->count();
        if ($nComande > 0) {
            $this->bloccaEliminazione("Non eliminabile: {$nComande} comande già registrate su questa postazione");

            return;
        }

        $nMappature = $postazione->mappature()->count();
        if ($nMappature > 0) {
            $this->bloccaEliminazione("Non eliminabile: {$nMappature} mappature punto cassa collegate a questa postazione");

            return;
        }

        $postazione->delete();
        $this->errore = '';
        $this->dispatch('toast', message: 'Postazione eliminata.', type: 'ok');
    }

    public function aggiungiPunto(): void
    {
        $this->validate(['nuovoPunto' => 'required|string|max:255']);
        PuntoCassa::query()->create(['nome' => $this->nuovoPunto, 'attivo' => true]);
        $this->nuovoPunto = '';
        $this->errore = '';
    }

    public function eliminaPunto(int $id): void
    {
        $punto = PuntoCassa::query()->findOrFail($id);

        $nComande = $punto->comande()->count();
        if ($nComande > 0) {
            $this->bloccaEliminazione("Non eliminabile: {$nComande} comande già registrate su questo punto cassa");

            return;
        }

        $nChiusure = $punto->chiusure()->count();
        if ($nChiusure > 0) {
            $this->bloccaEliminazione("Non eliminabile: {$nChiusure} chiusure collegate a questo punto cassa");

            return;
        }

        $nMappature = $punto->mappature()->count();
        if ($nMappature > 0) {
            $this->bloccaEliminazione("Non eliminabile: {$nMappature} mappature postazione collegate a questo punto cassa");

            return;
        }

        $punto->delete();
        $this->errore = '';
        $this->dispatch('toast', message: 'Punto cassa eliminato.', type: 'ok');
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
        $this->errore = '';
        session()->flash('status', 'Mappatura salvata.');
        $this->dispatch('toast', message: 'Mappatura salvata.', type: 'ok');
    }

    private function bloccaEliminazione(string $messaggio): void
    {
        $this->errore = $messaggio;
        $this->dispatch('toast', message: $messaggio, type: 'danger');
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
