<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaRiga extends Model
{
    protected $table = 'comanda_righe';

    protected $fillable = [
        'comanda_id',
        'menu_item_id',
        'quantita',
        'prezzo_unitario',
        'qta_scalata',
        'bar',
    ];

    protected function casts(): array
    {
        return [
            'prezzo_unitario' => 'decimal:2',
            'bar' => 'boolean',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function subtotale(): float
    {
        return (float) $this->quantita * (float) $this->prezzo_unitario;
    }
}
