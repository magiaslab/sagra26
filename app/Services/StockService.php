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
}
