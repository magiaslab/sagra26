<?php

use App\Models\Comanda;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('calcola il numero di serata al volo senza toccare numero_progressivo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    $c1 = $service->confermaEStampa($serata, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');
    $c2 = $service->confermaEStampa($serata, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');

    expect($c1->numeroDiSerata())->toBe(1)
        ->and($c2->numeroDiSerata())->toBe(2)
        ->and($c2->numero_progressivo)->toBeGreaterThan($c1->numero_progressivo);

    $html = $this->get(route('cassa.stampa', $c2))->assertOk()->getContent();
    expect($html)->toContain('Comanda 2 di stasera')
        ->and($html)->toContain('rif. #'.$c2->numero_progressivo)
        ->and($html)->toContain('comanda num. #'.$c2->numero_progressivo)
        ->and(substr_count($html, 'comanda num. #'.$c2->numero_progressivo))->toBe(4) // 3 zone + cameriere
        ->and($html)->not->toContain('tag-right')
        ->and($html)->toContain('tag-produzione')
        ->and($html)->toContain('tag-cameriere');
});

it('azzera il numero di serata su una nuova serata', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $service = app(SerataService::class);
    $comandaService = app(ComandaService::class);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $serata1 = $service->apri(now()->subDay()->toDateString(), null, [], [$puntoId => 50]);
    $prima = $comandaService->confermaEStampa($serata1, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');
    $service->chiudi($serata1);

    $serata2 = $service->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $seconda = $comandaService->confermaEStampa($serata2, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');

    expect($prima->numeroDiSerata())->toBe(1)
        ->and($seconda->numeroDiSerata())->toBe(1)
        ->and($seconda->numero_progressivo)->toBeGreaterThan($prima->numero_progressivo)
        ->and(Comanda::prossimoNumeroDiSerata($serata2->id))->toBe(2);
});

it('espone numero_di_serata nel richiamo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    $service->confermaEStampa($serata, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');
    $seconda = $service->confermaEStampa($serata, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');

    $this->getJson(route('cassa.richiamo', $seconda->numero_progressivo))
        ->assertOk()
        ->assertJsonPath('numero', $seconda->numero_progressivo)
        ->assertJsonPath('numero_di_serata', 2);
});
