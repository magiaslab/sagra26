<?php

use App\Livewire\Report\ReportHub;
use App\Models\PuntoCassa;
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
