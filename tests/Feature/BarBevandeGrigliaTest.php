<?php

use App\Livewire\Report\ReportHub;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('snapshotta il flag bar sulla comanda_riga e non lo ricalcola dal menù', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $acqua->update(['bar' => true]);

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
    );

    $riga = $comanda->righe->first();
    expect($riga->bar)->toBeTrue();

    $acqua->update(['bar' => false]);
    $riga->refresh();

    expect($riga->bar)->toBeTrue()
        ->and((bool) MenuItem::query()->find($acqua->id)->bar)->toBeFalse();
});

it('calcola di cui Bar solo dalle righe con bar=true', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);

    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail(); // 2.00
    $coca = MenuItem::query()->where('nome', 'Coca-Cola Lattina')->firstOrFail(); // 2.50
    $acqua->update(['bar' => true]);
    $coca->update(['bar' => false]);

    $service->confermaEStampa($serata, $postazione, [
        ['menu_item_id' => $acqua->id, 'quantita' => 3], // bar 6.00
        ['menu_item_id' => $coca->id, 'quantita' => 2],  // non-bar 5.00
    ], 0, 'contante');

    expect(ReportHub::totaleBarPerSerate(collect([$serata->id])))->toBe(6.0);
});

it('il report Bevande include voci non-bar della categoria is_bevande', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();

    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $acqua->update(['bar' => false]);

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    $hub = new ReportHub;
    $method = new \ReflectionMethod(ReportHub::class, 'datiBevande');
    $method->setAccessible(true);
    $dati = $method->invoke($hub, collect([$serata->id]), collect([$serata->id]));

    $nomi = $dati['items']->pluck('nome');
    expect($nomi)->toContain('Acqua Naturale 1L')
        ->and($dati['items']->firstWhere('nome', 'Acqua Naturale 1L')->bar)->toBeFalse()
        ->and($dati['riepilogo']['non_bar_stasera'])->toBe(2.0)
        ->and($dati['riepilogo']['bar_stasera'])->toBe(0.0);
});

it('il report Griglia rispetta areaStampaEffettiva per categorie miste', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    $hub = new ReportHub;
    $method = new \ReflectionMethod(ReportHub::class, 'datiReparto');
    $method->setAccessible(true);
    $dati = $method->invoke($hub, collect([$serata->id]), collect([$serata->id]), $serata, 'griglia');

    $nomi = $dati['categorie']->flatMap(fn ($c) => $c->menuItems->pluck('nome'));

    expect($nomi)->toContain('Bistecca di Manzo 300/350 gr.')
        ->and($nomi)->not->toContain('Frittura di Mare')
        ->and($nomi)->toContain('Pesce alla Griglia (Orata)');

    $cucina = $method->invoke($hub, collect([$serata->id]), collect([$serata->id]), $serata, 'cucina_1');
    $nomiCucina = $cucina['categorie']->flatMap(fn ($c) => $c->menuItems->pluck('nome'));

    expect($nomiCucina)->toContain('Frittura di Mare')
        ->and($nomiCucina)->not->toContain('Bistecca di Manzo 300/350 gr.');
});
