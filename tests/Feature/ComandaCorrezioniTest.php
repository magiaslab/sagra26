<?php

use App\Models\Comanda;
use App\Models\ComandaCorrezione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\SerataStock;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('crea uno snapshot in comanda_correzioni quando si corregge una comanda', function () {
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
    $totalePrima = (float) $originale->totale;

    expect(ComandaCorrezione::query()->count())->toBe(0);

    $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $coca->id, 'quantita' => 1]],
        0,
        'contante',
        null,
        null,
        $originale,
        null, // motivo facoltativo
    );

    expect(ComandaCorrezione::query()->count())->toBe(1);

    $log = ComandaCorrezione::query()->first();
    expect($log->comanda_id)->toBe($originale->id)
        ->and($log->postazione_id)->toBe($postazione->id)
        ->and((float) $log->totale_precedente)->toBe($totalePrima)
        ->and($log->motivo)->toBeNull()
        ->and($log->righe_precedenti)->toHaveCount(1)
        ->and($log->righe_precedenti[0]['menu_item_id'])->toBe($acqua->id)
        ->and($log->righe_precedenti[0]['quantita'])->toBe(2)
        ->and($log->righe_precedenti[0]['nome'])->toBe('Acqua Naturale 1L');
});

it('fa rollback dello snapshot se la correzione fallisce per stock', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $cacciucco = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    SerataStock::query()
        ->where('serata_id', $serata->id)
        ->where('menu_item_id', $cacciucco->id)
        ->update(['stock_iniziale' => 1, 'stock_residuo' => 0]);

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    expect(fn () => $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $cacciucco->id, 'quantita' => 1]],
        0,
        'contante',
        null,
        null,
        $originale,
    ))->toThrow(RuntimeException::class);

    expect(ComandaCorrezione::query()->count())->toBe(0)
        ->and(Comanda::query()->find($originale->id)->righe)->toHaveCount(1)
        ->and(Comanda::query()->find($originale->id)->righe->first()->menu_item_id)->toBe($acqua->id);
});

it('non crea correzioni per una comanda nuova', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    expect(ComandaCorrezione::query()->count())->toBe(0);
});
