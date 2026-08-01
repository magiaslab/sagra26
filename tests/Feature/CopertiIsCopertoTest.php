<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;
use App\Livewire\Gestione\MenuCrud;

beforeEach(function () {
    $this->seed();
});

it('ricalcola i coperti dal flag is_coperto ignorando il valore client', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $coperto = MenuItem::query()->where('is_coperto', true)->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $coperto->id, 'quantita' => 3],
            ['menu_item_id' => $acqua->id, 'quantita' => 2],
        ],
        99, // valore client falsificato: deve essere ignorato
        'contante',
    );

    expect($comanda->coperti)->toBe(3);
});

it('mette a zero i coperti se nessuna riga è is_coperto', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        5,
        'contante',
    );

    expect($comanda->coperti)->toBe(0);
});

it('il seeder marca Coperto con is_coperto e la cassa lo espone nel payload', function () {
    $coperto = MenuItem::query()->where('nome', 'Coperto')->firstOrFail();
    expect($coperto->is_coperto)->toBeTrue();

    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    $html = $this->get(route('cassa'))->assertOk()->getContent();
    // Blade escapa le virgolette dell'attributo x-data.
    expect($html)->toContain('is_coperto&quot;:true');
});

it('impedisce due voci attive con is_coperto', function () {
    $coperto = MenuItem::query()->where('is_coperto', true)->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    Livewire::test(MenuCrud::class)
        ->call('edit', $acqua->id)
        ->set('is_coperto', true)
        ->set('attivo', true)
        ->call('salva')
        ->assertHasErrors(['is_coperto']);

    expect($acqua->fresh()->is_coperto)->toBeFalse()
        ->and($coperto->fresh()->is_coperto)->toBeTrue();
});
