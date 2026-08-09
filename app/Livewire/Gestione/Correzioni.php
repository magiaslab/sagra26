<?php

namespace App\Livewire\Gestione;

use App\Models\ComandaCorrezione;
use App\Models\Impostazione;
use App\Models\Serata;
use Illuminate\Support\Collection;
use Livewire\Component;

class Correzioni extends Component
{
    public ?int $serataId = null;

    public function mount(): void
    {
        $corrente = Serata::corrente();
        $this->serataId = $corrente?->id
            ?? Serata::queryEdizione()->orderByDesc('data')->value('id');
    }

    public function render()
    {
        $serate = Serata::queryEdizione()->orderByDesc('data')->get();
        $serata = $this->serataSelezionata();
        $correzioni = $serata ? $this->queryCorrezioni($serata->id) : collect();

        return view('livewire.gestione.correzioni', [
            'serate' => $serate,
            'serata' => $serata,
            'correzioni' => $correzioni,
            'impostazioni' => Impostazione::corrente(),
            'comandeUniche' => $correzioni->pluck('comanda_id')->unique()->count(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }

    private function serataSelezionata(): ?Serata
    {
        if (! $this->serataId) {
            return null;
        }

        return Serata::query()->find($this->serataId);
    }

    /** @return Collection<int, ComandaCorrezione> */
    private function queryCorrezioni(int $serataId): Collection
    {
        return ComandaCorrezione::query()
            ->with(['comanda.postazione', 'comanda.puntoCassa', 'postazione'])
            ->whereHas('comanda', fn ($q) => $q->where('serata_id', $serataId))
            ->orderByDesc('id')
            ->get();
    }
}
