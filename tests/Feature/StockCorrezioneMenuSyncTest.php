<?php

use App\Livewire\Gestione\MenuCrud;
use App\Livewire\Gestione\Serate;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\SerataStock;
use App\Services\ComandaService;
use App\Services\SerataService;
use App\Services\StockService;
use App\Models\Postazione;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('permette di diminuire lo stock in serata senza scendere sotto zero', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $item = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    $row = SerataStock::query()
        ->where('serata_id', $serata->id)
        ->where('menu_item_id', $item->id)
        ->firstOrFail();
    $iniziale = (int) $row->stock_iniziale;
    $residuo = (int) $row->stock_residuo;

    $aggiornato = app(StockService::class)->rifornisci($serata->id, $item->id, -10);

    expect((int) $aggiornato->stock_residuo)->toBe($residuo - 10)
        ->and((int) $aggiornato->stock_iniziale)->toBe($iniziale - 10);

    expect(fn () => app(StockService::class)->rifornisci($serata->id, $item->id, -(($residuo - 10) + 1)))
        ->toThrow(RuntimeException::class, 'Non puoi scendere sotto zero');
});

it('la ui serate applica una correzione negativa', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $item = MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->firstOrFail();
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(Serate::class)
        ->set("rifornimenti.{$item->id}", '-3')
        ->call('rifornisciStock', $item->id)
        ->assertDispatched('toast', message: 'Stock aggiornato (-3).', type: 'ok');
});

it('modificando stock_default nel menù aggiorna lo stock della serata aperta', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $item = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();
    $postazione = Postazione::query()->firstOrFail();

    // Vendi 2 pezzi → residuo scende
    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $item->id, 'quantita' => 2]],
        0,
        'contante',
    );

    $prima = SerataStock::query()
        ->where('serata_id', $serata->id)
        ->where('menu_item_id', $item->id)
        ->firstOrFail();
    $venduti = (int) $prima->stock_iniziale - (int) $prima->stock_residuo;
    expect($venduti)->toBe(2);

    $nuovoDefault = (int) $prima->stock_iniziale + 20;

    Livewire::test(MenuCrud::class)
        ->call('edit', $item->id)
        ->set('stock_default', (string) $nuovoDefault)
        ->call('salva');

    $dopo = $prima->fresh();
    expect((int) $dopo->stock_iniziale)->toBe($nuovoDefault)
        ->and((int) $dopo->stock_residuo)->toBe($nuovoDefault - 2)
        ->and((int) $item->fresh()->stock_default)->toBe($nuovoDefault);
});
