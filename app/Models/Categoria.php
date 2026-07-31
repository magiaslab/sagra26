<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorie';

    protected $fillable = [
        'nome',
        'area_stampa',
        'is_bevande',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'is_bevande' => 'boolean',
        ];
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('ordinamento');
    }
}
