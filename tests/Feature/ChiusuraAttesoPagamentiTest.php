<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\RiconciliazioneService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('chiusura atteso esclude omaggio e sospeso aperto, include sconto e cambio metodo', function () {
    $punto = PuntoCassa::query()->first();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail(); // 2€
    $righe = [['menu_item_id' => $acqua->id, 'quantita' => 2]]; // 4€ lordo

    // 4€ POS
    $service->confermaEStampa($serata, $postazione, $righe, 0, 'pos');

    // 4€ omaggio → 0 in atteso
    $service->confermaEStampa(
        $serata, $postazione, $righe, 0, 'omaggio',
        null, null, null, null, null, null, null,
        'Mario', 'Ospite', null,
    );

    // 4€ sospeso aperto → 0 in atteso
    $service->confermaEStampa(
        $serata, $postazione, $righe, 0, 'sospeso',
        null, null, null, null, null, null, null,
        'Luca', 'Rossi', null,
    );

    // 4€ − 50% = 2€ contante
    $service->confermaEStampa(
        $serata, $postazione, $righe, 0, 'contante',
        null, null, null, null, null, null, null,
        'Mario', 'Scontato', null, 50,
    );

    // 4€ POS poi corretto a contante (stesso totale)
    $daCorreggere = $service->confermaEStampa($serata, $postazione, $righe, 0, 'pos');
    $service->confermaEStampa(
        $serata, $postazione, $righe, 0, 'contante',
        null, null, $daCorreggere, 'metodo sbagliato', (int) $daCorreggere->version,
    );

    $atteso = app(RiconciliazioneService::class)->attesoDaComande($serata, $punto);

    // POS 4 + contante sconto 2 + contante (ex POS) 4 = contante 6, pos 4
    expect($atteso['contante'])->toBe(6.0)
        ->and($atteso['pos'])->toBe(4.0)
        ->and($atteso['totale'])->toBe(10.0)
        ->and($atteso['n_omaggi'])->toBe(1)
        ->and($atteso['omaggi_valore'])->toBe(4.0)
        ->and($atteso['n_sospesi_aperti'])->toBe(1)
        ->and($atteso['n_sconti'])->toBe(1)
        ->and($atteso['sconti_valore'])->toBe(2.0)
        ->and($atteso['n_corrette'])->toBe(1);
});

it('chiusura atteso dopo chiusura sospeso e correzione con delta misto', function () {
    $punto = PuntoCassa::query()->first();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail(); // 2.50

    $sospeso = $service->confermaEStampa(
        $serata, $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0, 'sospeso',
        null, null, null, null, null, null, null,
        'Anna', 'Bianchi', null,
    );

    // Chiude sospeso in POS → 4€ POS
    $service->confermaEStampa(
        $serata, $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0, 'pos',
        null, null, $sospeso, 'saldo', (int) $sospeso->version,
    );

    // Comanda POS 4€ + aggiunta coca in contante → misto 2.50 + 4
    $base = $service->confermaEStampa(
        $serata, $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0, 'pos',
    );
    $service->confermaEStampa(
        $serata, $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 2],
            ['menu_item_id' => $coca->id, 'quantita' => 1],
        ],
        0, 'contante',
        null, null, $base, 'aggiunta', (int) $base->version,
    );

    $atteso = app(RiconciliazioneService::class)->attesoDaComande($serata, $punto);

    expect($atteso['contante'])->toBe(2.5)
        ->and($atteso['pos'])->toBe(8.0) // 4 (ex sospeso) + 4 (base)
        ->and($atteso['totale'])->toBe(10.5);
});
