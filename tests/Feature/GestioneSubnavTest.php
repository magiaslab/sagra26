<?php

beforeEach(function () {
    $this->seed();
});

it('mostra la subnav gestione come pulsanti con icone', function () {
    $html = $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('aria-label="Sezioni gestione"')
        ->toContain('Dashboard')
        ->toContain('Guida')
        ->toContain('bg-sagra text-white')
        ->toContain('<svg');
});
