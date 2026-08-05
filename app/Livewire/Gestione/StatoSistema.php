<?php

namespace App\Livewire\Gestione;

use App\Models\Impostazione;
use App\Services\SystemStatusService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
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
        return view('livewire.gestione.stato-sistema');
    }
}
