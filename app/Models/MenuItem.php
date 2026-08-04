<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    public const AREE_STAMPA = ['cliente', 'cucina_1', 'cucina_2', 'griglia'];

    protected $table = 'menu_items';

    protected $fillable = [
        'categoria_id',
        'nome',
        'prezzo',
        'attivo',
        'piatto_del_giorno',
        'bar',
        'congelato',
        'is_coperto',
        'stock_default',
        'area_stampa',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'prezzo' => 'decimal:2',
            'attivo' => 'boolean',
            'piatto_del_giorno' => 'boolean',
            'bar' => 'boolean',
            'congelato' => 'boolean',
            'is_coperto' => 'boolean',
            'stock_default' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function serataStocks(): HasMany
    {
        return $this->hasMany(SerataStock::class);
    }

    public function areaStampaEffettiva(): string
    {
        $area = $this->area_stampa ?? $this->categoria->area_stampa;

        return match ($area) {
            'cucina' => 'cucina_1', // legacy pre-split
            default => (string) $area,
        };
    }

    public static function etichettaArea(?string $area): string
    {
        return match ($area) {
            'cucina_1', 'cucina' => 'Cucina 1',
            'cucina_2' => 'Cucina 2',
            'griglia' => 'Griglia',
            'cliente' => 'Cliente',
            default => (string) $area,
        };
    }
}
