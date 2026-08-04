<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('togliendo un piatto da 10€ restituisce esattamente 10€ anche se un altro prezzo menù è cambiato', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail(); // 2.00
    $tortelli = MenuItem::query()->where('nome', 'Tortelli al Ragù')->firstOrFail(); // 10.00

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 1],
            ['menu_item_id' => $tortelli->id, 'quantita' => 1],
        ],
        0,
        'contante',
    );
    expect((float) $originale->totale)->toBe(12.0);

    // Simula modifica prezzo menù dopo la vendita (non deve alterare il resto in correzione).
    $acqua->update(['prezzo' => 3.00]);

    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
        null,
        null,
        $originale->fresh(),
        'tolto tortello',
    );

    expect((float) $corretta->totale)->toBe(2.0)
        ->and((float) $corretta->righe->firstWhere('menu_item_id', $acqua->id)->prezzo_unitario)->toBe(2.0);

    $diff = $corretta->fresh(['righe.menuItem', 'correzioni'])->diffUltimaCorrezione();
    expect($diff['delta_importo'])->toBe(-10.0)
        ->and($diff['totale_precedente'])->toBe(12.0)
        ->and($diff['totale_attuale'])->toBe(2.0);

    $html = $this->get(route('cassa.stampa', $corretta))->assertOk()->getContent();
    expect($html)->toContain('Da restituire')
        ->and($html)->toContain('10,00')
        ->and($html)->not->toContain('Da restituire 9');
});

it('voci nuove in correzione usano il prezzo menù attuale', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail();

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'pos',
    );

    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 1],
            ['menu_item_id' => $coca->id, 'quantita' => 1],
        ],
        0,
        'contante',
        null,
        null,
        $originale,
    );

    expect((float) $corretta->totale)->toBe(4.5)
        ->and($corretta->fresh(['righe.menuItem', 'correzioni'])->diffUltimaCorrezione()['delta_importo'])->toBe(2.5);
});
