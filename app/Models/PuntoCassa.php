<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuntoCassa extends Model
{
    protected $table = 'punti_cassa';

    protected $fillable = ['nome', 'attivo'];

    protected function casts(): array
    {
        return [
            'attivo' => 'boolean',
        ];
    }

    public function mappature(): HasMany
    {
        return $this->hasMany(PostazionePuntoCassa::class);
    }

    public function chiusure(): HasMany
    {
        return $this->hasMany(Chiusura::class);
    }

    public function comande(): HasMany
    {
        return $this->hasMany(Comanda::class);
    }
}
