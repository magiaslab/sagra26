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

it('statistiche senza completo usano solo la serata selezionata', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $svc = app(SerataService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $postazione = Postazione::query()->firstOrFail();

    $s1 = $svc->apri(now()->subDay()->toDateString(), null, [], [$puntoId => 50]);
    app(ComandaService::class)->confermaEStampa(
        $s1,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 5]],
        0,
        'contante',
    );
    $svc->chiudi($s1);

    $s2 = $svc->apri(now()->toDateString(), null, [], [$puntoId => 40]);
    app(ComandaService::class)->confermaEStampa(
        $s2,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    // Completo: somma entrambe le serate (5+1=6 pezzi → top mostra 6).
    Livewire::test(ReportHub::class)
        ->set('serataId', $s2->id)
        ->set('tipo', 'statistiche')
        ->set('completo', true)
        ->assertSee('tutta l’edizione')
        ->assertSee('Acqua Naturale 1L — 6');

    // Solo serata selezionata: 1 pezzo.
    Livewire::test(ReportHub::class)
        ->set('serataId', $s2->id)
        ->set('tipo', 'statistiche')
        ->set('completo', false)
        ->assertSee('serata '.$s2->data->format('d/m/Y'))
        ->assertSee('Acqua Naturale 1L — 1')
        ->assertDontSee('Acqua Naturale 1L — 6');
});

it('confronto range include più di due serate', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $svc = app(SerataService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $postazione = Postazione::query()->firstOrFail();

    $dates = [
        now()->subDays(2)->toDateString(),
        now()->subDay()->toDateString(),
        now()->toDateString(),
    ];
    $serate = [];
    foreach ($dates as $i => $data) {
        if ($i > 0) {
            $svc->chiudi($serate[$i - 1]);
        }
        $serate[$i] = $svc->apri($data, null, [], [$puntoId => 50]);
        app(ComandaService::class)->confermaEStampa(
            $serate[$i],
            $postazione,
            [['menu_item_id' => $acqua->id, 'quantita' => $i + 1]],
            0,
            'contante',
        );
    }

    Livewire::test(ReportHub::class)
        ->set('tipo', 'confronto')
        ->set('serataDaId', $serate[0]->id)
        ->set('serataId', $serate[2]->id)
        ->assertSee('3 serate')
        ->assertSee($serate[0]->data->format('d/m/Y'))
        ->assertSee($serate[1]->data->format('d/m/Y'))
        ->assertSee($serate[2]->data->format('d/m/Y'))
        ->assertSee('Totale');
});
