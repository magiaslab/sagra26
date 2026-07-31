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
use Illuminate\Support\Collection;
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

            $dati = match ($this->tipo) {
                'cucina' => $this->datiReparto($idsStasera, $idsFino, $serata, 'cucina'),
                'griglia' => $this->datiReparto($idsStasera, $idsFino, $serata, 'griglia'),
                'bevande' => $this->datiBevande($idsStasera, $idsFino),
                'statistiche' => $this->datiStatistiche($serateFino),
                'economico' => $this->datiEconomico($serateFino),
                'consegna' => $this->datiConsegna($serata),
                default => [],
            };
        }

        return view('livewire.report.hub', [
            'serate' => $serate,
            'serata' => $serata,
            'punti' => PuntoCassa::query()->where('attivo', true)->get(),
            'dati' => $dati,
            'impostazioni' => Impostazione::corrente(),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }

    /**
     * @return array{qta: array<int, int>, incasso: array<int, float>}
     */
    private function venditeDettaglioPerItem($serataIds): array
    {
        $rows = ComandaRiga::query()
            ->select(
                'menu_item_id',
                DB::raw('SUM(quantita) as qta'),
                DB::raw('SUM(quantita * prezzo_unitario) as incasso')
            )
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata'))
            ->groupBy('menu_item_id')
            ->get();

        $qta = [];
        $incasso = [];
        foreach ($rows as $row) {
            $qta[(int) $row->menu_item_id] = (int) $row->qta;
            $incasso[(int) $row->menu_item_id] = round((float) $row->incasso, 2);
        }

        return ['qta' => $qta, 'incasso' => $incasso];
    }

    private function venditePerItem($serataIds): array
    {
        return $this->venditeDettaglioPerItem($serataIds)['qta'];
    }

    private function datiReparto($idsStasera, $idsFino, Serata $serata, string $area): array
    {
        $stasera = $this->venditePerItem($idsStasera);
        $cumulato = $this->venditePerItem($idsFino);
        $stock = SerataStock::query()->where('serata_id', $serata->id)->get()->keyBy('menu_item_id');

        $categorie = Categoria::query()
            ->with(['menuItems' => fn ($q) => $q->orderBy('ordinamento')])
            ->orderBy('ordinamento')
            ->get()
            ->map(function (Categoria $cat) use ($area) {
                $items = $cat->menuItems->filter(
                    fn (MenuItem $item) => $item->areaStampaEffettiva() === $area
                )->values();
                $cat->setRelation('menuItems', $items);

                return $cat;
            })
            ->filter(fn (Categoria $cat) => $cat->menuItems->isNotEmpty())
            ->values();

        $copertiStasera = (int) Comanda::query()->whereIn('serata_id', $idsStasera)->where('stato', 'stampata')->sum('coperti');
        $copertiCum = (int) Comanda::query()->whereIn('serata_id', $idsFino)->where('stato', 'stampata')->sum('coperti');

        return [
            'area' => $area,
            'categorie' => $categorie,
            'stasera' => $stasera,
            'cumulato' => $cumulato,
            'stock' => $stock,
            'copertiStasera' => $copertiStasera,
            'copertiCum' => $copertiCum,
        ];
    }

    private function datiBevande($idsStasera, $idsFino): array
    {
        $s = $this->venditeDettaglioPerItem($idsStasera);
        $c = $this->venditeDettaglioPerItem($idsFino);

        $items = MenuItem::query()
            ->with('categoria')
            ->whereHas('categoria', fn ($q) => $q->where('is_bevande', true))
            ->orderBy('ordinamento')
            ->get()
            ->filter(function (MenuItem $item) use ($s, $c) {
                if ($item->attivo) {
                    return true;
                }

                return ($s['qta'][$item->id] ?? 0) > 0 || ($c['qta'][$item->id] ?? 0) > 0;
            })
            ->values();

        $itemIds = $items->pluck('id');

        $riepilogo = [
            'bar_stasera' => $this->incassoBar($idsStasera, true, $itemIds),
            'non_bar_stasera' => $this->incassoBar($idsStasera, false, $itemIds),
            'bar_cumulato' => $this->incassoBar($idsFino, true, $itemIds),
            'non_bar_cumulato' => $this->incassoBar($idsFino, false, $itemIds),
        ];

        return [
            'items' => $items,
            'stasera_qta' => $s['qta'],
            'stasera_incasso' => $s['incasso'],
            'cumulato_qta' => $c['qta'],
            'cumulato_incasso' => $c['incasso'],
            'riepilogo' => $riepilogo,
        ];
    }

    private function incassoBar($serataIds, bool $bar, Collection $menuItemIds): float
    {
        if ($menuItemIds->isEmpty()) {
            return 0.0;
        }

        $val = ComandaRiga::query()
            ->where('bar', $bar)
            ->whereIn('menu_item_id', $menuItemIds)
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata'))
            ->selectRaw('COALESCE(SUM(quantita * prezzo_unitario), 0) as tot')
            ->value('tot');

        return round((float) $val, 2);
    }

    public static function totaleBarPerSerate($serataIds): float
    {
        $val = ComandaRiga::query()
            ->where('bar', true)
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata'))
            ->selectRaw('COALESCE(SUM(quantita * prezzo_unitario), 0) as tot')
            ->value('tot');

        return round((float) $val, 2);
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
                'bar' => self::totaleBarPerSerate(collect([$s->id])),
            ];
        }
        $totale = round($totC + $totP, 2);
        $ids = $serateFino->pluck('id');

        return [
            'righe' => $righe,
            'tot_contante' => round($totC, 2),
            'tot_pos' => round($totP, 2),
            'totale' => $totale,
            'di_cui_bar' => self::totaleBarPerSerate($ids),
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
