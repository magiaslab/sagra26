<?php

use App\Livewire\Gestione\MenuCrud;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('migra le aree cucina legacy a cucina_1 e espone cucina_2 nel CRUD', function () {
    expect(MenuItem::query()->where('nome', 'Frittura di Mare')->firstOrFail()->areaStampaEffettiva())
        ->toBe('cucina_1')
        ->and(MenuItem::query()->where('nome', 'Bistecca di Manzo 300/350 gr.')->firstOrFail()->areaStampaEffettiva())
        ->toBe('griglia');

    Livewire::test(MenuCrud::class)
        ->assertSee('cucina 1')
        ->assertSee('cucina 2')
        ->assertSee('Comunicazione sulle comande');
});

it('salva la comunicazione comanda dal CRUD menù e la stampa sul cliente', function () {
    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(MenuCrud::class)
        ->set('comunicazione_comanda', 'Ritirare le bevande al bar')
        ->call('salvaComunicazione')
        ->assertSet('comunicazione_comanda', 'Ritirare le bevande al bar');

    expect(Impostazione::corrente()->fresh()->comunicazione_comanda)
        ->toBe('Ritirare le bevande al bar');

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

    expect($html)->toContain('tag-comunicazione')
        ->and($html)->toContain('Ritirare le bevande al bar')
        ->and($html)->toContain('CUCINA 1')
        ->and($html)->toContain('CUCINA 2')
        ->and($html)->toContain('GRIGLIA')
        ->and($html)->toContain('tag-produzione')
        ->and($html)->not->toContain('tag-griglia-head');
});

it('non mostra il blocco comunicazione se il testo è vuoto', function () {
    Impostazione::corrente()->update(['comunicazione_comanda' => null]);

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

    expect($this->get(route('cassa.stampa', $comanda))->assertOk()->getContent())
        ->not->toContain('tag-comunicazione');
});

it('assegna una voce a cucina_2 e la stampa nel box corretto', function () {
    $item = MenuItem::query()->where('nome', 'Patate Fritte')->firstOrFail();
    $item->update(['area_stampa' => 'cucina_2']);

    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $item->id, 'quantita' => 2]],
        0,
        'contante',
    );

    $html = $this->get(route('cassa.stampa', $comanda))->assertOk()->getContent();

    expect($html)->toContain('data-zona="cucina_2"')
        ->and($html)->toContain('Patate Fritte')
        ->and($html)->toContain('Cucina 2'); // etichetta nel riepilogo cameriere
});
