<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('in correzione attribuisce solo la differenza al metodo scelto (POS + contante)', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail(); // 2.00
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail(); // 2.50

    // Originale: 2 acque = 4.00 pagate POS
    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'pos',
    );
    expect((float) $originale->totale)->toBe(4.0)
        ->and($originale->metodo_pagamento)->toBe('pos');

    // Correzione: aggiunge 1 coca (+2.50) incassata in contante
    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 2],
            ['menu_item_id' => $coca->id, 'quantita' => 1],
        ],
        0,
        'contante', // solo la differenza
        null,
        null,
        $originale,
        'aggiunta coca',
    );

    expect((float) $corretta->totale)->toBe(6.5)
        ->and($corretta->metodo_pagamento)->toBe('misto')
        ->and((float) $corretta->importo_contante)->toBe(2.5)
        ->and((float) $corretta->importo_pos)->toBe(4.0)
        ->and($corretta->importoContanteEffettivo())->toBe(2.5)
        ->and($corretta->importoPosEffettivo())->toBe(4.0);
});

it('in correzione con restituzione contante su comanda POS registra il resto negativo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 3]], // 6.00 POS
        0,
        'pos',
    );

    // Toglie 1 acqua (−2.00) restituita in contante
    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $originale,
        'tolto un\'acqua',
    );

    expect((float) $corretta->totale)->toBe(4.0)
        ->and($corretta->metodo_pagamento)->toBe('misto')
        ->and((float) $corretta->importo_contante)->toBe(-2.0)
        ->and((float) $corretta->importo_pos)->toBe(6.0);
});

it('in correzione senza differenza mantiene il metodo di pagamento originale', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    // 2 acque = 4.00 POS; correzione a totale invariato
    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'pos',
    );

    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante', // ignorato se delta 0
        null,
        null,
        $originale,
        'ristampa',
    );

    expect((float) $corretta->totale)->toBe(4.0)
        ->and($corretta->metodo_pagamento)->toBe('pos')
        ->and($corretta->importo_contante)->toBeNull()
        ->and($corretta->importo_pos)->toBeNull();
});

it('in correzione con restituzione sullo stesso metodo resta metodo puro', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 3]],
        0,
        'contante',
    );

    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
        null,
        null,
        $originale,
    );

    expect((float) $corretta->totale)->toBe(2.0)
        ->and($corretta->metodo_pagamento)->toBe('contante')
        ->and($corretta->importoContanteEffettivo())->toBe(2.0)
        ->and($corretta->importoPosEffettivo())->toBe(0.0);
});
