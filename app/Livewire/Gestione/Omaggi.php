<?php

namespace App\Livewire\Gestione;

use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\Serata;
use Illuminate\Support\Collection;
use Livewire\Component;

class Omaggi extends Component
{
    public ?int $serataId = null;

    public function mount(): void
    {
        $corrente = Serata::corrente();
        $this->serataId = $corrente?->id
            ?? Serata::query()->orderByDesc('data')->value('id');
    }

    public function exportCsv()
    {
        $serata = $this->serataSelezionata();
        if (! $serata) {
            return null;
        }

        $omaggi = $this->queryOmaggi($serata->id);
        $filename = 'omaggi-'.$serata->data->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($serata, $omaggi) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM Excel
            fputcsv($out, [
                'data_serata',
                'numero',
                'ora',
                'ospite',
                'autorizzato_da',
                'note',
                'cassa',
                'punto_cassa',
                'coperti',
                'totale_valore',
                'voci',
            ], ';');

            foreach ($omaggi as $c) {
                $voci = $c->righe
                    ->map(fn ($r) => ($r->menuItem?->nome ?? '?').' ×'.$r->quantita)
                    ->implode(', ');

                fputcsv($out, [
                    $serata->data->format('Y-m-d'),
                    $c->numero_progressivo,
                    optional($c->created_at)?->format('H:i'),
                    $c->nominativo,
                    $c->autorizzato_da,
                    $c->pagamento_note,
                    $c->postazione?->nome,
                    $c->puntoCassa?->nome,
                    (int) $c->coperti,
                    number_format((float) $c->totale, 2, '.', ''),
                    $voci,
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, [
                'RIEPILOGO',
                'comande',
                $omaggi->count(),
                'totale_valore',
                number_format(round($omaggi->sum('totale'), 2), 2, '.', ''),
                'coperti',
                (int) $omaggi->sum('coperti'),
            ], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $serate = Serata::query()->orderByDesc('data')->get();
        $serata = $this->serataSelezionata();
        $omaggi = $serata ? $this->queryOmaggi($serata->id) : collect();

        return view('livewire.gestione.omaggi', [
            'serate' => $serate,
            'serata' => $serata,
            'omaggi' => $omaggi,
            'impostazioni' => Impostazione::corrente(),
            'totaleValore' => round($omaggi->sum('totale'), 2),
            'totaleCoperti' => (int) $omaggi->sum('coperti'),
            'perAutorizzatore' => $this->riepilogoPerAutorizzatore($omaggi),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }

    private function serataSelezionata(): ?Serata
    {
        if (! $this->serataId) {
            return null;
        }

        return Serata::query()->find($this->serataId);
    }

    /** @return Collection<int, Comanda> */
    private function queryOmaggi(int $serataId): Collection
    {
        return Comanda::query()
            ->with(['postazione', 'puntoCassa', 'righe.menuItem'])
            ->where('serata_id', $serataId)
            ->where('stato', 'stampata')
            ->where('metodo_pagamento', 'omaggio')
            ->orderByDesc('numero_progressivo')
            ->get();
    }

    /** @param  Collection<int, Comanda>  $omaggi */
    private function riepilogoPerAutorizzatore(Collection $omaggi): Collection
    {
        return $omaggi
            ->groupBy(fn (Comanda $c) => filled($c->autorizzato_da) ? $c->autorizzato_da : '(non indicato)')
            ->map(fn (Collection $group, string $nome) => [
                'nome' => $nome,
                'count' => $group->count(),
                'totale' => round($group->sum('totale'), 2),
            ])
            ->sortByDesc('totale')
            ->values();
    }
}
