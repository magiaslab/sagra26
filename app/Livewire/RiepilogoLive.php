<?php

namespace App\Livewire;

use App\Models\Comanda;
use App\Models\ComandaRiga;
use App\Models\Impostazione;
use App\Models\Serata;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RiepilogoLive extends Component
{
    public function render()
    {
        $serata = Serata::corrente();
        $dati = [
            'coperti' => 0,
            'incasso' => 0,
            'contante' => 0,
            'pos' => 0,
            'di_cui_bar' => 0,
            'per_piatto' => collect(),
            'per_postazione' => collect(),
            'annullate' => collect(),
        ];

        if ($serata) {
            $comande = Comanda::query()
                ->with('postazione')
                ->where('serata_id', $serata->id)
                ->where('stato', 'stampata')
                ->get();

            $dati['coperti'] = $comande->sum('coperti');
            $dati['incasso'] = round($comande->sum('totale'), 2);
            $dati['contante'] = round($comande->sum(fn ($c) => $c->importoContanteEffettivo()), 2);
            $dati['pos'] = round($comande->sum(fn ($c) => $c->importoPosEffettivo()), 2);
            $dati['di_cui_bar'] = \App\Livewire\Report\ReportHub::totaleBarPerSerate(collect([$serata->id]));

            $dati['per_postazione'] = $comande->groupBy('postazione_id')->map(function ($group) {
                return [
                    'nome' => $group->first()->postazione->nome,
                    'n' => $group->count(),
                    'totale' => round($group->sum('totale'), 2),
                ];
            })->values();

            $dati['per_piatto'] = ComandaRiga::query()
                ->select('menu_item_id', DB::raw('SUM(quantita) as qta'), DB::raw('SUM(quantita * prezzo_unitario) as incasso'))
                ->whereHas('comanda', fn ($q) => $q->where('serata_id', $serata->id)->where('stato', 'stampata'))
                ->with('menuItem')
                ->groupBy('menu_item_id')
                ->orderByDesc('qta')
                ->get();

            $dati['annullate'] = Comanda::query()
                ->where('serata_id', $serata->id)
                ->where('stato', 'annullata')
                ->orderByDesc('numero_progressivo')
                ->get(['numero_progressivo', 'motivo_annullo', 'totale']);
        }

        return view('livewire.riepilogo-live', [
            'serata' => $serata,
            'dati' => $dati,
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
