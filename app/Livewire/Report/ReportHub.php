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
    public string $tipo = 'cumulativo';

    public ?int $serataId = null;

    public ?int $serataConfrontoId = null;

    public ?int $puntoCassaId = null;

    public bool $completo = true;

    public function mount(): void
    {
        $this->serataId = Serata::query()->orderByDesc('data')->value('id');
        $this->puntoCassaId = PuntoCassa::query()->where('attivo', true)->value('id');
        $this->serataConfrontoId = $this->serataPrecedenteId($this->serataId);
    }

    public function updatedSerataId(?int $value): void
    {
        if ($this->tipo === 'confronto') {
            $this->serataConfrontoId = $this->serataPrecedenteId($value);
        }
    }

    public function exportCsv()
    {
        $serata = $this->serataId ? Serata::query()->find($this->serataId) : null;
        if (! $serata) {
            return;
        }

        $filename = 'serata-'.$serata->data->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($serata) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM Excel
            fputcsv($out, [
                'numero', 'numero_di_serata', 'stato', 'coperti', 'metodo',
                'importo_contante', 'importo_pos', 'totale', 'tavolo', 'note',
                'postazione', 'punto_cassa', 'created_at',
                'voce', 'quantita', 'prezzo_unitario', 'importo_riga',
            ], ';');

            $comande = Comanda::query()
                ->with(['righe.menuItem', 'postazione', 'puntoCassa'])
                ->where('serata_id', $serata->id)
                ->orderBy('id')
                ->get();

            foreach ($comande as $c) {
                $base = [
                    $c->numero_progressivo,
                    $c->numeroDiSerata(),
                    $c->stato,
                    $c->coperti,
                    $c->metodo_pagamento,
                    $c->importoContanteEffettivo(),
                    $c->importoPosEffettivo(),
                    (float) $c->totale,
                    $c->tavolo,
                    $c->note,
                    $c->postazione?->nome,
                    $c->puntoCassa?->nome,
                    optional($c->created_at)?->format('Y-m-d H:i:s'),
                ];
                if ($c->righe->isEmpty()) {
                    fputcsv($out, array_merge($base, ['', '', '', '']), ';');
                    continue;
                }
                foreach ($c->righe as $r) {
                    fputcsv($out, array_merge($base, [
                        $r->menuItem?->nome,
                        $r->quantita,
                        (float) $r->prezzo_unitario,
                        round($r->quantita * (float) $r->prezzo_unitario, 2),
                    ]), ';');
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
                'cumulativo' => $this->datiReparto($idsStasera, $idsFino, $serata, null),
                'cucina_1' => $this->datiReparto($idsStasera, $idsFino, $serata, 'cucina_1'),
                'cucina_2' => $this->datiReparto($idsStasera, $idsFino, $serata, 'cucina_2'),
                'griglia' => $this->datiReparto($idsStasera, $idsFino, $serata, 'griglia'),
                'bevande' => $this->datiBevande($idsStasera, $idsFino),
                'statistiche' => $this->datiStatistiche($serateFino),
                'economico' => $this->datiEconomico($serateFino),
                'consegna' => $this->datiConsegna($serata),
                'confronto' => $this->datiConfronto($serata),
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

    private function serataPrecedenteId(?int $serataId): ?int
    {
        if (! $serataId) {
            return null;
        }
        $corrente = Serata::query()->find($serataId);
        if (! $corrente) {
            return null;
        }

        return Serata::query()
            ->where('data', '<', $corrente->data->toDateString())
            ->orderByDesc('data')
            ->value('id');
    }

    private function datiConfronto(Serata $serata): array
    {
        $altra = $this->serataConfrontoId
            ? Serata::query()->find($this->serataConfrontoId)
            : null;

        $a = $this->riepilogoSerataBreve($serata);
        $b = $altra ? $this->riepilogoSerataBreve($altra) : null;

        $piatti = [];
        $idsA = $a['qta'];
        $idsB = $b['qta'] ?? [];
        $allIds = collect(array_keys($idsA))->merge(array_keys($idsB))->unique()->values();
        $nomi = MenuItem::query()->whereIn('id', $allIds)->pluck('nome', 'id');
        foreach ($allIds as $id) {
            $qa = (int) ($idsA[$id] ?? 0);
            $qb = (int) ($idsB[$id] ?? 0);
            if ($qa === 0 && $qb === 0) {
                continue;
            }
            $piatti[] = [
                'nome' => $nomi[$id] ?? ('#'.$id),
                'qta_a' => $qa,
                'qta_b' => $qb,
                'delta' => $qa - $qb,
            ];
        }
        usort($piatti, fn ($x, $y) => abs($y['delta']) <=> abs($x['delta']));

        return [
            'a' => $a,
            'b' => $b,
            'piatti' => $piatti,
        ];
    }

    /**
     * @return array{label: string, coperti: int, comande: int, incasso: float, contante: float, pos: float, qta: array<int, int>}
     */
    private function riepilogoSerataBreve(Serata $serata): array
    {
        $comande = Comanda::query()->where('serata_id', $serata->id)->where('stato', 'stampata')->get();
        $dettaglio = $this->venditeDettaglioPerItem(collect([$serata->id]));

        return [
            'label' => $serata->data->format('d/m/Y'),
            'coperti' => (int) $comande->sum('coperti'),
            'comande' => $comande->count(),
            'incasso' => round($comande->sum(fn ($c) => $c->importoIncasso()), 2),
            'contante' => round($comande->sum(fn ($c) => $c->importoContanteEffettivo()), 2),
            'pos' => round($comande->sum(fn ($c) => $c->importoPosEffettivo()), 2),
            'qta' => $dettaglio['qta'],
        ];
    }

    /**
     * @return array{qta: array<int, int>, incasso: array<int, float>}
     */
    private function venditeDettaglioPerItem($serataIds): array
    {
        // Quantità: tutte le comande stampate (anche omaggio/sospeso).
        $qtaRows = ComandaRiga::query()
            ->select('menu_item_id', DB::raw('SUM(quantita) as qta'))
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata'))
            ->groupBy('menu_item_id')
            ->get();

        // Incasso €: esclude omaggio e sospesi aperti.
        $incassoRows = ComandaRiga::query()
            ->select('menu_item_id', DB::raw('SUM(quantita * prezzo_unitario) as incasso'))
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata')->contanoComeIncasso())
            ->groupBy('menu_item_id')
            ->get();

        $qta = [];
        $incasso = [];
        foreach ($qtaRows as $row) {
            $qta[(int) $row->menu_item_id] = (int) $row->qta;
        }
        foreach ($incassoRows as $row) {
            $incasso[(int) $row->menu_item_id] = round((float) $row->incasso, 2);
        }

        return ['qta' => $qta, 'incasso' => $incasso];
    }

    private function venditePerItem($serataIds): array
    {
        return $this->venditeDettaglioPerItem($serataIds)['qta'];
    }

    /**
     * Report produzione: un'area (dettaglio) oppure tutte cucina_1/2 + griglia (cumulativo).
     *
     * @param  Collection<int, int>  $idsStasera
     * @param  Collection<int, int>  $idsFino
     * @return array{
     *   mode: string,
     *   area: ?string,
     *   titolo: string,
     *   sezioni: list<array{area: string, label: string, categorie: \Illuminate\Support\Collection}>,
     *   stasera: array<int, int>,
     *   cumulato: array<int, int>,
     *   stock: \Illuminate\Support\Collection,
     *   copertiStasera: int,
     *   copertiCum: int
     * }
     */
    private function datiReparto($idsStasera, $idsFino, Serata $serata, ?string $area): array
    {
        $stasera = $this->venditePerItem($idsStasera);
        $cumulato = $this->venditePerItem($idsFino);
        $stock = SerataStock::query()->where('serata_id', $serata->id)->get()->keyBy('menu_item_id');

        $aree = $area === null
            ? ['cucina_1', 'cucina_2', 'griglia']
            : [$area];

        $tutteCategorie = Categoria::query()
            ->with(['menuItems' => fn ($q) => $q->orderBy('ordinamento')])
            ->orderBy('ordinamento')
            ->get();

        $sezioni = [];
        foreach ($aree as $codiceArea) {
            $categorie = $tutteCategorie
                ->map(function (Categoria $cat) use ($codiceArea) {
                    $clone = clone $cat;
                    $items = $cat->menuItems->filter(
                        fn (MenuItem $item) => $item->areaStampaEffettiva() === $codiceArea
                    )->values();
                    $clone->setRelation('menuItems', $items);

                    return $clone;
                })
                ->filter(fn (Categoria $cat) => $cat->menuItems->isNotEmpty())
                ->values();

            if ($categorie->isEmpty()) {
                continue;
            }

            $sezioni[] = [
                'area' => $codiceArea,
                'label' => MenuItem::etichettaArea($codiceArea),
                'categorie' => $categorie,
            ];
        }

        $copertiStasera = (int) Comanda::query()->whereIn('serata_id', $idsStasera)->where('stato', 'stampata')->sum('coperti');
        $copertiCum = (int) Comanda::query()->whereIn('serata_id', $idsFino)->where('stato', 'stampata')->sum('coperti');

        $mode = $area === null ? 'cumulativo' : 'dettaglio';

        return [
            'mode' => $mode,
            'area' => $area,
            'titolo' => $mode === 'cumulativo'
                ? 'Cumulativo produzione'
                : ('Dettaglio '.MenuItem::etichettaArea($area)),
            'sezioni' => $sezioni,
            // Compat test legacy: flat categorie della prima (unica) sezione dettaglio
            'categorie' => collect($sezioni)->flatMap(fn ($s) => $s['categorie'])->values(),
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
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata')->contanoComeIncasso())
            ->selectRaw('COALESCE(SUM(quantita * prezzo_unitario), 0) as tot')
            ->value('tot');

        return round((float) $val, 2);
    }

    public static function totaleBarPerSerate($serataIds): float
    {
        $val = ComandaRiga::query()
            ->where('bar', true)
            ->whereHas('comanda', fn ($q) => $q->whereIn('serata_id', $serataIds)->where('stato', 'stampata')->contanoComeIncasso())
            ->selectRaw('COALESCE(SUM(quantita * prezzo_unitario), 0) as tot')
            ->value('tot');

        return round((float) $val, 2);
    }

    private function datiStatistiche($serateFino): array
    {
        $ids = $serateFino->pluck('id');
        $comande = Comanda::query()->whereIn('serata_id', $ids)->where('stato', 'stampata')->get();
        $coperti = (int) $comande->sum('coperti');
        $incasso = round($comande->sum(fn ($c) => $c->importoIncasso()), 2);
        $nSerate = max(1, $serateFino->count());
        $mediaCoperti = round($coperti / $nSerate, 1);

        $perSerata = $serateFino->map(function ($s) {
            $c = Comanda::query()->where('serata_id', $s->id)->where('stato', 'stampata')->get();

            return [
                'data' => $s->data->format('d/m'),
                'coperti' => (int) $c->sum('coperti'),
                'incasso' => round($c->sum(fn ($x) => $x->importoIncasso()), 2),
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
