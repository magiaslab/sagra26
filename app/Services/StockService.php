<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\SerataStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function scala(int $serataId, int $menuItemId, int $delta): void
    {
        if ($delta <= 0) {
            return;
        }

        $updated = SerataStock::query()
            ->where('serata_id', $serataId)
            ->where('menu_item_id', $menuItemId)
            ->where('stock_residuo', '>=', $delta)
            ->update([
                'stock_residuo' => DB::raw("stock_residuo - {$delta}"),
            ]);

        if ($updated === 0) {
            $residuo = SerataStock::query()
                ->where('serata_id', $serataId)
                ->where('menu_item_id', $menuItemId)
                ->value('stock_residuo');

            throw new RuntimeException(
                'Stock insufficiente (rimasti: '.($residuo ?? 0).').'
            );
        }
    }

    public function restituisci(int $serataId, int $menuItemId, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        SerataStock::query()
            ->where('serata_id', $serataId)
            ->where('menu_item_id', $menuItemId)
            ->update([
                'stock_residuo' => DB::raw("stock_residuo + {$qty}"),
            ]);
    }

    /**
     * Corregge lo stock a serata aperta: qty positiva = rifornimento, negativa = diminuzione.
     * Aggiorna residuo e iniziale insieme così i pezzi già venduti restano coerenti.
     */
    public function rifornisci(int $serataId, int $menuItemId, int $qty): SerataStock
    {
        if ($qty === 0) {
            throw new RuntimeException('Indica una quantità diversa da zero (+ per aggiungere, − per togliere).');
        }

        return DB::transaction(function () use ($serataId, $menuItemId, $qty) {
            $row = SerataStock::query()
                ->where('serata_id', $serataId)
                ->where('menu_item_id', $menuItemId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                throw new RuntimeException('Voce senza stock per questa serata.');
            }

            $nuovoResiduo = (int) $row->stock_residuo + $qty;
            $nuovoIniziale = (int) $row->stock_iniziale + $qty;

            if ($nuovoResiduo < 0) {
                throw new RuntimeException(
                    'Non puoi scendere sotto zero (residuo attuale: '.(int) $row->stock_residuo.').'
                );
            }

            // Difesa: iniziale non sotto i pezzi già venduti.
            $venduti = max(0, (int) $row->stock_iniziale - (int) $row->stock_residuo);
            if ($nuovoIniziale < $venduti) {
                throw new RuntimeException(
                    'Non puoi ridurre sotto i pezzi già venduti ('.$venduti.').'
                );
            }

            $row->stock_residuo = $nuovoResiduo;
            $row->stock_iniziale = $nuovoIniziale;
            $row->save();

            return $row;
        });
    }

    /**
     * Allinea lo stock della serata aperta al nuovo stock_default del menù,
     * preservando i pezzi già venduti (residuo = max(0, default − venduti)).
     */
    public function sincronizzaDaMenuDefault(int $serataId, int $menuItemId, ?int $stockDefault): void
    {
        if ($stockDefault === null) {
            return;
        }

        if ($stockDefault < 0) {
            throw new RuntimeException('Stock default non valido.');
        }

        DB::transaction(function () use ($serataId, $menuItemId, $stockDefault) {
            $row = SerataStock::query()
                ->where('serata_id', $serataId)
                ->where('menu_item_id', $menuItemId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                SerataStock::query()->create([
                    'serata_id' => $serataId,
                    'menu_item_id' => $menuItemId,
                    'stock_iniziale' => $stockDefault,
                    'stock_residuo' => $stockDefault,
                ]);

                return;
            }

            $venduti = max(0, (int) $row->stock_iniziale - (int) $row->stock_residuo);
            $row->stock_iniziale = $stockDefault;
            $row->stock_residuo = max(0, $stockDefault - $venduti);
            $row->save();
        });
    }

    /**
     * @return array<int, int> menu_item_id => stock_residuo
     */
    public function mappaResidui(int $serataId): array
    {
        return SerataStock::query()
            ->where('serata_id', $serataId)
            ->pluck('stock_residuo', 'menu_item_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Crea le righe serata_stock mancanti per voci con stock_default
     * (es. serata aperta senza passare da SerataService::apri).
     */
    public function assicuraStockLimitati(int $serataId): void
    {
        $esistenti = SerataStock::query()
            ->where('serata_id', $serataId)
            ->pluck('menu_item_id')
            ->all();

        $mancanti = MenuItem::query()
            ->where('attivo', true)
            ->whereNotNull('stock_default')
            ->when($esistenti !== [], fn ($q) => $q->whereNotIn('id', $esistenti))
            ->get();

        foreach ($mancanti as $item) {
            $iniziale = (int) $item->stock_default;
            SerataStock::query()->create([
                'serata_id' => $serataId,
                'menu_item_id' => $item->id,
                'stock_iniziale' => $iniziale,
                'stock_residuo' => $iniziale,
            ]);
        }
    }
}
