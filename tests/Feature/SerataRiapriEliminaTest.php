<?php

use App\Livewire\Gestione\ChiusuraForm;
use App\Livewire\Gestione\Serate;
use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\ChiusuraService;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('riapre una serata chiusa e la rende corrente', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    app(SerataService::class)->chiudi($serata);

    expect(Serata::corrente())->toBeNull();

    Livewire::test(Serate::class)
        ->call('riapri', $serata->id)
        ->assertHasNoErrors()
        ->assertRedirect(route('gestione.serate', absolute: false));

    expect(Serata::corrente()?->id)->toBe($serata->id)
        ->and($serata->fresh()->stato)->toBe('aperta');
});

it('non riapre se esiste già una serata aperta', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $service = app(SerataService::class);
    $prima = $service->apri(now()->subDay()->toDateString(), null, [], [$puntoId => 50]);
    $service->chiudi($prima);
    $service->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(Serate::class)
        ->call('riapri', $prima->id)
        ->assertSet('errore', fn ($e) => is_string($e) && str_contains($e, 'già una serata aperta'));

    expect($prima->fresh()->stato)->toBe('chiusa');
});

it('elimina una serata chiusa con comande e chiusure', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $service = app(SerataService::class);
    $serata = $service->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    $service->chiudi($serata);
    $id = $serata->id;

    Livewire::test(Serate::class)
        ->call('elimina', $id)
        ->assertRedirect(route('gestione.serate', absolute: false));

    expect(Serata::query()->find($id))->toBeNull()
        ->and(Comanda::query()->where('serata_id', $id)->count())->toBe(0)
        ->and(Chiusura::query()->where('serata_id', $id)->count())->toBe(0);
});

it('blocca il salvataggio chiusura su serata chiusa (protezione foglio consegna)', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $service = app(SerataService::class);
    $serata = $service->apri(now()->toDateString(), null, [], [$punto->id => 50]);
    $service->chiudi($serata);

    expect(fn () => app(ChiusuraService::class)->salva($serata->fresh(), $punto, [
        'fondo_iniziale' => 50,
        'fondo_trattenuto' => 50,
        'totale_pos' => 0,
        'totale_z' => 0,
    ]))->toThrow(RuntimeException::class, 'foglio consegna');

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $punto->id)
        ->call('salva')
        ->assertSet('errore', fn ($e) => is_string($e) && str_contains($e, 'Riapri'));
});

it('permette di salvare la chiusura dopo riapertura', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $service = app(SerataService::class);
    $serata = $service->apri(now()->toDateString(), null, [], [$punto->id => 50]);
    $service->chiudi($serata);
    $service->riapri($serata->fresh());

    $chiusura = app(ChiusuraService::class)->salva($serata->fresh(), $punto, [
        'fondo_iniziale' => 50,
        'fondo_trattenuto' => 50,
        'totale_pos' => 10,
        'totale_z' => 10,
        'pezzi_50' => 1,
    ]);

    expect($chiusura->chiusa_at)->not->toBeNull()
        ->and((float) $chiusura->totale_pos)->toBe(10.0);
});
