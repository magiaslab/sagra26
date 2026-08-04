<?php

use App\Livewire\Report\ReportHub;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\SerataStock;
use App\Services\ComandaService;
use App\Services\SerataService;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('rifornisce stock a serata aperta aggiornando residuo e iniziale', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $cacciucco = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    $row = SerataStock::query()
        ->where('serata_id', $serata->id)
        ->where('menu_item_id', $cacciucco->id)
        ->firstOrFail();
    $iniziale = (int) $row->stock_iniziale;
    $residuo = (int) $row->stock_residuo;

    $aggiornato = app(StockService::class)->rifornisci($serata->id, $cacciucco->id, 15);

    expect((int) $aggiornato->stock_residuo)->toBe($residuo + 15)
        ->and((int) $aggiornato->stock_iniziale)->toBe($iniziale + 15);
});

it('salva tavolo e note sulla comanda e li espone in richiamo/stampa', function () {
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
        null,
        null,
        null,
        null,
        null,
        'T12',
        'Senza ghiaccio',
    );

    expect($comanda->tavolo)->toBe('T12')
        ->and($comanda->note)->toBe('Senza ghiaccio');

    $this->getJson(route('cassa.richiamo', $comanda->numero_progressivo, absolute: false))
        ->assertOk()
        ->assertJson([
            'tavolo' => 'T12',
            'note' => 'Senza ghiaccio',
        ]);

    $this->get(route('cassa.stampa', $comanda, absolute: false))
        ->assertOk()
        ->assertSee('Tavolo T12')
        ->assertSee('Senza ghiaccio');
});

it('registra pagamento misto con importi che sommano al totale', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $prezzo = (float) $acqua->prezzo;
    $qta = 2;
    $totale = round($prezzo * $qta, 2);
    $contante = round($totale / 2, 2);
    $pos = round($totale - $contante, 2);

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => $qta]],
        0,
        'misto',
        $contante,
        $pos,
    );

    expect($comanda->metodo_pagamento)->toBe('misto')
        ->and((float) $comanda->importo_contante)->toBe($contante)
        ->and((float) $comanda->importo_pos)->toBe($pos)
        ->and($comanda->importoContanteEffettivo())->toBe($contante)
        ->and($comanda->importoPosEffettivo())->toBe($pos);
});

it('rifiuta pagamento misto se la somma non eguaglia il totale', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    expect(fn () => app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'misto',
        0.50,
        0.50,
    ))->toThrow(RuntimeException::class);
});

it('filtra la ristampa per parte cliente produzione cameriere', function () {
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

    $this->get(route('cassa.stampa', $comanda, absolute: false).'?parte=cliente')
        ->assertOk()
        ->assertSee('print-sheet--parte-cliente')
        ->assertSee('Cliente');

    $this->get(route('cassa.stampa', $comanda, absolute: false).'?parte=produzione')
        ->assertOk()
        ->assertSee('print-sheet--parte-produzione');

    $this->get(route('cassa.stampa', $comanda, absolute: false).'?parte=cameriere')
        ->assertOk()
        ->assertSee('print-sheet--parte-cameriere');
});

it('esporta csv serata con tavolo note e importi', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'misto',
        0.50,
        round((float) $acqua->prezzo - 0.50, 2),
        null,
        null,
        null,
        'A3',
        'Note CSV',
    );

    Livewire::test(ReportHub::class)
        ->set('serataId', $serata->id)
        ->call('exportCsv')
        ->assertFileDownloaded('serata-'.$serata->data->format('Y-m-d').'.csv');
});

it('mostra confronto tra due serate nel report hub', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $svc = app(SerataService::class);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $postazione = Postazione::query()->first();

    $ieri = now()->subDay()->toDateString();
    $oggi = now()->toDateString();

    $s1 = $svc->apri($ieri, null, [], [$puntoId => 50]);
    app(ComandaService::class)->confermaEStampa(
        $s1,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 3]],
        0,
        'contante',
    );
    $svc->chiudi($s1);

    $s2 = $svc->apri($oggi, null, [], [$puntoId => 40]);
    app(ComandaService::class)->confermaEStampa(
        $s2,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'pos',
    );

    Livewire::test(ReportHub::class)
        ->set('serataId', $s2->id)
        ->set('tipo', 'confronto')
        ->set('serataConfrontoId', $s1->id)
        ->assertSee('Confronto serate')
        ->assertSee('Acqua Naturale 1L');
});

it('legge e salva soglia stock alert nelle impostazioni', function () {
    $i = Impostazione::corrente();
    expect($i->sogliaStockAlert())->toBeGreaterThanOrEqual(0);

    $i->update(['stock_soglia_alert' => 7]);
    expect(Impostazione::corrente()->sogliaStockAlert())->toBe(7);
});
