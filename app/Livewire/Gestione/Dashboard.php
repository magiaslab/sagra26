<?php

namespace App\Livewire\Gestione;

use App\Models\Impostazione;
use App\Models\Serata;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.gestione.dashboard', [
            'serata' => Serata::corrente(),
            'impostazioni' => Impostazione::corrente(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
