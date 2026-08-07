<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Serata extends Model
{
    protected $table = 'serate';

    protected $fillable = [
        'edizione_id',
        'data',
        'stato',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
        ];
    }

    public function edizione(): BelongsTo
    {
        return $this->belongsTo(Edizione::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(SerataStock::class);
    }

    public function comande(): HasMany
    {
        return $this->hasMany(Comanda::class);
    }

    public function chiusure(): HasMany
    {
        return $this->hasMany(Chiusura::class);
    }

    public function isAperta(): bool
    {
        return $this->stato === 'aperta';
    }

    public static function corrente(): ?self
    {
        // Criterio = stato aperta, NON la data: serve anche nei collaudi
        // fatti prima del giorno reale della sagra.
        return static::query()
            ->where('stato', 'aperta')
            ->orderByDesc('id')
            ->first();
    }

    /** Serate dell’edizione operativa (o dell’id indicato). */
    public static function queryEdizione(?int $edizioneId = null): Builder
    {
        $id = $edizioneId ?? Edizione::corrente()?->id;

        $q = static::query();
        if ($id) {
            return $q->where('edizione_id', $id);
        }

        // Nessuna edizione aperta: non esporre lo storico di edizioni archiviate.
        return $q->whereRaw('0 = 1');
    }
}
