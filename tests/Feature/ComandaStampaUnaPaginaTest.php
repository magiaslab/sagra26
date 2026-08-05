<?php

use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;

beforeEach(function () {
    $this->seed();
});

it('il css di stampa forza una sola pagina per la comanda', function () {
    $css = file_get_contents(public_path('css/print.css'));

    expect($css)
        ->toContain('size: 297mm 210mm')
        ->toContain('position: fixed !important')
        ->toContain('page-break-after: avoid !important')
        ->toContain('padding-bottom: 10.5mm')
        ->toContain('grid-template-columns: 1fr 1fr 1fr');
});

it('la pagina stampa comanda include il foglio e lo script una-pagina', function () {
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

    expect($html)
        ->toContain('print-sheet')
        ->toContain('preparaStampaUnaPagina')
        ->toContain('beforeprint');
});
