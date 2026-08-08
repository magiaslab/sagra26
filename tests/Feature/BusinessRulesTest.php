<?php

use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use App\Services\ComandaService;
use App\Services\RiconciliazioneService;
use App\Services\SerataService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed();
});

it('scala lo stock in modo atomico e rifiuta la contesa', function () {
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [
        PuntoCassa::query()->first()->id => 100,
    ]);

    $item = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();
    SerataStock::query()->where('serata_id', $serata->id)->where('menu_item_id', $item->id)
        ->update(['stock_iniziale' => 5, 'stock_residuo' => 5]);

    $stock = app(StockService::class);
    $stock->scala($serata->id, $item->id, 3);

    expect(SerataStock::query()->where('serata_id', $serata->id)->where('menu_item_id', $item->id)->value('stock_residuo'))
        ->toBe(2);

    expect(fn () => $stock->scala($serata->id, $item->id, 3))
        ->toThrow(RuntimeException::class);

    expect(SerataStock::query()->where('serata_id', $serata->id)->where('menu_item_id', $item->id)->value('stock_residuo'))
        ->toBe(2);
});

it('assegna numeri progressivi unici sotto creazioni concorrenti', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    $numeri = [];
    for ($i = 0; $i < 10; $i++) {
        $comanda = $service->confermaEStampa(
            $serata,
            $postazione,
            [['menu_item_id' => $acqua->id, 'quantita' => 1]],
            0,
            'contante',
        );
        $numeri[] = $comanda->numero_progressivo;
    }

    expect($numeri)->toHaveCount(10)
        ->and(count(array_unique($numeri)))->toBe(10)
        ->and(Comanda::query()->count())->toBe(10);
});

it('calcola la riconciliazione a tre vie', function () {
    $punto = PuntoCassa::query()->first();
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$punto->id => 100]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    // 3 × 2€ contante = 6; 2 × 2€ pos = 4; totale atteso 10
    $service->confermaEStampa($serata, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 3]], 0, 'contante');
    $service->confermaEStampa($serata, $postazione, [['menu_item_id' => $acqua->id, 'quantita' => 2]], 0, 'pos');

    $chiusura = Chiusura::query()->where('serata_id', $serata->id)->where('punto_cassa_id', $punto->id)->firstOrFail();
    // Reale contante = contante_contato - fondo_iniziale. Atteso contante = 6 → contato = 106
    $chiusura->fondo_iniziale = 100;
    $chiusura->n_100 = 1;
    $chiusura->n_5 = 1;
    $chiusura->n_1 = 1;
    $chiusura->contante_contato = $chiusura->calcolaContanteContato();
    $chiusura->fondo_trattenuto = 100;
    $chiusura->totale_pos = 4;
    $chiusura->totale_z_contante = 6;
    $chiusura->totale_z_pos = 4;
    $chiusura->sincronizzaTotaleZ();
    $chiusura->save();

    $ric = app(RiconciliazioneService::class)->calcola($serata, $punto, $chiusura);

    expect($ric['atteso_contante'])->toBe(6.0)
        ->and($ric['atteso_pos'])->toBe(4.0)
        ->and($ric['atteso_totale'])->toBe(10.0)
        ->and($ric['reale_contante'])->toBe(6.0)
        ->and($ric['delta_contante'])->toBe(0.0)
        ->and($ric['delta_pos'])->toBe(0.0)
        ->and($ric['fiscale_contante'])->toBe(6.0)
        ->and($ric['fiscale_pos'])->toBe(4.0)
        ->and($ric['delta_fiscale_contante'])->toBe(0.0)
        ->and($ric['delta_fiscale_pos'])->toBe(0.0)
        ->and($ric['delta_fiscale'])->toBe(0.0)
        ->and($ric['contante_consegnato'])->toBe(6.0);
});

it('non brucia il numero progressivo se lo stock è esaurito', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $service = app(ComandaService::class);

    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $cacciucco = MenuItem::query()->where('nome', 'Cacciucchetto')->firstOrFail();

    // Una comanda valida: consuma il primo numero progressivo
    $prima = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    $ultimoUsato = $prima->numero_progressivo;
    $comandePrima = Comanda::query()->count();

    // Stock già a zero: il tentativo deve fallire
    SerataStock::query()
        ->where('serata_id', $serata->id)
        ->where('menu_item_id', $cacciucco->id)
        ->update(['stock_iniziale' => 1, 'stock_residuo' => 0]);

    expect(fn () => $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $cacciucco->id, 'quantita' => 1]],
        0,
        'contante',
    ))->toThrow(RuntimeException::class);

    expect(Comanda::query()->count())->toBe($comandePrima);

    // Il numero successivo non deve saltare: il fallimento non ha bruciato progressivi
    $dopo = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    expect($dopo->numero_progressivo)->toBe($ultimoUsato + 1);
});

it('precompila il fondo iniziale dalla chiusura precedente', function () {
    $punto = PuntoCassa::query()->first();
    $s1 = app(SerataService::class)->apri(now()->subDay()->toDateString(), null, [], [$punto->id => 80]);

    $chiusura = Chiusura::query()->where('serata_id', $s1->id)->where('punto_cassa_id', $punto->id)->firstOrFail();
    $chiusura->fondo_trattenuto = 75.5;
    $chiusura->contante_contato = 200;
    $chiusura->contante_consegnato = 124.5;
    $chiusura->chiusa_at = now();
    $chiusura->save();

    app(SerataService::class)->chiudi($s1);

    $suggerito = app(RiconciliazioneService::class)->fondoInizialeSuggerito($punto);
    expect($suggerito)->toBe(75.5);

    $s2 = app(SerataService::class)->apri(now()->toDateString());
    $fondo = Chiusura::query()->where('serata_id', $s2->id)->where('punto_cassa_id', $punto->id)->value('fondo_iniziale');
    expect((float) $fondo)->toBe(75.5);
});
