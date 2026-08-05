<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('il richiamo di un numero di serata precedente restituisce 404', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serataService = app(SerataService::class);
    $comandaService = app(ComandaService::class);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $serataVecchia = $serataService->apri(now()->subDay()->toDateString(), null, [], [$puntoId => 50]);
    $comandaVecchia = $comandaService->confermaEStampa(
        $serataVecchia,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    $serataService->chiudi($serataVecchia);

    $serataService->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    $this->getJson(route('cassa.richiamo', $comandaVecchia->numero_progressivo))
        ->assertStatus(404)
        ->assertJsonPath('error', 'Comanda non trovata.');
});

it('la correzione di una comanda di un\'altra serata fallisce senza toccare lo stock corrente', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serataService = app(SerataService::class);
    $comandaService = app(ComandaService::class);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $cacciucco = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    $serataVecchia = $serataService->apri(now()->subDay()->toDateString(), null, [], [$puntoId => 50]);
    $comandaVecchia = $comandaService->confermaEStampa(
        $serataVecchia,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    $serataService->chiudi($serataVecchia);

    $serataNuova = $serataService->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $stockPrima = (int) SerataStock::query()
        ->where('serata_id', $serataNuova->id)
        ->where('menu_item_id', $cacciucco->id)
        ->value('stock_residuo');

    expect(fn () => $comandaService->confermaEStampa(
        $serataNuova,
        $postazione,
        [['menu_item_id' => $cacciucco->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $comandaVecchia,
        'correzione cross-serata',
        $comandaVecchia->version,
    ))->toThrow(RuntimeException::class, 'Comanda non appartiene alla serata corrente');

    $stockDopo = (int) SerataStock::query()
        ->where('serata_id', $serataNuova->id)
        ->where('menu_item_id', $cacciucco->id)
        ->value('stock_residuo');

    expect($stockDopo)->toBe($stockPrima)
        ->and($comandaVecchia->fresh()->serata_id)->toBe($serataVecchia->id)
        ->and($comandaVecchia->fresh()->stato)->toBe('stampata');
});

it('annulla su comanda di serata precedente/chiusa fallisce e lascia la comanda stampata', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serataService = app(SerataService::class);
    $comandaService = app(ComandaService::class);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $serataVecchia = $serataService->apri(now()->subDay()->toDateString(), null, [], [$puntoId => 50]);
    $comandaVecchia = $comandaService->confermaEStampa(
        $serataVecchia,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    $totaleVecchio = (float) $comandaVecchia->totale;
    $serataService->chiudi($serataVecchia);

    // Nessuna serata aperta: annullo bloccato
    expect(fn () => $comandaService->annulla($comandaVecchia, 'troppo tardi'))
        ->toThrow(RuntimeException::class, 'Comanda non appartiene alla serata corrente');

    expect($comandaVecchia->fresh()->stato)->toBe('stampata')
        ->and((float) $comandaVecchia->fresh()->totale)->toBe($totaleVecchio);

    // Nuova serata aperta: stesso blocco (serata_id diverso)
    $serataService->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    expect(fn () => $comandaService->annulla($comandaVecchia->fresh(), 'troppo tardi'))
        ->toThrow(RuntimeException::class, 'Comanda non appartiene alla serata corrente');

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $postazione->id])->assertOk();

    $this->postJson(route('cassa.annulla', $comandaVecchia), ['motivo' => 'troppo tardi'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'Comanda non appartiene alla serata corrente, impossibile annullarla.');

    expect($comandaVecchia->fresh()->stato)->toBe('stampata')
        ->and(Serata::corrente()?->id)->not->toBe($serataVecchia->id);
});
