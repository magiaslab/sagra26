<?php

use App\Livewire\Gestione\Omaggi;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('pagina omaggi elenca ospite e autorizzatore', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'omaggio',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Mario Rossi',
        'Ospite VIP',
        'tavolo direzione',
    );

    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.omaggi'))
        ->assertOk()
        ->assertSee('Omaggi')
        ->assertSee('Ospite VIP')
        ->assertSee('Mario Rossi')
        ->assertSee('tavolo direzione')
        ->assertSee('Export riepilogo CSV');
});

it('export csv omaggi contiene ospite autorizzatore e riepilogo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'omaggio',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Luca',
        'Bianchi',
        null,
    );

    $component = Livewire::test(Omaggi::class)
        ->set('serataId', $serata->id)
        ->call('exportCsv')
        ->assertFileDownloaded('omaggi-'.$serata->data->format('Y-m-d').'.csv');

    $content = base64_decode(data_get($component->effects, 'download.content'));
    expect($content)
        ->toContain('ospite')
        ->toContain('autorizzato_da')
        ->toContain('Bianchi')
        ->toContain('Luca')
        ->toContain('RIEPILOGO');
});

it('subnav e dashboard espongono il link omaggi', function () {
    $html = $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('gestione/omaggi')
        ->toContain('Omaggi');
});
