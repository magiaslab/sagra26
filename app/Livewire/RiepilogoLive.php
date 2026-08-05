<?php

namespace App\Livewire;

use App\Models\Comanda;
use App\Models\ComandaCorrezione;
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
            'omaggi' => 0,
            'sospesi' => 0,
            'di_cui_bar' => 0,
            'per_piatto' => collect(),
            'per_postazione' => collect(),
            'annullate' => collect(),
            'correzioni_per_postazione' => collect(),
        ];

        if ($serata) {
            $comande = Comanda::query()
                ->with('postazione')
                ->where('serata_id', $serata->id)
                ->where('stato', 'stampata')
                ->get();

            $dati['coperti'] = $comande->sum('coperti');
            $dati['incasso'] = round($comande->sum(fn ($c) => $c->importoIncasso()), 2);
            $dati['contante'] = round($comande->sum(fn ($c) => $c->importoContanteEffettivo()), 2);
            $dati['pos'] = round($comande->sum(fn ($c) => $c->importoPosEffettivo()), 2);
            $dati['omaggi'] = round($comande->where('metodo_pagamento', 'omaggio')->sum('totale'), 2);
            $dati['sospesi'] = round($comande->where('metodo_pagamento', 'sospeso')->sum('totale'), 2);
            $dati['di_cui_bar'] = \App\Livewire\Report\ReportHub::totaleBarPerSerate(collect([$serata->id]));

            $dati['per_postazione'] = $comande->groupBy('postazione_id')->map(function ($group) {
                return [
                    'nome' => $group->first()->postazione->nome,
                    'n' => $group->count(),
                    'totale' => round($group->sum(fn ($c) => $c->importoIncasso()), 2),
                ];
            })->values();

            $dati['per_piatto'] = ComandaRiga::query()
                ->join('comande', 'comande.id', '=', 'comanda_righe.comanda_id')
                ->select(
                    'comanda_righe.menu_item_id',
                    DB::raw('SUM(comanda_righe.quantita) as qta'),
                    DB::raw("SUM(CASE WHEN comande.metodo_pagamento NOT IN ('omaggio', 'sospeso') THEN comanda_righe.quantita * comanda_righe.prezzo_unitario ELSE 0 END) as incasso")
                )
                ->where('comande.serata_id', $serata->id)
                ->where('comande.stato', 'stampata')
                ->groupBy('comanda_righe.menu_item_id')
                ->orderByDesc('qta')
                ->with('menuItem')
                ->get();

            $dati['annullate'] = Comanda::query()
                ->where('serata_id', $serata->id)
                ->where('stato', 'annullata')
                ->orderByDesc('numero_progressivo')
                ->get(['numero_progressivo', 'motivo_annullo', 'totale']);

            $dati['correzioni_per_postazione'] = ComandaCorrezione::query()
                ->select('postazione_id', DB::raw('COUNT(*) as n'))
                ->whereHas('comanda', fn ($q) => $q->where('serata_id', $serata->id))
                ->with('postazione')
                ->groupBy('postazione_id')
                ->get()
                ->map(fn ($row) => [
                    'nome' => $row->postazione->nome,
                    'n' => (int) $row->n,
                ]);
        }

        return view('livewire.riepilogo-live', [
            'serata' => $serata,
            'dati' => $dati,
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
