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
}
