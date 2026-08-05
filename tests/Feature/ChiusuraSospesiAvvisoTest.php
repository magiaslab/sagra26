<?php

use App\Livewire\Gestione\ChiusuraForm;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('mostra il banner con conteggio e totale se ci sono sospesi aperti per serata e punto cassa', function () {
    $punti = PuntoCassa::query()->where('attivo', true)->orderBy('id')->get();
    expect($punti->count())->toBeGreaterThanOrEqual(1);

    $puntoId = $punti->first()->id;
    $fondi = $punti->mapWithKeys(fn ($p) => [$p->id => 50.0])->all();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], $fondi);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Luca',
        'Rossi',
        null,
    );

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $puntoId)
        ->assertSee('1 conto sospeso ancora aperto')
        ->assertSee('4,00 €')
        ->assertSee('Vai a Sospesi')
        ->assertSee(route('gestione.sospesi', absolute: false), false);
});

it('non mostra il banner senza sospesi aperti', function () {
    $puntoId = PuntoCassa::query()->where('attivo', true)->value('id');
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $puntoId)
        ->assertDontSee('conti sospesi ancora aperti')
        ->assertDontSee('conto sospeso ancora aperto')
        ->assertDontSee('Vai a Sospesi');
});

it('ignora sospesi di un altro punto cassa o di un altra serata', function () {
    $puntoA = PuntoCassa::query()->where('attivo', true)->orderBy('id')->firstOrFail();
    $puntoB = PuntoCassa::query()->create(['nome' => 'Cassa B test', 'attivo' => true]);

    $fondi = [
        $puntoA->id => 50.0,
        $puntoB->id => 50.0,
    ];

    $serata1 = app(SerataService::class)->apri(now()->toDateString(), null, [], $fondi);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $sospeso = app(ComandaService::class)->confermaEStampa(
        $serata1,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Mario',
        'Bianchi',
        null,
    );

    $sospeso->update(['punto_cassa_id' => $puntoA->id]);

    // Sul punto B della stessa serata: niente banner
    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata1->id)
        ->set('puntoCassaId', $puntoB->id)
        ->assertDontSee('conto sospeso ancora aperto')
        ->assertDontSee('conti sospesi ancora aperti');

    // Serata diversa: chiudi e apri un'altra
    app(SerataService::class)->chiudi($serata1);
    $serata2 = app(SerataService::class)->apri(now()->addDay()->toDateString(), null, [], $fondi);

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata2->id)
        ->set('puntoCassaId', $puntoA->id)
        ->assertDontSee('conto sospeso ancora aperto')
        ->assertDontSee('conti sospesi ancora aperti');
});

it('permette di salvare la chiusura anche con sospesi aperti', function () {
    $puntoId = PuntoCassa::query()->where('attivo', true)->value('id');
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Anna',
        'Verdi',
        null,
    );

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $puntoId)
        ->assertSee('conto sospeso ancora aperto')
        ->set('totale_pos', 0)
        ->set('totale_z', 0)
        ->call('salva')
        ->assertDispatched('toast', message: 'Chiusura salvata.', type: 'ok')
        ->assertSee('conto sospeso ancora aperto');
});
