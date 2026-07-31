<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chiusura extends Model
{
    protected $table = 'chiusure';

    protected $fillable = [
        'serata_id',
        'punto_cassa_id',
        'fondo_iniziale',
        'n_100', 'n_50', 'n_20', 'n_10', 'n_5', 'n_2', 'n_1',
        'n_050', 'n_020', 'n_010', 'n_005', 'n_002', 'n_001',
        'contante_contato',
        'fondo_trattenuto',
        'contante_consegnato',
        'totale_pos',
        'totale_z',
        'note',
        'chiusa_at',
    ];

    protected function casts(): array
    {
        return [
            'fondo_iniziale' => 'decimal:2',
            'contante_contato' => 'decimal:2',
            'fondo_trattenuto' => 'decimal:2',
            'contante_consegnato' => 'decimal:2',
            'totale_pos' => 'decimal:2',
            'totale_z' => 'decimal:2',
            'chiusa_at' => 'datetime',
        ];
    }

    public const TAGLI = [
        'n_100' => 100.00,
        'n_50' => 50.00,
        'n_20' => 20.00,
        'n_10' => 10.00,
        'n_5' => 5.00,
        'n_2' => 2.00,
        'n_1' => 1.00,
        'n_050' => 0.50,
        'n_020' => 0.20,
        'n_010' => 0.10,
        'n_005' => 0.05,
        'n_002' => 0.02,
        'n_001' => 0.01,
    ];

    public function serata(): BelongsTo
    {
        return $this->belongsTo(Serata::class);
    }

    public function puntoCassa(): BelongsTo
    {
        return $this->belongsTo(PuntoCassa::class);
    }

    public function calcolaContanteContato(): float
    {
        $totale = 0.0;
        foreach (self::TAGLI as $campo => $valore) {
            $totale += ((int) $this->{$campo}) * $valore;
        }

        return round($totale, 2);
    }

    public function incassoContanteReale(): float
    {
        return round((float) $this->contante_contato - (float) $this->fondo_iniziale, 2);
    }
}
