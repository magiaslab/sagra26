<?php

use App\Livewire\Gestione\MenuCrud;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('salva il flag congelato dal CRUD menù', function () {
    $item = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    Livewire::test(MenuCrud::class)
        ->call('edit', $item->id)
        ->set('congelato', true)
        ->call('salva');

    expect($item->fresh()->congelato)->toBeTrue();
});

it('mostra asterisco e nota sul talloncino cliente leggendo congelato live', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $cacciucchetto = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $cacciucchetto->update(['congelato' => true]);
    $acqua->update(['congelato' => false]);

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $cacciucchetto->id, 'quantita' => 1],
            ['menu_item_id' => $acqua->id, 'quantita' => 1],
        ],
        0,
        'contante',
    );

    $html = $this->get(route('cassa.stampa', $comanda))->assertOk()->getContent();

    expect($html)->toContain('Cacciucchetto *')
        ->and($html)->toContain('Acqua Naturale 1L')
        ->and($html)->not->toContain('Acqua Naturale 1L *')
        ->and($html)->toContain('* Pietanza che può contenere ingredienti congelati.');

    // Solo sul talloncino Cliente: cucina/cameriere/griglia restano senza asterisco.
    expect(substr_count($html, 'Cacciucchetto *'))->toBe(1);
});

it('toglie l asterisco alla ristampa se il flag menù viene rimosso (non storicizzato)', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $cacciucchetto = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    $cacciucchetto->update(['congelato' => true]);

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $cacciucchetto->id, 'quantita' => 1]],
        0,
        'contante',
    );

    expect($this->get(route('cassa.stampa', $comanda))->assertOk()->getContent())
        ->toContain('Cacciucchetto *');

    $cacciucchetto->update(['congelato' => false]);

    $html = $this->get(route('cassa.stampa', $comanda))->assertOk()->getContent();

    expect($html)->toContain('Cacciucchetto')
        ->and($html)->not->toContain('Cacciucchetto *')
        ->and($html)->not->toContain('* Pietanza che può contenere ingredienti congelati.');
});

it('mostra asterisco e nota sul facsimile solo se ci sono voci congelate', function () {
    $cacciucchetto = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();
    $cacciucchetto->update(['congelato' => false]);

    $senza = $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.menu.facsimile'))
        ->assertOk()
        ->getContent();
    expect($senza)->not->toContain('Cacciucchetto *')
        ->and($senza)->not->toContain('* Pietanza che può contenere ingredienti congelati.')
        ->and($senza)->toContain('facsimile-sheet');

    $cacciucchetto->update(['congelato' => true]);

    $con = $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.menu.facsimile'))
        ->assertOk()
        ->getContent();
    expect($con)->toContain('Cacciucchetto *')
        ->and($con)->toContain('* Pietanza che può contenere ingredienti congelati.')
        // Due metà sullo stesso foglio A4 (una pagina).
        ->and(substr_count($con, 'facsimile-half'))->toBe(2)
        ->and(substr_count($con, 'facsimile-sheet'))->toBe(1);
});
