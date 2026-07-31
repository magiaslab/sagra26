<?php

namespace App\Livewire\Report;

use App\Models\Categoria;
use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\ComandaRiga;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use App\Services\RiconciliazioneService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportHub extends Component
{
    public string $tipo = 'cucina';

    public ?int $serataId = null;

    public ?int $puntoCassaId = null;

    public bool $completo = true;

    public function mount(): void
    {
        $this->serataId = Serata::query()->orderByDesc('data')->value('id');
        $this->puntoCassaId = PuntoCassa::query()->where('attivo', true)->value('id');
    }

    public function render()
    {
        $serate = Serata::query()->orderBy('data')->get();
        $serata = $this->serataId ? Serata::query()->find($this->serataId) : null;
        $dati = [];

        if ($serata) {
            $serateFino = $this->completo
                ? $serate
                : $serate->filter(fn ($s) => $s->data->lte($serata->data));

            $idsFino = $serateFino->pluck('id');
            $idsStasera = collect([$serata->id]);

            if ($this->tipo === 'cucina') {
                $dati = $this->datiCucina($idsStasera, $idsFino, $serata);
            } elseif ($this->tipo === 'statistiche') {
                $dati = $this->datiStatistiche($serateFino);
            } elseif ($this->tipo === 'economico') {
                $dati = $this->datiEconomico($serateFino);
            } elseif ($this->tipo === 'consegna') {
                $dati = $this->datiConsegna($serata);
            }
        }

        return view('livewire.report.hub', [
            'serate' => $serate,
            'serata' => $serata,
            'punti' => PuntoCassa::query()->where('attivo', true)->get(),
            'dati' => $dati,
            'impostazioni' => Impostazione::corrente(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }

    private function venditePerItem($serataIds): array
    {
        return ComandaRiga::query()
            ->select('menu_item_id', DB::raw('SUM(quantita) as qta'), DB::raw('SUM(quantita * prezzo_unitario) as incasso'))
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata'))
            ->groupBy('menu_item_id')
            ->pluck('qta', 'menu_item_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function datiCucina($idsStasera, $idsFino, Serata $serata): array
    {
        $stasera = $this->venditePerItem($idsStasera);
        $cumulato = $this->venditePerItem($idsFino);
        $stock = SerataStock::query()->where('serata_id', $serata->id)->get()->keyBy('menu_item_id');

        $categorie = Categoria::query()->with(['menuItems' => fn ($q) => $q->orderBy('ordinamento')])->orderBy('ordinamento')->get();

        $copertiStasera = (int) Comanda::query()->whereIn('serata_id', $idsStasera)->where('stato', 'stampata')->sum('coperti');
        $copertiCum = (int) Comanda::query()->whereIn('serata_id', $idsFino)->where('stato', 'stampata')->sum('coperti');

        return compact('categorie', 'stasera', 'cumulato', 'stock', 'copertiStasera', 'copertiCum');
    }

    private function datiStatistiche($serateFino): array
    {
        $ids = $serateFino->pluck('id');
        $comande = Comanda::query()->whereIn('serata_id', $ids)->where('stato', 'stampata')->get();
        $coperti = (int) $comande->sum('coperti');
        $incasso = round($comande->sum('totale'), 2);
        $nSerate = max(1, $serateFino->count());
        $mediaCoperti = round($coperti / $nSerate, 1);

        $perSerata = $serateFino->map(function ($s) {
            $c = Comanda::query()->where('serata_id', $s->id)->where('stato', 'stampata')->get();

            return [
                'data' => $s->data->format('d/m'),
                'coperti' => (int) $c->sum('coperti'),
                'incasso' => round($c->sum('totale'), 2),
            ];
        });

        $maxCoperti = max(1, $perSerata->max('coperti') ?: 1);

        $ore = [];
        foreach ($comande as $c) {
            $h = $c->created_at->format('H');
            $ore[$h] = ($ore[$h] ?? 0) + 1;
        }
        ksort($ore);
        $maxOre = max(1, $ore ? max($ore) : 1);

        $top = ComandaRiga::query()
            ->select('menu_item_id', DB::raw('SUM(quantita) as qta'))
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $ids)->where('stato', 'stampata'))
            ->with('menuItem')
            ->groupBy('menu_item_id')
            ->orderByDesc('qta')
            ->limit(10)
            ->get();

        $record = $perSerata->sortByDesc('incasso')->first();

        return compact('coperti', 'incasso', 'mediaCoperti', 'perSerata', 'maxCoperti', 'ore', 'maxOre', 'top', 'record');
    }

    private function datiEconomico($serateFino): array
    {
        $ric = app(RiconciliazioneService::class);
        $righe = [];
        $totC = 0;
        $totP = 0;
        foreach ($serateFino as $s) {
            $comande = Comanda::query()->where('serata_id', $s->id)->where('stato', 'stampata')->get();
            $c = round($comande->sum(fn ($x) => $x->importoContanteEffettivo()), 2);
            $p = round($comande->sum(fn ($x) => $x->importoPosEffettivo()), 2);
            $totC += $c;
            $totP += $p;
            $righe[] = [
                'data' => $s->data->format('d/m/Y'),
                'contante' => $c,
                'pos' => $p,
                'totale' => round($c + $p, 2),
            ];
        }
        $totale = round($totC + $totP, 2);

        return [
            'righe' => $righe,
            'tot_contante' => round($totC, 2),
            'tot_pos' => round($totP, 2),
            'totale' => $totale,
            'pct_contante' => $totale > 0 ? round($totC / $totale * 100, 1) : 0,
            'pct_pos' => $totale > 0 ? round($totP / $totale * 100, 1) : 0,
        ];
    }

    private function datiConsegna(Serata $serata): array
    {
        if (! $this->puntoCassaId) {
            return [];
        }
        $punto = PuntoCassa::query()->findOrFail($this->puntoCassaId);
        $chiusura = Chiusura::query()
            ->where('serata_id', $serata->id)
            ->where('punto_cassa_id', $punto->id)
            ->first();
        if (! $chiusura) {
            return ['errore' => 'Nessuna chiusura salvata per questo punto cassa.'];
        }
        $ric = app(RiconciliazioneService::class)->calcola($serata, $punto, $chiusura);

        return [
            'punto' => $punto,
            'chiusura' => $chiusura,
            'ric' => $ric,
            'tagli' => Chiusura::TAGLI,
        ];
    }
}
