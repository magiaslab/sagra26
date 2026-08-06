<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed();
});

it('stampa cliente mostra badge sospeso con nominativo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Cassiere',
        'Luca Bianchi',
        null,
    );

    $html = $this->get(route('cassa.stampa', $comanda))->assertOk()->getContent();

    expect($html)
        ->toContain('SOSPESO')
        ->toContain('Luca Bianchi')
        ->toContain('pay-badge--sospeso');
});

it('dopo il fix migration un DB con CHECK vecchio accetta e stampa i sospesi', function () {
    // Simula DB rimasto con CHECK pre-omaggio/sospeso (causa dell’errore SQL in stampa).
    DB::statement('PRAGMA foreign_keys=OFF');
    DB::statement('DROP TABLE IF EXISTS comande');
    DB::statement('CREATE TABLE comande (
      id integer primary key autoincrement not null,
      numero_progressivo integer not null,
      serata_id integer not null,
      postazione_id integer not null,
      punto_cassa_id integer not null,
      coperti integer not null default 0,
      stato varchar check (stato in ("aperta", "stampata", "annullata")) not null default "aperta",
      version integer not null default 1,
      metodo_pagamento varchar check (metodo_pagamento in ("contante", "pos", "misto")),
      importo_contante numeric, importo_pos numeric, totale numeric not null default 0,
      motivo_annullo text, tavolo varchar, note varchar,
      created_at datetime, updated_at datetime,
      autorizzato_da varchar, nominativo varchar, pagamento_note varchar,
      sospeso_chiuso_at datetime, era_sospeso tinyint(1) not null default 0,
      foreign key("serata_id") references "serate"("id"),
      foreign key("postazione_id") references "postazioni"("id"),
      foreign key("punto_cassa_id") references "punti_cassa"("id")
    )');
    DB::statement('PRAGMA foreign_keys=ON');

    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    try {
        app(ComandaService::class)->confermaEStampa(
            $serata,
            $postazione,
            [['menu_item_id' => $acqua->id, 'quantita' => 1]],
            0,
            'sospeso',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'Cassiere',
            'Mario',
            null,
        );
        expect(false)->toBeTrue('doveva fallire con CHECK vecchio');
    } catch (Throwable $e) {
        expect($e->getMessage())->toContain('CHECK constraint failed');
    }

    $migration = require database_path('migrations/2026_08_06_205300_fix_comande_metodo_pagamento_sospeso.php');
    $migration->up();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Cassiere',
        'Mario',
        null,
    );

    expect($comanda->metodo_pagamento)->toBe('sospeso');

    $this->get(route('cassa.stampa', $comanda))
        ->assertOk()
        ->assertSee('SOSPESO')
        ->assertSee('Mario');
});
