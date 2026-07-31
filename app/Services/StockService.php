<?php

namespace App\Services;

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

        $mancanti = \App\Models\MenuItem::query()
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
