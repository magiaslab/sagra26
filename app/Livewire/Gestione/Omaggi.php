<?php

namespace App\Livewire\Gestione;

use App\Livewire\Concerns\WithToast;
use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\Serata;
use Illuminate\Support\Collection;
use Livewire\Component;

class Omaggi extends Component
{
    use WithToast;

    public ?int $serataId = null;

    public function mount(): void
    {
        $corrente = Serata::corrente();
        $this->serataId = $corrente?->id
            ?? Serata::queryEdizione()->orderByDesc('data')->value('id');
    }

    public function exportCsv()
    {
        $serata = $this->serataSelezionata();
        if (! $serata) {
            $this->toastWarn('Seleziona una serata.');

            return null;
        }

        $omaggi = $this->queryOmaggi($serata->id);
        if ($omaggi->isEmpty()) {
            $this->toastWarn('Nessun omaggio o sconto da esportare.');

            return null;
        }

        $filename = 'omaggi-sconti-'.$serata->data->format('Y-m-d').'.csv';
        $this->toastOk('Export omaggi/sconti avviato.');

        return response()->streamDownload(function () use ($serata, $omaggi) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM Excel
            fputcsv($out, [
                'data_serata',
                'numero',
                'ora',
                'tipo',
                'sconto_percentuale',
                'ospite',
                'autorizzato_da',
                'note',
                'cassa',
                'punto_cassa',
                'coperti',
                'valore_sconto',
                'totale_pagato',
                'metodo_pagamento',
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
                    $c->isOmaggio() ? 'OMAGGIO' : ('SCONTO '.(int) $c->sconto_percentuale.'%'),
                    (int) ($c->sconto_percentuale ?? 0),
                    $c->nominativo,
                    $c->autorizzato_da,
                    $c->pagamento_note,
                    $c->postazione?->nome,
                    $c->puntoCassa?->nome,
                    (int) $c->coperti,
                    number_format($c->importoSconto(), 2, '.', ''),
                    number_format($c->isOmaggio() ? 0.0 : (float) $c->totale, 2, '.', ''),
                    $c->metodo_pagamento,
                    $voci,
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, [
                'RIEPILOGO',
                'comande',
                $omaggi->count(),
                'valore_sconto_totale',
                number_format(round($omaggi->sum(fn (Comanda $c) => $c->importoSconto()), 2), 2, '.', ''),
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
        $serate = Serata::queryEdizione()->orderByDesc('data')->get();
        $serata = $this->serataSelezionata();
        $omaggi = $serata ? $this->queryOmaggi($serata->id) : collect();

        return view('livewire.gestione.omaggi', [
            'serate' => $serate,
            'serata' => $serata,
            'omaggi' => $omaggi,
            'impostazioni' => Impostazione::corrente(),
            'totaleValore' => round($omaggi->sum(fn (Comanda $c) => $c->importoSconto()), 2),
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
            ->where(function ($q) {
                $q->where('metodo_pagamento', 'omaggio')
                    ->orWhere(function ($q2) {
                        $q2->where('sconto_percentuale', '>', 0)
                            ->where('sconto_percentuale', '<', 100);
                    });
            })
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
                'totale' => round($group->sum(fn (Comanda $c) => $c->importoSconto()), 2),
            ])
            ->sortByDesc('totale')
            ->values();
    }
}
