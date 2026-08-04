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
        ->set('tipo', 'cucina_1')
        ->assertSeeHtml('size: A4 landscape');

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->set('tipo', 'statistiche')
        ->assertSeeHtml('size: A4 portrait')
        ->assertSee('A4 verticale');
});
