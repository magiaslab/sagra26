<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed();
});

it('ripara FK comande_bak e permette stampa sospeso e omaggio', function () {
    // Simula il bug SQLite: RENAME comande → comande_bak aggiorna le FK figlie,
    // poi la tabella bak viene eliminata e resta "no such table: comande_bak".
    // Colpisce qualsiasi conferma (sospeso, omaggio, contante…) sull’insert righe.
    Schema::disableForeignKeyConstraints();
    Schema::rename('comande', 'comande_bak');
    Schema::create('comande', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('numero_progressivo')->unique();
        $table->foreignId('serata_id')->constrained('serate');
        $table->foreignId('postazione_id')->constrained('postazioni');
        $table->foreignId('punto_cassa_id')->constrained('punti_cassa');
        $table->integer('coperti')->default(0);
        $table->string('stato')->default('aperta');
        $table->unsignedInteger('version')->default(1);
        $table->string('metodo_pagamento', 20)->nullable();
        $table->decimal('importo_contante', 8, 2)->nullable();
        $table->decimal('importo_pos', 8, 2)->nullable();
        $table->decimal('totale', 8, 2)->default(0);
        $table->text('motivo_annullo')->nullable();
        $table->string('tavolo', 40)->nullable();
        $table->string('note', 255)->nullable();
        $table->string('autorizzato_da', 80)->nullable();
        $table->string('nominativo', 80)->nullable();
        $table->string('pagamento_note', 255)->nullable();
        $table->timestamp('sospeso_chiuso_at')->nullable();
        $table->boolean('era_sospeso')->default(false);
        $table->timestamps();
    });
    DB::statement('INSERT INTO comande SELECT * FROM comande_bak');
    Schema::drop('comande_bak');
    Schema::enableForeignKeyConstraints();

    $sqlRighe = (string) DB::selectOne("SELECT sql FROM sqlite_master WHERE name = 'comanda_righe'")->sql;
    expect($sqlRighe)->toContain('comande_bak');

    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $righe = [['menu_item_id' => $acqua->id, 'quantita' => 1]];

    foreach (['sospeso', 'omaggio'] as $metodo) {
        try {
            app(ComandaService::class)->confermaEStampa(
                $serata,
                $postazione,
                $righe,
                0,
                $metodo,
                null, null, null, null, null, null, null,
                'Cassiere',
                'Luca',
                null,
            );
            expect(false)->toBeTrue("doveva fallire con comande_bak per {$metodo}");
        } catch (Throwable $e) {
            expect($e->getMessage())->toContain('comande_bak');
        }
    }

    $migration = require database_path('migrations/2026_08_07_042500_fix_sqlite_fk_comande_bak.php');
    $migration->up();

    $sqlRighe = (string) DB::selectOne("SELECT sql FROM sqlite_master WHERE name = 'comanda_righe'")->sql;
    $sqlCorr = (string) DB::selectOne("SELECT sql FROM sqlite_master WHERE name = 'comanda_correzioni'")->sql;
    expect($sqlRighe)->not->toContain('comande_bak')
        ->and($sqlRighe)->toContain('references "comande"')
        ->and($sqlCorr)->not->toContain('comande_bak');

    $sospeso = app(ComandaService::class)->confermaEStampa(
        $serata->fresh(),
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'sospeso',
        null, null, null, null, null, null, null,
        'Cassiere',
        'Luca',
        null,
    );

    $omaggio = app(ComandaService::class)->confermaEStampa(
        $serata->fresh(),
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'omaggio',
        null, null, null, null, null, null, null,
        'Mario',
        'Ospite VIP',
        'prova',
    );

    expect($sospeso->metodo_pagamento)->toBe('sospeso')
        ->and($sospeso->righe)->toHaveCount(1)
        ->and($omaggio->metodo_pagamento)->toBe('omaggio')
        ->and($omaggio->righe)->toHaveCount(1)
        ->and($omaggio->autorizzato_da)->toBe('Mario')
        ->and($omaggio->nominativo)->toBe('Ospite VIP');

    $this->get(route('cassa.stampa', $sospeso))
        ->assertOk()
        ->assertSee('SOSPESO')
        ->assertSee('Luca');

    $this->get(route('cassa.stampa', $omaggio))
        ->assertOk()
        ->assertSee('OMAGGIO')
        ->assertSee('Ospite VIP');
});
