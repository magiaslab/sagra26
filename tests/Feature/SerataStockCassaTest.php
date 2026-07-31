<?php

use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use App\Services\SerataService;
use App\Services\StockService;

beforeEach(function () {
    $this->seed();
});

it('trova la serata corrente per stato aperta anche con data futura', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $futura = now()->addDays(5)->toDateString();
    $serata = app(SerataService::class)->apri($futura, 'collaudo', [], [$puntoId => 50]);

    $corrente = Serata::corrente();

    expect($corrente)->not->toBeNull()
        ->and($corrente->id)->toBe($serata->id)
        ->and($corrente->data->toDateString())->toBe($futura)
        ->and($corrente->stato)->toBe('aperta');
});

it('crea le righe serata_stock in apertura per voci con stock_default', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    $cacciucchetto = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();
    $row = SerataStock::query()
        ->where('serata_id', $serata->id)
        ->where('menu_item_id', $cacciucchetto->id)
        ->first();

    expect($row)->not->toBeNull()
        ->and((int) $row->stock_iniziale)->toBe((int) $cacciucchetto->stock_default)
        ->and((int) $row->stock_residuo)->toBe((int) $cacciucchetto->stock_default);
});

it('assicuraStockLimitati ripara serata aperta senza righe stock', function () {
    $serata = Serata::query()->create([
        'data' => now()->toDateString(),
        'stato' => 'aperta',
    ]);
    expect(SerataStock::query()->where('serata_id', $serata->id)->count())->toBe(0);

    $stock = app(StockService::class);
    $stock->assicuraStockLimitati($serata->id);
    $mappa = $stock->mappaResidui($serata->id);

    $cacciucchetto = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    expect($mappa)->toHaveKey($cacciucchetto->id)
        ->and($mappa[$cacciucchetto->id])->toBe((int) $cacciucchetto->stock_default);
});

it('la cassa espone lo stock del cacciucchetto dopo assicura', function () {
    $serata = Serata::query()->create([
        'data' => now()->addDays(3)->toDateString(),
        'stato' => 'aperta',
    ]);

    $this->get('/cassa')
        ->assertOk()
        ->assertSee('rimasti', false)
        ->assertSee('Cacciucchetto');

    $cacciucchetto = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();
    expect(
        SerataStock::query()
            ->where('serata_id', $serata->id)
            ->where('menu_item_id', $cacciucchetto->id)
            ->exists()
    )->toBeTrue();
});
