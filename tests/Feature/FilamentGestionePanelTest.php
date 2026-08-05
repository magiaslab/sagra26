<?php

use App\Models\MenuItem;

beforeEach(function () {
    $this->seed();
});

it('protegge il pannello Filament con PinGestione', function () {
    $this->get('/gestione-fi')->assertRedirect(route('gestione.pin'));
});

it('apre il dashboard Filament con sessione sbloccata', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get('/gestione-fi')
        ->assertOk();
});

it('espone la resource voci menù senza toccare /cassa', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get('/gestione-fi/menu')
        ->assertOk();

    $this->get(route('cassa'))->assertOk();
});

it('non intercetta le rotte cassa', function () {
    expect(MenuItem::query()->exists())->toBeTrue();

    $this->get('/cassa')->assertOk();
    $this->get('/gestione-fi/cassa')->assertNotFound();
});
