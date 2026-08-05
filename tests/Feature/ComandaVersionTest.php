<?php

use App\Exceptions\ComandaConflittoException;
use App\Models\Comanda;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('rifiuta una seconda correzione con version stantia', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail();

    $comanda = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    expect($comanda->version)->toBe(1);
    $versionIniziale = $comanda->version;

    $prima = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $coca->id, 'quantita' => 1]],
        0,
        'contante',
        null,
        null,
        $comanda,
        null,
        $versionIniziale,
    );
    expect($prima->version)->toBe(2);

    expect(fn () => $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 3]],
        0,
        'contante',
        null,
        null,
        $comanda->fresh(),
        null,
        $versionIniziale, // stantia
    ))->toThrow(ComandaConflittoException::class);

    expect($comanda->fresh()->version)->toBe(2);
});

it('accetta una correzione con version aggiornata dopo richiamo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail();

    $comanda = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    $dopoPrima = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $comanda,
        null,
        1,
    );
    expect($dopoPrima->version)->toBe(2);

    $dopoSeconda = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $coca->id, 'quantita' => 1]],
        0,
        'contante',
        null,
        null,
        $dopoPrima,
        null,
        2,
    );
    expect($dopoSeconda->version)->toBe(3);
});

it('crea comande nuove senza richiedere version', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    expect($comanda->version)->toBe(1)
        ->and($comanda->fresh()->version)->toBe(1);
});

it('il richiamo restituisce la version incrementata dopo una correzione', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $comanda,
        null,
        1,
    );

    $this->getJson(route('cassa.richiamo', $comanda->numero_progressivo))
        ->assertOk()
        ->assertJsonPath('version', 2)
        ->assertJsonPath('numero', $comanda->numero_progressivo);
});

it('la conferma HTTP risponde 409 in caso di conflitto di version', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $comanda,
        null,
        1,
    );

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $postazione->id])->assertOk();

    $this->postJson(route('cassa.conferma'), [
        'postazione_id' => $postazione->id,
        'coperti' => 0,
        'metodo_pagamento' => 'contante',
        'comanda_id' => $comanda->id,
        'version' => 1,
        'righe' => [['menu_item_id' => $acqua->id, 'quantita' => 5]],
    ])
        ->assertStatus(409)
        ->assertJsonPath('conflitto', true);
});
