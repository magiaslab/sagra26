<?php

use App\Livewire\Report\ReportHub;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ChiusuraService;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('nasconde header UI in stampa e usa portrait per consegna', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    $html = Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('tipo', 'consegna')
        ->html();

    expect($html)
        ->toContain('print:hidden')
        ->toContain('Report / Stampe')
        ->toContain('Consegna incassi')
        ->toContain('size: A4 portrait')
        ->toContain('report-sheet');
});

it('mostra i coperti accanto alla data nel foglio consegna', function () {
    $punto = PuntoCassa::query()->firstOrFail();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coperto = MenuItem::query()->where('is_coperto', true)->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 1],
            ['menu_item_id' => $coperto->id, 'quantita' => 5],
        ],
        0,
        'contante',
    );

    app(ChiusuraService::class)->salva($serata, $punto, [
        'fondo_iniziale' => 50,
        'fondo_trattenuto' => 50,
        'totale_pos' => 0,
        'totale_z' => 0,
    ]);

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $punto->id)
        ->set('tipo', 'consegna')
        ->assertSee($serata->data->format('d/m/Y'))
        ->assertSee('Coperti: 5');
});

it('apre il foglio consegna da query string e prepara la stampa automatica', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.report', [
            'tipo' => 'consegna',
            'serata_id' => $serata->id,
            'punto_cassa_id' => $puntoId,
            'print' => 1,
        ]))
        ->assertOk()
        ->assertSee('Consegna incassi', false)
        ->assertSee('window.print()', false);

    Livewire::withQueryParams([
        'tipo' => 'consegna',
        'serata_id' => $serata->id,
        'punto_cassa_id' => $puntoId,
        'print' => 1,
    ])
        ->test(ReportHub::class)
        ->assertSet('tipo', 'consegna')
        ->assertSet('serataId', $serata->id)
        ->assertSet('puntoCassaId', $puntoId)
        ->assertSet('autoPrint', true)
        ->assertSeeHtml('window.print()');
});

it('usa A4 landscape per report a tabella larga e portrait per statistiche', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('tipo', 'bevande')
        ->assertSeeHtml('size: A4 landscape')
        ->assertSee('A4 orizzontale');

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('tipo', 'cumulativo')
        ->assertSeeHtml('size: A4 landscape')
        ->assertSee('Cumulativo produzione');

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('tipo', 'cucina_1')
        ->assertSeeHtml('size: A4 landscape')
        ->assertSee('Dettaglio Cucina 1');

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('tipo', 'statistiche')
        ->assertSeeHtml('size: A4 portrait')
        ->assertSee('A4 verticale');
});
