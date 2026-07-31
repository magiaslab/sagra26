<?php

namespace App\Livewire\Gestione;

use App\Models\Impostazione;
use Livewire\Component;

class Pin extends Component
{
    public string $pin = '';

    public ?string $errore = null;

    public function sblocca(): void
    {
        $atteso = Impostazione::corrente()->pin_gestione;
        if (hash_equals((string) $atteso, $this->pin)) {
            session(['gestione_sbloccata' => true]);
            $this->redirect(route('gestione.dashboard'), navigate: true);

            return;
        }
        $this->errore = 'PIN non corretto.';
        $this->pin = '';
    }

    public function render()
    {
        return view('livewire.gestione.pin')
            ->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
