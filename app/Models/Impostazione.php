<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impostazione extends Model
{
    protected $table = 'impostazioni';

    protected $fillable = [
        'intestazione_nome',
        'intestazione_anno',
        'intestazione_sottotitolo',
        'pin_gestione',
        'comunicazione_comanda',
        'chromium_path',
    ];

    public static function corrente(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'intestazione_nome' => 'Sagra del Cacciucchetto',
            'intestazione_anno' => '2026',
            'intestazione_sottotitolo' => 'A.S.D. Basket San Vincenzo · UISP Pallavolo · ASD Calcio San Vincenzo',
            'pin_gestione' => '1234',
            'chromium_path' => null,
        ]);
    }
}
