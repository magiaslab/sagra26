<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postazione extends Model
{
    protected $table = 'postazioni';

    protected $fillable = ['nome'];

    public function mappature(): HasMany
    {
        return $this->hasMany(PostazionePuntoCassa::class);
    }

    public function comande(): HasMany
    {
        return $this->hasMany(Comanda::class);
    }

    public function puntoCassaAttivo(?string $data = null): ?PuntoCassa
    {
        $data = $data ?? now()->toDateString();

        $mappa = PostazionePuntoCassa::query()
            ->where('postazione_id', $this->id)
            ->whereDate('valido_da', '<=', $data)
            ->orderByDesc('valido_da')
            ->first();

        return $mappa?->puntoCassa;
    }
}
