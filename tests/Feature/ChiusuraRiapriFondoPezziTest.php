<?php

use App\Livewire\Gestione\ChiusuraForm;
use App\Models\Chiusura;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\ChiusuraService;
use App\Services\RiconciliazioneService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('riapre la chiusura per correzione anche con serata chiusa', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $serataService = app(SerataService::class);
    $serata = $serataService->apri(now()->toDateString(), null, [], [$punto->id => 50]);

    app(ChiusuraService::class)->salva($serata, $punto, [
        'fondo_iniziale' => 50,
        'fondo_trattenuto' => 50,
        'n_50' => 1,
        'totale_pos' => 0,
        'totale_z' => 0,
    ]);
    $serataService->chiudi($serata);

    expect($serata->fresh()->stato)->toBe('chiusa')
        ->and(Chiusura::query()->where('serata_id', $serata->id)->first()->chiusa_at)->not->toBeNull();

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $punto->id)
        ->call('riapriPerCorreggere')
        ->assertHasNoErrors()
        ->assertSet('chiusuraCompletata', false);

    expect(Serata::corrente()?->id)->toBe($serata->id)
        ->and(Chiusura::query()->where('serata_id', $serata->id)->first()->chiusa_at)->toBeNull();
});

it('salva i pezzi del fondo e li usa come fondo trattenuto', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 50]);

    $chiusura = app(ChiusuraService::class)->salva($serata, $punto, [
        'fondo_iniziale' => 50,
        'n_20' => 5, // 100 contato
        'n_10' => 2,
        'pezzi_fondo' => [
            'n_050' => 20, // 10
            'n_020' => 10, // 2
            'n_010' => 10, // 1
        ],
        'fondo_trattenuto' => 999, // ignorato: vincono i pezzi fondo
        'totale_pos' => 0,
        'totale_z' => 0,
    ]);

    expect((float) $chiusura->fondo_trattenuto)->toBe(13.0)
        ->and($chiusura->pezziFondoNormalizzati()['n_050'])->toBe(20)
        ->and((float) $chiusura->contante_contato)->toBe(120.0)
        ->and((float) $chiusura->contante_consegnato)->toBe(107.0);
});

it('suggerisce il fondo successivo con composizione pezzi', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 40]);

    app(ChiusuraService::class)->salva($serata, $punto, [
        'fondo_iniziale' => 40,
        'n_20' => 3,
        'pezzi_fondo' => [
            'n_1' => 10,
            'n_050' => 20,
        ],
        'totale_pos' => 0,
        'totale_z' => 0,
    ]);

    $dettaglio = app(RiconciliazioneService::class)->fondoPrecedenteDettaglio($punto);

    expect($dettaglio)->not->toBeNull()
        ->and($dettaglio['importo'])->toBe(20.0)
        ->and($dettaglio['pezzi']['n_1'])->toBe(10)
        ->and($dettaglio['pezzi']['n_050'])->toBe(20)
        ->and($dettaglio['descrizione'])->toContain('10×1 €')
        ->and($dettaglio['descrizione'])->toContain('20×0,50 €');
});

it('la form chiusura sincronizza fondo trattenuto dai pezzi fondo', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 50]);

    Livewire::test(ChiusuraForm::class)
        ->set('puntoCassaId', $punto->id)
        ->set('pezziFondo.n_050', 4)
        ->set('pezziFondo.n_1', 2)
        ->assertSet('fondo_trattenuto', 4.0)
        ->call('applicaTotalePezziFondo')
        ->assertSet('fondo_trattenuto', 4.0)
        ->assertSee('Fondo cassa sera dopo')
        ->assertDontSee('Riapri per correggere conteggi');
});

it('dopo riapertura permette di correggere i pezzi e risalvare', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $serataService = app(SerataService::class);
    $serata = $serataService->apri(now()->toDateString(), null, [], [$punto->id => 50]);
    $chiusuraService = app(ChiusuraService::class);

    $chiusuraService->salva($serata, $punto, [
        'fondo_iniziale' => 50,
        'n_50' => 2,
        'fondo_trattenuto' => 50,
        'totale_pos' => 5,
        'totale_z' => 5,
    ]);
    $serataService->chiudi($serata);

    $chiusuraService->riapriPerCorrezione($serata->fresh(), $punto);

    $corretta = $chiusuraService->salva($serata->fresh(), $punto, [
        'fondo_iniziale' => 50,
        'n_50' => 3,
        'pezzi_fondo' => ['n_050' => 100],
        'totale_pos' => 5,
        'totale_z' => 5,
    ]);

    expect((int) $corretta->n_50)->toBe(3)
        ->and((float) $corretta->fondo_trattenuto)->toBe(50.0)
        ->and($corretta->chiusa_at)->not->toBeNull();
});
