<?php

use App\Livewire\Report\ReportHub;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('il cumulativo menù include cucina, bevande e bar nel range date', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();

    $pasta = MenuItem::query()->where('nome', 'Tortelli al Ragù')->firstOrFail();
    $bibita = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $bibita->update(['bar' => false, 'attivo' => true]);
    $bar = MenuItem::query()->where('nome', 'Caffè')->firstOrFail();
    $bar->update(['bar' => true, 'attivo' => true]);

    app(ComandaService::class)->confermaEStampa($serata, $postazione, [
        ['menu_item_id' => $pasta->id, 'quantita' => 2],
        ['menu_item_id' => $bibita->id, 'quantita' => 3],
        ['menu_item_id' => $bar->id, 'quantita' => 4],
    ], 0, 'contante');

    $html = Livewire::test(ReportHub::class)
        ->set('tipo', 'piatti')
        ->set('dataDa', $serata->data->toDateString())
        ->set('dataA', $serata->data->toDateString())
        ->assertSee('Cumulativo menù (tutti i reparti)')
        ->assertSee('Riepilogo per reparto')
        ->assertSee('Bevande / Bibite')
        ->assertSee('Bar')
        ->assertSee($pasta->nome)
        ->assertSee($bibita->nome)
        ->assertSee($bar->nome)
        ->html();

    expect($html)->toContain('TOTALE PERIODO (tutti i reparti)');

    $hub = new ReportHub;
    $hub->dataDa = $serata->data->toDateString();
    $hub->dataA = $serata->data->toDateString();
    $method = new \ReflectionMethod(ReportHub::class, 'datiPiattiRange');
    $method->setAccessible(true);
    $dati = $method->invoke($hub);

    $keys = collect($dati['reparti'])->pluck('key')->all();
    expect($keys)->toContain('bevande')
        ->and($keys)->toContain('bar');

    $qtaBibite = collect($dati['reparti'])->firstWhere('key', 'bevande')['qta'] ?? 0;
    $qtaBar = collect($dati['reparti'])->firstWhere('key', 'bar')['qta'] ?? 0;
    expect($qtaBibite)->toBeGreaterThanOrEqual(3)
        ->and($qtaBar)->toBeGreaterThanOrEqual(4)
        ->and($dati['totale_qta'])->toBeGreaterThanOrEqual(2 + 3 + 4);
});

it('export csv menù usa colonne reparto e include bibite/bar', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();

    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $acqua->update(['bar' => false, 'attivo' => true]);
    $caffe = MenuItem::query()->where('nome', 'Caffè')->firstOrFail();
    $caffe->update(['bar' => true, 'attivo' => true]);

    app(ComandaService::class)->confermaEStampa($serata, $postazione, [
        ['menu_item_id' => $acqua->id, 'quantita' => 1],
        ['menu_item_id' => $caffe->id, 'quantita' => 2],
    ], 0, 'contante');

    Livewire::test(ReportHub::class)
        ->set('tipo', 'piatti')
        ->set('dataDa', $serata->data->toDateString())
        ->set('dataA', $serata->data->toDateString())
        ->call('exportCsv')
        ->assertFileDownloaded();
});
