<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Serata extends Model
{
    protected $table = 'serate';

    protected $fillable = [
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
        return static::query()->where('stato', 'aperta')->latest('data')->first();
    }
}
