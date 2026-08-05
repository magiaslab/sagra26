<?php

use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('omaggio non conta come incasso ma scala stock e coperti', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $coperto = MenuItem::query()->where('is_coperto', true)->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [
            ['menu_item_id' => $acqua->id, 'quantita' => 1],
            ['menu_item_id' => $coperto->id, 'quantita' => 2],
        ],
        0,
        'omaggio',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Mario',
        'Ospite VIP',
        'tavolo direzione',
    );

    expect($comanda->metodo_pagamento)->toBe('omaggio')
        ->and($comanda->contaComeIncasso())->toBeFalse()
        ->and($comanda->importoIncasso())->toBe(0.0)
        ->and($comanda->importoContanteEffettivo())->toBe(0.0)
        ->and($comanda->importoPosEffettivo())->toBe(0.0)
        ->and((int) $comanda->coperti)->toBe(2)
        ->and($comanda->autorizzato_da)->toBe('Mario')
        ->and($comanda->nominativo)->toBe('Ospite VIP')
        ->and((float) $comanda->totale)->toBeGreaterThan(0);
});

it('sospeso si apre e poi si chiude con contante entrando negli incassi', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    $aperta = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Luca',
        'Rossi',
        null,
    );

    expect($aperta->metodo_pagamento)->toBe('sospeso')
        ->and($aperta->isSospesoAperto())->toBeTrue()
        ->and($aperta->era_sospeso)->toBeTrue()
        ->and($aperta->importoIncasso())->toBe(0.0)
        ->and((float) $aperta->totale)->toBe(4.0);

    $chiusa = $service->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
        null,
        null,
        $aperta,
        'saldo sospeso',
        (int) $aperta->version,
    );

    expect($chiusa->metodo_pagamento)->toBe('contante')
        ->and($chiusa->isSospesoAperto())->toBeFalse()
        ->and($chiusa->importoIncasso())->toBe(4.0)
        ->and($chiusa->importoContanteEffettivo())->toBe(4.0)
        ->and($chiusa->sospeso_chiuso_at)->not->toBeNull()
        ->and($chiusa->era_sospeso)->toBeTrue();
});

it('conferma omaggio rifiuta pin errato', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $this->withSession(['postazione_id' => $postazione->id])
        ->postJson(route('cassa.conferma'), [
            'postazione_id' => $postazione->id,
            'coperti' => 0,
            'metodo_pagamento' => 'omaggio',
            'pin_autorizzazione' => '0000',
            'autorizzato_da' => 'Mario',
            'nominativo' => 'Ospite',
            'righe' => [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['error' => 'PIN non valido.']);
});

it('conferma omaggio con pin corretto crea la comanda', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $pin = Impostazione::corrente()->pin_gestione;

    $this->withSession(['postazione_id' => $postazione->id])
        ->postJson(route('cassa.conferma'), [
            'postazione_id' => $postazione->id,
            'coperti' => 0,
            'metodo_pagamento' => 'omaggio',
            'pin_autorizzazione' => $pin,
            'autorizzato_da' => 'Mario',
            'nominativo' => 'Ospite',
            'pagamento_note' => 'prova',
            'righe' => [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(Comanda::query()->latest('id')->first()->metodo_pagamento)->toBe('omaggio');
});

it('stampa cliente mostra badge omaggio con totale', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
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
        'Mario',
        'Ospite',
        null,
    );

    $html = $this->get(route('cassa.stampa', $comanda))->assertOk()->getContent();

    expect($html)
        ->toContain('OMAGGIO')
        ->toContain('TOTALE PAGATO')
        ->toContain('pay-badge--omaggio');
});

it('pagina gestione sospesi elenca e chiude un sospeso', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->first();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $sospeso = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'sospeso',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'Anna',
        'Bianchi',
        null,
    );

    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(\App\Livewire\Gestione\Sospesi::class)
        ->assertSee('Bianchi')
        ->assertSee('#'.$sospeso->numero_progressivo)
        ->call('apriChiusura', $sospeso->id)
        ->set('metodo', 'pos')
        ->call('confermaChiusura')
        ->assertHasNoErrors();

    expect($sospeso->fresh()->metodo_pagamento)->toBe('pos')
        ->and($sospeso->fresh()->importoIncasso())->toBe(2.0);
});

it('ui cassa espone omaggio e sospeso sotto al misto', function () {
    $html = file_get_contents(resource_path('views/cassa/index.blade.php'));

    expect($html)
        ->toContain("apriAuthSpeciale('omaggio')")
        ->toContain("apriAuthSpeciale('sospeso')")
        ->toContain('PIN gestione')
        ->toContain('Nome ospite');
});
