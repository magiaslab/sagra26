<?php

namespace Database\Seeders;

use App\Models\Impostazione;
use App\Models\Postazione;
use App\Models\PostazionePuntoCassa;
use App\Models\PuntoCassa;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Impostazione::query()->create([
            'intestazione_nome' => 'Sagra del Cacciucchetto',
            'intestazione_anno' => '2026',
            'intestazione_sottotitolo' => 'A.S.D. Basket San Vincenzo · UISP Pallavolo · ASD Calcio San Vincenzo',
            'pin_gestione' => '1234',
        ]);

        $punto = PuntoCassa::query()->create([
            'nome' => 'Cassetto unico',
            'attivo' => true,
        ]);

        $cassaA = Postazione::query()->create(['nome' => 'Cassa A']);
        $cassaB = Postazione::query()->create(['nome' => 'Cassa B']);

        // Data passata: resta valida per tutte le serate future senza dipendere da "oggi"
        $validoDa = '2020-01-01';

        PostazionePuntoCassa::query()->create([
            'postazione_id' => $cassaA->id,
            'punto_cassa_id' => $punto->id,
            'valido_da' => $validoDa,
        ]);

        PostazionePuntoCassa::query()->create([
            'postazione_id' => $cassaB->id,
            'punto_cassa_id' => $punto->id,
            'valido_da' => $validoDa,
        ]);
    }
}
