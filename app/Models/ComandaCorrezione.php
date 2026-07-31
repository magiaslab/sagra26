<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaCorrezione extends Model
{
    protected $table = 'comanda_correzioni';

    public const UPDATED_AT = null;

    protected $fillable = [
        'comanda_id',
        'postazione_id',
        'righe_precedenti',
        'totale_precedente',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'righe_precedenti' => 'array',
            'totale_precedente' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function postazione(): BelongsTo
    {
        return $this->belongsTo(Postazione::class);
    }
}
