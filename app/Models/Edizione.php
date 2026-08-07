<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edizione extends Model
{
    protected $table = 'edizioni';

    protected $fillable = [
        'anno',
        'nome',
        'stato',
        'aperta_at',
        'chiusa_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'anno' => 'integer',
            'aperta_at' => 'datetime',
            'chiusa_at' => 'datetime',
        ];
    }

    public function serate(): HasMany
    {
        return $this->hasMany(Serata::class);
    }

    public function isAperta(): bool
    {
        return $this->stato === 'aperta';
    }

    public function isArchiviata(): bool
    {
        return $this->stato === 'archiviata';
    }

    public function etichetta(): string
    {
        $nome = trim((string) ($this->nome ?: ''));

        return $nome !== '' ? $nome : 'Sagra '.$this->anno;
    }

    /** Edizione operativa corrente (al più una aperta). */
    public static function corrente(): ?self
    {
        return static::query()
            ->where('stato', 'aperta')
            ->orderByDesc('anno')
            ->orderByDesc('id')
            ->first();
    }
}
