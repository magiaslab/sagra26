<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comanda extends Model
{
    protected $table = 'comande';

    protected $fillable = [
        'numero_progressivo',
        'serata_id',
        'postazione_id',
        'punto_cassa_id',
        'coperti',
        'stato',
        'version',
        'metodo_pagamento',
        'importo_contante',
        'importo_pos',
        'totale',
        'motivo_annullo',
    ];

    protected function casts(): array
    {
        return [
            'totale' => 'decimal:2',
            'importo_contante' => 'decimal:2',
            'importo_pos' => 'decimal:2',
        ];
    }

    public function serata(): BelongsTo
    {
        return $this->belongsTo(Serata::class);
    }

    public function postazione(): BelongsTo
    {
        return $this->belongsTo(Postazione::class);
    }

    public function puntoCassa(): BelongsTo
    {
        return $this->belongsTo(PuntoCassa::class);
    }

    public function righe(): HasMany
    {
        return $this->hasMany(ComandaRiga::class);
    }

    public function correzioni(): HasMany
    {
        return $this->hasMany(ComandaCorrezione::class);
    }

    public function isAnnullata(): bool
    {
        return $this->stato === 'annullata';
    }

    /**
     * Posizione leggibile nella serata (1-based), calcolata al volo.
     * Non è una colonna: non sostituisce numero_progressivo.
     */
    public function numeroDiSerata(): int
    {
        return (int) static::query()
            ->where('serata_id', $this->serata_id)
            ->where('id', '<=', $this->id)
            ->count();
    }

    public static function prossimoNumeroDiSerata(?int $serataId): int
    {
        if (! $serataId) {
            return 1;
        }

        return (int) static::query()->where('serata_id', $serataId)->count() + 1;
    }

    /**
     * Coperti già fatti nella serata (comande stampate, tutte le postazioni).
     */
    public static function copertiTotaliSerata(?int $serataId): int
    {
        if (! $serataId) {
            return 0;
        }

        return (int) static::query()
            ->where('serata_id', $serataId)
            ->where('stato', 'stampata')
            ->sum('coperti');
    }

    public function importoContanteEffettivo(): float
    {
        return match ($this->metodo_pagamento) {
            'contante' => (float) $this->totale,
            'misto' => (float) ($this->importo_contante ?? 0),
            default => 0.0,
        };
    }

    public function importoPosEffettivo(): float
    {
        return match ($this->metodo_pagamento) {
            'pos' => (float) $this->totale,
            'misto' => (float) ($this->importo_pos ?? 0),
            default => 0.0,
        };
    }

    /**
     * Diff rispetto all’ultima correzione: voci invariate / aggiunte / tolte.
     * Usato in stampa per non far rifare tutta la comanda in cucina.
     *
     * @return array{
     *     count: int,
     *     motivo: ?string,
     *     totale_precedente: float,
     *     totale_attuale: float,
     *     delta_importo: float,
     *     voci: list<array{
     *         menu_item_id: int,
     *         nome: string,
     *         quantita: int,
     *         stato: 'invariata'|'aggiunta'|'tolta'|'aumentata'|'ridotta',
     *         delta_q: int,
     *         prezzo_unitario: float,
     *         area_stampa: ?string,
     *         congelato: bool
     *     }>
     * }|null
     */
    public function diffUltimaCorrezione(): ?array
    {
        if (! $this->relationLoaded('correzioni')) {
            $this->load('correzioni');
        }

        $ultima = $this->correzioni->sortByDesc('id')->first();
        if (! $ultima) {
            return null;
        }

        if (! $this->relationLoaded('righe')) {
            $this->load('righe.menuItem');
        } elseif ($this->righe->isNotEmpty() && ! $this->righe->first()->relationLoaded('menuItem')) {
            $this->load('righe.menuItem');
        }

        $prec = [];
        $nomiPrec = [];
        $prezziPrec = [];
        foreach ($ultima->righe_precedenti ?? [] as $r) {
            $id = (int) ($r['menu_item_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $prec[$id] = (int) ($r['quantita'] ?? 0);
            $nomiPrec[$id] = (string) ($r['nome'] ?? '');
            $prezziPrec[$id] = (float) ($r['prezzo_unitario'] ?? 0);
        }

        $att = [];
        foreach ($this->righe as $riga) {
            $att[(int) $riga->menu_item_id] = [
                'quantita' => (int) $riga->quantita,
                'nome' => $riga->menuItem->nome,
                'prezzo_unitario' => (float) $riga->prezzo_unitario,
                'area_stampa' => $riga->menuItem->areaStampaEffettiva(),
                'congelato' => (bool) $riga->menuItem->congelato,
            ];
        }

        $ids = array_values(array_unique(array_merge(array_keys($prec), array_keys($att))));
        sort($ids);

        // Area stampa per voci solo tolte (non più nelle righe attuali).
        $areeMancanti = [];
        $congelatiMancanti = [];
        $idsSoloTolti = array_values(array_filter(
            $ids,
            fn (int $id) => ! isset($att[$id]) && ($prec[$id] ?? 0) > 0,
        ));
        if ($idsSoloTolti !== []) {
            MenuItem::query()->whereIn('id', $idsSoloTolti)->get()
                ->each(function (MenuItem $item) use (&$areeMancanti, &$congelatiMancanti) {
                    $areeMancanti[$item->id] = $item->areaStampaEffettiva();
                    $congelatiMancanti[$item->id] = (bool) $item->congelato;
                });
        }

        $voci = [];
        foreach ($ids as $id) {
            $qPrec = $prec[$id] ?? 0;
            $qAtt = $att[$id]['quantita'] ?? 0;
            $deltaQ = $qAtt - $qPrec;

            if ($qPrec > 0 && $qAtt === 0) {
                $stato = 'tolta';
                $quantita = $qPrec;
            } elseif ($qPrec === 0 && $qAtt > 0) {
                $stato = 'aggiunta';
                $quantita = $qAtt;
            } elseif ($deltaQ > 0) {
                $stato = 'aumentata';
                $quantita = $qAtt;
            } elseif ($deltaQ < 0) {
                $stato = 'ridotta';
                $quantita = $qAtt;
            } else {
                $stato = 'invariata';
                $quantita = $qAtt;
            }

            $voci[] = [
                'menu_item_id' => $id,
                'nome' => $att[$id]['nome'] ?? ($nomiPrec[$id] ?: ('#'.$id)),
                'quantita' => $quantita,
                'stato' => $stato,
                'delta_q' => $deltaQ,
                'prezzo_unitario' => $att[$id]['prezzo_unitario'] ?? ($prezziPrec[$id] ?? 0.0),
                'area_stampa' => $att[$id]['area_stampa'] ?? ($areeMancanti[$id] ?? null),
                'congelato' => $att[$id]['congelato'] ?? ($congelatiMancanti[$id] ?? false),
            ];
        }

        $totalePrec = (float) $ultima->totale_precedente;
        $totaleAtt = (float) $this->totale;

        return [
            'count' => $this->correzioni->count(),
            'motivo' => $ultima->motivo,
            'totale_precedente' => $totalePrec,
            'totale_attuale' => $totaleAtt,
            'delta_importo' => round($totaleAtt - $totalePrec, 2),
            'voci' => $voci,
        ];
    }
}
