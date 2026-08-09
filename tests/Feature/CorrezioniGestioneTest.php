<?php

use App\Livewire\Gestione\Correzioni;
use App\Models\ComandaCorrezione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('pagina correzioni elenca comanda modificata con pagamento prima e dopo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    $originale = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'pos',
    );

    $corretta = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $originale,
        'POS non funzionava',
        (int) $originale->version,
    );

    $snap = ComandaCorrezione::query()->latest('id')->first();
    expect($snap)->not->toBeNull()
        ->and($snap->pagamento_precedente['metodo'] ?? null)->toBe('pos')
        ->and((float) $snap->totale_precedente)->toBe(4.0)
        ->and($corretta->metodo_pagamento)->toBe('contante');

    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.correzioni'))
        ->assertOk()
        ->assertSee('Comande modificate')
        ->assertSee('#'.$corretta->numero_progressivo)
        ->assertSee('POS')
        ->assertSee('CONTANTE')
        ->assertSee('POS non funzionava');

    Livewire::test(Correzioni::class)
        ->set('serataId', $serata->id)
        ->assertSee('#'.$corretta->numero_progressivo)
        ->assertSee('Richiamo');
});

it('subnav e dashboard espongono il link comande modificate', function () {
    $html = $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('gestione/correzioni')
        ->toContain('Comande modificate');
});
