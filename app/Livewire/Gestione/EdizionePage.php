<?php

namespace App\Livewire\Gestione;

use App\Livewire\Concerns\WithToast;
use App\Models\Edizione;
use App\Models\Impostazione;
use App\Models\Serata;
use App\Services\EdizioneService;
use Livewire\Component;
use Throwable;

class EdizionePage extends Component
{
    use WithToast;

    public string $nuovoAnno = '';

    public string $nuovoNome = '';

    public string $noteChiusura = '';

    public string $confermaChiusura = '';

    public bool $chiediChiusura = false;

    public function mount(EdizioneService $edizioni): void
    {
        $edizioni->assicuratiCorrente();
        $this->nuovoAnno = (string) ((int) date('Y') + 1);
        $this->nuovoNome = 'Sagra '.$this->nuovoAnno;
    }

    public function preparaChiusura(): void
    {
        $this->chiediChiusura = true;
        $this->confermaChiusura = '';
    }

    public function annullaChiusura(): void
    {
        $this->chiediChiusura = false;
        $this->confermaChiusura = '';
        $this->noteChiusura = '';
    }

    public function chiudiEdizione(EdizioneService $edizioni): void
    {
        if (trim($this->confermaChiusura) !== 'CHIUDI') {
            $this->toastWarn('Per confermare digita esattamente CHIUDI.');

            return;
        }

        try {
            $edizione = Edizione::corrente();
            if (! $edizione) {
                throw new \RuntimeException('Nessuna edizione aperta.');
            }
            $edizioni->chiudi($edizione, $this->noteChiusura ?: null);
            $this->toastOk('Edizione '.$edizione->anno.' archiviata. I dati restano disponibili.');
            $this->annullaChiusura();
        } catch (Throwable $e) {
            $this->toastDanger($e->getMessage());
        }
    }

    public function apriNuova(EdizioneService $edizioni): void
    {
        try {
            $anno = (int) $this->nuovoAnno;
            $edizione = $edizioni->apri($anno, $this->nuovoNome ?: null);
            Impostazione::corrente()->update(['intestazione_anno' => (string) $anno]);
            $this->toastOk('Edizione '.$edizione->anno.' aperta. Puoi aprire le serate.');
            $this->nuovoAnno = (string) ($anno + 1);
            $this->nuovoNome = 'Sagra '.$this->nuovoAnno;
        } catch (Throwable $e) {
            $this->toastDanger($e->getMessage());
        }
    }

    public function riapri(int $edizioneId, EdizioneService $edizioni): void
    {
        try {
            $edizione = Edizione::query()->findOrFail($edizioneId);
            $edizioni->riapri($edizione);
            Impostazione::corrente()->update(['intestazione_anno' => (string) $edizione->anno]);
            $this->toastOk('Edizione '.$edizione->anno.' riaperta.');
        } catch (Throwable $e) {
            $this->toastDanger($e->getMessage());
        }
    }

    public function render()
    {
        $corrente = Edizione::corrente();
        $serateCount = $corrente
            ? Serata::query()->where('edizione_id', $corrente->id)->count()
            : 0;
        $serataAperta = Serata::corrente();

        return view('livewire.gestione.edizione', [
            'corrente' => $corrente,
            'serateCount' => $serateCount,
            'serataAperta' => $serataAperta,
            'storico' => Edizione::query()->orderByDesc('anno')->get(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
