<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('in stampa di correzione evidenzia aggiunte e barra le voci già in corso', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail();

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
    );

    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 2],
            ['menu_item_id' => $coca->id, 'quantita' => 1],
        ],
        0,
        'contante',
        null,
        null,
        $originale,
        'aggiunta coca',
    );

    $diff = $corretta->fresh(['righe.menuItem', 'correzioni'])->diffUltimaCorrezione();
    expect($diff)->not->toBeNull()
        ->and($diff['delta_importo'])->toBe(2.5)
        ->and(collect($diff['voci'])->firstWhere('menu_item_id', $acqua->id)['stato'])->toBe('invariata')
        ->and(collect($diff['voci'])->firstWhere('menu_item_id', $coca->id)['stato'])->toBe('aggiunta');

    $html = $this->get(route('cassa.stampa', $corretta))->assertOk()->getContent();
    expect($html)->toContain('CORREZIONE — già in corso')
        ->and($html)->toContain('tag-voce--invariata')
        ->and($html)->toContain('tag-voce--aggiunta')
        ->and($html)->toContain('AGGIUNTA')
        ->and($html)->toContain('già ok')
        ->and($html)->toContain('Da chiedere')
        ->and($html)->toContain('Barrato = già in corso');
});

it('in stampa di correzione mostra le voci tolte barrate', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail();

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 1],
            ['menu_item_id' => $coca->id, 'quantita' => 1],
        ],
        0,
        'pos',
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
        'tolta coca',
    );

    $html = $this->get(route('cassa.stampa', $corretta))->assertOk()->getContent();
    expect($html)->toContain('TOLTA')
        ->and($html)->toContain('tag-voce--tolta')
        ->and($html)->toContain('tag-voce--invariata')
        ->and($html)->toContain('Da restituire')
        ->and($html)->toContain('Coca-Cola Lattina');
});

it('comanda nuova senza correzioni non mostra banner correzione', function () {
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

    $html = $this->get(route('cassa.stampa', $comanda))->assertOk()->getContent();
    expect($html)->not->toContain('CORREZIONE — già in corso')
        ->and($html)->not->toContain('tag-voce--aggiunta');
});
