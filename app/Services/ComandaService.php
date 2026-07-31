<?php

namespace App\Services;

use App\Models\Comanda;
use App\Models\ComandaRiga;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\Serata;
use App\Models\SerataStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ComandaService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    /**
     * Allocates the next global progressive number atomically.
     */
    public function nextNumero(): int
    {
        return (int) DB::table('comanda_numeri')->insertGetId([]);
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $righe
     */
    public function confermaEStampa(
        Serata $serata,
        Postazione $postazione,
        array $righe,
        int $coperti,
        string $metodoPagamento,
        ?float $importoContante = null,
        ?float $importoPos = null,
        ?Comanda $esistente = null,
    ): Comanda {
        if (! $serata->isAperta()) {
            throw new RuntimeException('Nessuna serata aperta.');
        }

        if (! in_array($metodoPagamento, ['contante', 'pos', 'misto'], true)) {
            throw new RuntimeException('Metodo di pagamento non valido.');
        }

        $puntoCassa = $postazione->puntoCassaAttivo($serata->data->toDateString());
        if (! $puntoCassa) {
            throw new RuntimeException('Postazione non mappata a un punto cassa.');
        }

        $righeNormalizzate = $this->normalizzaRighe($righe);
        if ($righeNormalizzate === []) {
            throw new RuntimeException('Comanda vuota.');
        }

        return DB::transaction(function () use (
            $serata,
            $postazione,
            $puntoCassa,
            $righeNormalizzate,
            $coperti,
            $metodoPagamento,
            $importoContante,
            $importoPos,
            $esistente,
        ) {
            if ($esistente) {
                $comanda = Comanda::query()->lockForUpdate()->findOrFail($esistente->id);
                if ($comanda->isAnnullata()) {
                    throw new RuntimeException('Comanda annullata, non modificabile.');
                }
                $this->applicaDeltaStock($serata, $comanda, $righeNormalizzate);
                $comanda->righe()->delete();
            } else {
                // Stock PRIMA del numero: nextNumero() usa autoincrement su comanda_numeri
                // che non torna indietro al rollback → altrimenti bruceremmo progressivi.
                $this->applicaDeltaStock($serata, null, $righeNormalizzate);

                $comanda = new Comanda([
                    'numero_progressivo' => $this->nextNumero(),
                    'serata_id' => $serata->id,
                    'postazione_id' => $postazione->id,
                    'punto_cassa_id' => $puntoCassa->id,
                ]);
            }

            $totale = 0.0;
            $comanda->coperti = $coperti;
            $comanda->stato = 'stampata';
            $comanda->metodo_pagamento = $metodoPagamento;
            $comanda->importo_contante = $metodoPagamento === 'misto' ? $importoContante : null;
            $comanda->importo_pos = $metodoPagamento === 'misto' ? $importoPos : null;
            $comanda->totale = 0;
            $comanda->save();

            foreach ($righeNormalizzate as $riga) {
                $item = MenuItem::query()->findOrFail($riga['menu_item_id']);
                $sub = round($riga['quantita'] * (float) $item->prezzo, 2);
                $totale += $sub;

                ComandaRiga::query()->create([
                    'comanda_id' => $comanda->id,
                    'menu_item_id' => $item->id,
                    'quantita' => $riga['quantita'],
                    'prezzo_unitario' => $item->prezzo,
                    'qta_scalata' => $item->stock_default !== null ? $riga['quantita'] : 0,
                ]);
            }

            $comanda->totale = round($totale, 2);
            $comanda->save();

            return $comanda->load(['righe.menuItem.categoria', 'postazione', 'puntoCassa', 'serata']);
        });
    }

    public function annulla(Comanda $comanda, string $motivo): Comanda
    {
        return DB::transaction(function () use ($comanda, $motivo) {
            $comanda = Comanda::query()->lockForUpdate()->findOrFail($comanda->id);
            if ($comanda->isAnnullata()) {
                return $comanda;
            }

            foreach ($comanda->righe as $riga) {
                if ($riga->qta_scalata > 0) {
                    $this->stock->restituisci(
                        $comanda->serata_id,
                        $riga->menu_item_id,
                        $riga->qta_scalata,
                    );
                    $riga->qta_scalata = 0;
                    $riga->save();
                }
            }

            $comanda->stato = 'annullata';
            $comanda->motivo_annullo = $motivo;
            $comanda->save();

            return $comanda;
        });
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $righe
     * @return array<int, array{menu_item_id: int, quantita: int}>
     */
    private function normalizzaRighe(array $righe): array
    {
        $out = [];
        foreach ($righe as $riga) {
            $qty = (int) ($riga['quantita'] ?? 0);
            $id = (int) ($riga['menu_item_id'] ?? 0);
            if ($qty > 0 && $id > 0) {
                $out[] = ['menu_item_id' => $id, 'quantita' => $qty];
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $nuove
     */
    private function applicaDeltaStock(Serata $serata, ?Comanda $esistente, array $nuove): void
    {
        $precedenti = [];
        if ($esistente) {
            foreach ($esistente->righe as $riga) {
                $precedenti[$riga->menu_item_id] = $riga->qta_scalata;
            }
        }

        $nuoveMap = [];
        foreach ($nuove as $riga) {
            $item = MenuItem::query()->findOrFail($riga['menu_item_id']);
            if ($item->stock_default === null) {
                continue;
            }
            $nuoveMap[$item->id] = $riga['quantita'];
        }

        $ids = array_unique(array_merge(array_keys($precedenti), array_keys($nuoveMap)));
        foreach ($ids as $menuItemId) {
            $vecchia = $precedenti[$menuItemId] ?? 0;
            $nuova = $nuoveMap[$menuItemId] ?? 0;
            $delta = $nuova - $vecchia;
            if ($delta === 0) {
                continue;
            }
            if ($delta > 0) {
                $this->stock->scala($serata->id, $menuItemId, $delta);
            } else {
                $this->stock->restituisci($serata->id, $menuItemId, abs($delta));
            }
        }
    }
}
