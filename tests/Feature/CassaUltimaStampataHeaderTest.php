<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('la cassa espone in header l’importo dell’ultima comanda stampata', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
    );

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
    ])->assertOk();

    $html = $this->get(route('cassa'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('Ultima')
        ->toContain('ultimaStampataTotale: 4')
        ->toContain('ultimaStampataNumero: '.$comanda->numero_progressivo)
        ->toContain('bg-amber-300/30')
        ->toContain('richiedeSceltaPostazione: false');
});

it('il cambio postazione restituisce l’ultima stampata della postazione', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'pos',
    );

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
    ])
        ->assertOk()
        ->assertJsonPath('ultima_stampata.numero', $comanda->numero_progressivo)
        ->assertJsonPath('ultima_stampata.totale', 2);
});
