<?php

beforeEach(function () {
    $this->seed();
});

it('mostra guida e liberatoria nella dashboard gestione', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->assertSee('Documenti e aiuto')
        ->assertSee('Guida operativa cassa')
        ->assertSee('Liberatoria volontari minori');
});

it('apre la guida operativa in gestione dopo il pin', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.guida'))
        ->assertOk()
        ->assertSee('Guida operativa')
        ->assertSee('Accendere tutto');
});

it('scarica la liberatoria pdf', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.documenti.liberatoria'))
        ->assertOk()
        ->assertHeader('content-disposition')
        ->assertDownload('liberatoria-volontari-minori.pdf');
});

it('scarica il file della guida', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.documenti.guida.download'))
        ->assertOk()
        ->assertDownload('guida-operatore-cassa.md');
});

it('la guida richiede il pin gestione', function () {
    $this->get(route('gestione.guida'))
        ->assertRedirect(route('gestione.pin'));
});
