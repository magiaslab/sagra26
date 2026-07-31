<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerataStock extends Model
{
    protected $table = 'serata_stock';

    protected $fillable = [
        'serata_id',
        'menu_item_id',
        'stock_iniziale',
        'stock_residuo',
    ];

    public function serata(): BelongsTo
    {
        return $this->belongsTo(Serata::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
