<?php

namespace App\Livewire\Report;

use App\Models\Categoria;
use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\ComandaRiga;
use App\Models\Edizione;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use App\Services\EdizioneService;
use App\Services\RiconciliazioneService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportHub extends Component
{
    public string $tipo = 'cumulativo';

    public ?int $serataId = null;

    /** Inizio range per report Confronto (fine = serataId). */
    public ?int $serataDaId = null;

    /** @deprecated Usare serataDaId; mantenuto per query string legacy. */
    public ?int $serataConfrontoId = null;

    public ?int $puntoCassaId = null;

    public bool $completo = true;

    /** Se true, avvia window.print() al caricamento (es. da Chiusura → Foglio consegna). */
    public bool $autoPrint = false;

    private const TIPI = [
        'cumulativo', 'cucina_1', 'cucina_2', 'griglia', 'bevande',
        'statistiche', 'economico', 'consegna', 'confronto',
    ];

    public function mount(): void
    {
        $tipo = request()->query('tipo');
        if (is_string($tipo) && in_array($tipo, self::TIPI, true)) {
            $this->tipo = $tipo;
        }

        app(EdizioneService::class)->assicuratiCorrente();

        $serataQuery = request()->query('serata_id') ?? request()->query('serataId');
        if ($serataQuery !== null && $serataQuery !== '' && Serata::queryEdizione()->whereKey((int) $serataQuery)->exists()) {
            $this->serataId = (int) $serataQuery;
        } else {
            $this->serataId = Serata::queryEdizione()->orderByDesc('data')->value('id');
        }

        $puntoQuery = request()->query('punto_cassa_id') ?? request()->query('puntoCassaId');
        if ($puntoQuery !== null && $puntoQuery !== '' && PuntoCassa::query()->whereKey((int) $puntoQuery)->exists()) {
            $this->puntoCassaId = (int) $puntoQuery;
        } else {
            $this->puntoCassaId = PuntoCassa::query()->where('attivo', true)->value('id');
        }

        $daQuery = request()->query('serata_da_id') ?? request()->query('serataDaId')
            ?? request()->query('serata_confronto_id') ?? request()->query('serataConfrontoId');
        if ($daQuery !== null && $daQuery !== '' && Serata::queryEdizione()->whereKey((int) $daQuery)->exists()) {
            $this->serataDaId = (int) $daQuery;
        } else {
            $this->serataDaId = $this->serataPrecedenteId($this->serataId)
                ?? Serata::queryEdizione()->orderBy('data')->value('id');
        }
        $this->serataConfrontoId = $this->serataDaId;
        $this->autoPrint = request()->boolean('print');
    }

    public function updatedSerataId(?int $value): void
    {
        if ($this->tipo !== 'confronto' || ! $value) {
            return;
        }
        // Se il range è invertito, riallinea l’inizio.
        if ($this->serataDaId) {
            $fine = Serata::query()->find($value);
            $inizio = Serata::query()->find($this->serataDaId);
            if ($fine && $inizio && $inizio->data->gt($fine->data)) {
                $this->serataDaId = $this->serataPrecedenteId($value)
                    ?? Serata::queryEdizione()->orderBy('data')->value('id');
            }
        }
        $this->serataConfrontoId = $this->serataDaId;
    }

    public function updatedSerataDaId(?int $value): void
    {
        $this->serataConfrontoId = $value;
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
        $edizione = Edizione::corrente();
        $serate = Serata::queryEdizione($edizione?->id)->orderBy('data')->get();
        $serata = $this->serataId ? Serata::query()->find($this->serataId) : null;
        if ($serata && $edizione && (int) $serata->edizione_id !== (int) $edizione->id) {
            $this->serataId = $serate->last()?->id;
            $serata = $this->serataId ? Serata::query()->find($this->serataId) : null;
        }
        $dati = [];

        if ($serata) {
            // Completo = tutta l’edizione; senza spunta = solo la serata selezionata.
            $serateAmbito = $this->completo
                ? $serate
                : $serate->where('id', $serata->id)->values();

            $idsAmbito = $serateAmbito->pluck('id');
            $idsStasera = collect([$serata->id]);

            $dati = match ($this->tipo) {
                'cumulativo' => $this->datiReparto($idsStasera, $idsAmbito, $serata, null),
                'cucina_1' => $this->datiReparto($idsStasera, $idsAmbito, $serata, 'cucina_1'),
                'cucina_2' => $this->datiReparto($idsStasera, $idsAmbito, $serata, 'cucina_2'),
                'griglia' => $this->datiReparto($idsStasera, $idsAmbito, $serata, 'griglia'),
                'bevande' => $this->datiBevande($idsStasera, $idsAmbito),
                'statistiche' => $this->datiStatistiche($serateAmbito),
                'economico' => $this->datiEconomico($serateAmbito),
                'consegna' => $this->datiConsegna($serata),
                'confronto' => $this->datiConfrontoRange($serate, $serata),
                default => [],
            };
        }

        return view('livewire.report.hub', [
            'serate' => $serate,
            'serata' => $serata,
            'edizione' => $edizione,
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

        return Serata::queryEdizione($corrente->edizione_id)
            ->where('data', '<', $corrente->data->toDateString())
            ->orderByDesc('data')
            ->value('id');
    }

    /**
     * Riepilogo su un range di serate (da → a), con totali/medie e top piatti.
     *
     * @param  Collection<int, Serata>  $tutte
     */
    private function datiConfrontoRange(Collection $tutte, Serata $serataFine): array
    {
        $inizio = $this->serataDaId
            ? Serata::query()->find($this->serataDaId)
            : null;

        if (! $inizio) {
            $inizio = $serataFine;
        }

        if ($inizio->data->gt($serataFine->data)) {
            [$inizio, $serataFine] = [$serataFine, $inizio];
        }

        $range = $tutte
            ->filter(fn (Serata $s) => $s->data->gte($inizio->data) && $s->data->lte($serataFine->data))
            ->values();

        $righe = $range->map(fn (Serata $s) => $this->riepilogoSerataBreve($s))->all();
        $n = max(1, count($righe));
        $totCoperti = array_sum(array_column($righe, 'coperti'));
        $totComande = array_sum(array_column($righe, 'comande'));
        $totIncasso = round(array_sum(array_column($righe, 'incasso')), 2);
        $totContante = round(array_sum(array_column($righe, 'contante')), 2);
        $totPos = round(array_sum(array_column($righe, 'pos')), 2);

        $idsRange = $range->pluck('id');
        $dettaglio = $this->venditeDettaglioPerItem($idsRange);
        $nomi = MenuItem::query()->whereIn('id', array_keys($dettaglio['qta']))->pluck('nome', 'id');
        $piatti = [];
        foreach ($dettaglio['qta'] as $id => $qta) {
            $piatti[] = [
                'nome' => $nomi[$id] ?? ('#'.$id),
                'qta' => (int) $qta,
                'incasso' => (float) ($dettaglio['incasso'][$id] ?? 0),
            ];
        }
        usort($piatti, fn ($x, $y) => $y['qta'] <=> $x['qta']);
        $piatti = array_slice($piatti, 0, 15);

        $prima = $righe[0] ?? null;
        $ultima = $righe[count($righe) - 1] ?? null;
        $delta = ($prima && $ultima && count($righe) >= 2)
            ? [
                'coperti' => $ultima['coperti'] - $prima['coperti'],
                'comande' => $ultima['comande'] - $prima['comande'],
                'incasso' => round($ultima['incasso'] - $prima['incasso'], 2),
            ]
            : null;

        return [
            'label_da' => $inizio->data->format('d/m/Y'),
            'label_a' => $serataFine->data->format('d/m/Y'),
            'righe' => $righe,
            'totale' => [
                'coperti' => $totCoperti,
                'comande' => $totComande,
                'incasso' => $totIncasso,
                'contante' => $totContante,
                'pos' => $totPos,
            ],
            'media' => [
                'coperti' => round($totCoperti / $n, 1),
                'comande' => round($totComande / $n, 1),
                'incasso' => round($totIncasso / $n, 2),
            ],
            'delta_estremi' => $delta,
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

        $bevande = $items->where('bar', false)->values();
        $bar = $items->where('bar', true)->values();
        $bevandeIds = $bevande->pluck('id');
        $barIds = $bar->pluck('id');

        $riepilogo = [
            'bar_stasera' => $this->incassoBar($idsStasera, true, $barIds),
            'non_bar_stasera' => $this->incassoBar($idsStasera, false, $bevandeIds),
            'bar_cumulato' => $this->incassoBar($idsFino, true, $barIds),
            'non_bar_cumulato' => $this->incassoBar($idsFino, false, $bevandeIds),
            'bevande_stasera_qta' => $this->sommaQtaMappa($s['qta'], $bevandeIds),
            'bevande_cumulato_qta' => $this->sommaQtaMappa($c['qta'], $bevandeIds),
            'bar_stasera_qta' => $this->sommaQtaMappa($s['qta'], $barIds),
            'bar_cumulato_qta' => $this->sommaQtaMappa($c['qta'], $barIds),
        ];

        $sezioni = [
            [
                'key' => 'bevande',
                'label' => 'Bevande',
                'descrizione' => 'Voci da tavolo / servizio (non bar)',
                'items' => $bevande,
                'stasera' => $riepilogo['non_bar_stasera'],
                'cumulato' => $riepilogo['non_bar_cumulato'],
                'stasera_qta' => $riepilogo['bevande_stasera_qta'],
                'cumulato_qta' => $riepilogo['bevande_cumulato_qta'],
            ],
            [
                'key' => 'bar',
                'label' => 'Bar',
                'descrizione' => 'Voci servite al bar',
                'items' => $bar,
                'stasera' => $riepilogo['bar_stasera'],
                'cumulato' => $riepilogo['bar_cumulato'],
                'stasera_qta' => $riepilogo['bar_stasera_qta'],
                'cumulato_qta' => $riepilogo['bar_cumulato_qta'],
            ],
        ];

        return [
            'items' => $items,
            'sezioni' => $sezioni,
            'stasera_qta' => $s['qta'],
            'stasera_incasso' => $s['incasso'],
            'cumulato_qta' => $c['qta'],
            'cumulato_incasso' => $c['incasso'],
            'riepilogo' => $riepilogo,
        ];
    }

    /**
     * @param  array<int, int>  $qtaMap
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     */
    private function sommaQtaMappa(array $qtaMap, Collection $ids): int
    {
        $tot = 0;
        foreach ($ids as $id) {
            $tot += (int) ($qtaMap[(int) $id] ?? 0);
        }

        return $tot;
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
            return ['errore' => 'Seleziona un punto cassa per il foglio consegna.'];
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
        $coperti = (int) Comanda::query()
            ->where('serata_id', $serata->id)
            ->where('punto_cassa_id', $punto->id)
            ->where('stato', 'stampata')
            ->sum('coperti');

        return [
            'punto' => $punto,
            'chiusura' => $chiusura,
            'ric' => $ric,
            'tagli' => Chiusura::TAGLI,
            'coperti' => $coperti,
        ];
    }
}
