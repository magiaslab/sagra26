<?php

namespace App\Livewire\Gestione;

use App\Models\Impostazione;
use App\Services\SystemStatusService;
use Livewire\Component;

class StatoSistema extends Component
{
    /** @var array<string, mixed> */
    public array $report = [];

    public function mount(SystemStatusService $status): void
    {
        $this->aggiorna($status);
    }

    public function aggiorna(SystemStatusService $status): void
    {
        $this->report = $status->collect();
    }

    public function render()
    {
        return view('livewire.gestione.stato-sistema')
            ->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
