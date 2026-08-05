<?php

use App\Models\Comanda;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('lo storico elenca comande stampate e annullate della serata', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $a = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    $b = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'pos',
    );
    $service->annulla($b, 'errore cassiere');

    $this->getJson(route('cassa.storico'))
        ->assertOk()
        ->assertJsonCount(2, 'comande')
        ->assertJsonPath('comande.0.numero', $b->numero_progressivo)
        ->assertJsonPath('comande.0.stato', 'annullata')
        ->assertJsonPath('comande.0.motivo_annullo', 'errore cassiere')
        ->assertJsonPath('comande.1.numero', $a->numero_progressivo)
        ->assertJsonPath('comande.1.stato', 'stampata')
        ->assertJsonPath('comande.1.print_url', route('cassa.stampa', $a, absolute: false));
});

it('annulla richiede motivo di almeno 2 caratteri e rende la comanda non richiamabile', function () {
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

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $postazione->id])->assertOk();

    $this->postJson(route('cassa.annulla', $comanda), ['motivo' => 'x'])
        ->assertStatus(422);

    $this->postJson(route('cassa.annulla', $comanda), ['motivo' => 'sbaglio'])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($comanda->fresh()->stato)->toBe('annullata')
        ->and($comanda->fresh()->motivo_annullo)->toBe('sbaglio');

    $this->getJson(route('cassa.richiamo', $comanda->numero_progressivo))
        ->assertStatus(422)
        ->assertJsonPath('error', 'Comanda annullata.');
});

it('lo storico è vuoto senza serata aperta', function () {
    expect(Comanda::query()->count())->toBe(0);

    $this->getJson(route('cassa.storico'))
        ->assertOk()
        ->assertJsonPath('comande', []);
});
