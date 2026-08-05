<?php

namespace App\Livewire\Gestione;

use App\Livewire\Concerns\WithToast;
use App\Models\Impostazione;
use App\Models\Postazione;
use App\Models\PostazionePuntoCassa;
use App\Models\PuntoCassa;
use App\Support\GestioneEliminaGuardrail;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ImpostazioniPage extends Component
{
    use WithToast;

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
        $this->toastOk('Impostazioni salvate.');
    }

    public function aggiungiPostazione(): void
    {
        $this->validate(['nuovaPostazione' => 'required|string|max:255']);
        Postazione::query()->create(['nome' => $this->nuovaPostazione]);
        $this->nuovaPostazione = '';
        $this->errore = '';
        $this->toastOk('Postazione aggiunta.');
    }

    public function eliminaPostazione(int $id): void
    {
        $postazione = Postazione::query()->findOrFail($id);

        if ($motivo = GestioneEliminaGuardrail::motivoBloccoPostazione($postazione)) {
            $this->bloccaEliminazione($motivo);

            return;
        }

        $postazione->delete();
        $this->errore = '';
        $this->toastOk('Postazione eliminata.');
    }

    public function aggiungiPunto(): void
    {
        $this->validate(['nuovoPunto' => 'required|string|max:255']);
        PuntoCassa::query()->create(['nome' => $this->nuovoPunto, 'attivo' => true]);
        $this->nuovoPunto = '';
        $this->errore = '';
        $this->toastOk('Punto cassa aggiunto.');
    }

    public function eliminaPunto(int $id): void
    {
        $punto = PuntoCassa::query()->findOrFail($id);

        if ($motivo = GestioneEliminaGuardrail::motivoBloccoPuntoCassa($punto)) {
            $this->bloccaEliminazione($motivo);

            return;
        }

        $punto->delete();
        $this->errore = '';
        $this->toastOk('Punto cassa eliminato.');
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
        $this->toastOk('Mappatura salvata.');
    }

    private function bloccaEliminazione(string $messaggio): void
    {
        $this->errore = $messaggio;
        $this->toastDanger($messaggio);
    }

    public function render()
    {
        return view('livewire.gestione.impostazioni', [
            'postazioni' => Postazione::query()->orderBy('id')->get(),
            'punti' => PuntoCassa::query()->orderBy('id')->get(),
            'mappature' => PostazionePuntoCassa::query()->with(['postazione', 'puntoCassa'])->orderByDesc('valido_da')->get(),
        ]);
    }
}
