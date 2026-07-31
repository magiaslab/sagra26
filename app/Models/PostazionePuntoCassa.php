<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostazionePuntoCassa extends Model
{
    protected $table = 'postazione_punto_cassa';

    protected $fillable = [
        'postazione_id',
        'punto_cassa_id',
        'valido_da',
    ];

    protected function casts(): array
    {
        return [
            'valido_da' => 'date',
        ];
    }

    public function postazione(): BelongsTo
    {
        return $this->belongsTo(Postazione::class);
    }

    public function puntoCassa(): BelongsTo
    {
        return $this->belongsTo(PuntoCassa::class);
    }
}
