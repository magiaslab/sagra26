<?php

use App\Livewire\Gestione\ImpostazioniPage;
use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('reclama la postazione sulla selezione', function () {
    $postazione = Postazione::query()->firstOrFail();

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
    ])->assertOk()->assertJson(['ok' => true]);

    $postazione->refresh();
    expect($postazione->claimed_session_id)->toBe(session('postazione_claim_token'))
        ->and($postazione->claimed_at)->not->toBeNull()
        ->and(session('postazione_claim_token'))->not->toBeNull();
});

it('avvisa se la postazione è già reclamata da un altra sessione', function () {
    $postazione = Postazione::query()->firstOrFail();
    $postazione->claim('altra-sessione');

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
    ])
        ->assertStatus(409)
        ->assertJsonPath('claim_conflitto', true)
        ->assertJsonPath('richiede_pin', true)
        ->assertJsonPath('ok', false);

    expect($postazione->fresh()->claimed_session_id)->toBe('altra-sessione');
});

it('rifiuta force senza pin gestione', function () {
    $postazione = Postazione::query()->firstOrFail();
    $postazione->claim('altra-sessione');

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
        'force' => true,
    ])
        ->assertStatus(422)
        ->assertJsonPath('richiede_pin', true);

    expect($postazione->fresh()->claimed_session_id)->toBe('altra-sessione');
});

it('prende il controllo con force e pin corretto e sospende l altra sessione', function () {
    $a = Postazione::query()->orderBy('id')->firstOrFail();
    $b = Postazione::query()->orderBy('id')->skip(1)->firstOrFail();
    $pin = Impostazione::corrente()->pin_gestione;

    // Sessione vittima su A
    $this->withSession([
        'postazione_claim_token' => 'vittima-token',
        'postazione_id' => $a->id,
    ]);
    $a->claim('vittima-token');

    // Nuova sessione forza A con PIN
    $this->withSession([
        'postazione_claim_token' => 'aggressore-token',
        'postazione_id' => $b->id,
    ]);
    $b->claim('aggressore-token');

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $a->id,
        'force' => true,
        'pin' => $pin,
    ])->assertOk()->assertJson(['ok' => true]);

    expect($a->fresh()->claimed_session_id)->toBe('aggressore-token')
        ->and($b->fresh()->claimed_session_id)->toBeNull();

    // La vittima in poll riceve claim_perso
    $this->withSession([
        'postazione_claim_token' => 'vittima-token',
        'postazione_id' => $a->id,
    ]);

    $this->getJson(route('cassa.stock'))
        ->assertOk()
        ->assertJsonPath('claim_perso', true);
});

it('non avvisa se il claim è scaduto', function () {
    $postazione = Postazione::query()->firstOrFail();
    $postazione->forceFill([
        'claimed_session_id' => 'vecchia',
        'claimed_at' => now()->subSeconds(Postazione::CLAIM_TTL_SECONDS + 5),
    ])->save();

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
    ])->assertOk();

    expect($postazione->fresh()->claimed_session_id)->toBe(session('postazione_claim_token'));
});

it('aggiorna claimed_at sul polling stock se la sessione possiede la postazione', function () {
    $postazione = Postazione::query()->firstOrFail();
    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
    ])->assertOk();

    $postazione->refresh();
    expect($postazione->claimed_session_id)->toBe(session('postazione_claim_token'))
        ->and(session('postazione_id'))->toBe($postazione->id);

    $primaTs = $postazione->claimed_at->getTimestamp();
    $this->travel(30)->seconds();

    $this->getJson(route('cassa.stock'))
        ->assertOk()
        ->assertJsonPath('claim_perso', false);

    expect($postazione->fresh()->claimed_at->getTimestamp())->toBeGreaterThan($primaTs);
});

it('rilascia la postazione precedente quando se ne sceglie un altra libera', function () {
    $a = Postazione::query()->orderBy('id')->firstOrFail();
    $b = Postazione::query()->orderBy('id')->skip(1)->firstOrFail();

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $a->id])->assertOk();
    expect($a->fresh()->isClaimedBy(session('postazione_claim_token')))->toBeTrue();

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $b->id])->assertOk();

    expect($a->fresh()->claimed_session_id)->toBeNull()
        ->and($b->fresh()->claimed_session_id)->toBe(session('postazione_claim_token'));
});

it('all avvio cassa richiede scelta se non c è claim attivo', function () {
    $html = $this->get(route('cassa'))->assertOk()->getContent();

    expect($html)
        ->toContain('richiedeSceltaPostazione: true')
        ->toContain('Scegli la postazione cassa')
        ->toContain('Forza con PIN');
});

it('blocca conferma se il claim non è più attivo', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $postazione->id])->assertOk();
    $postazione->claim('altro');

    $this->postJson(route('cassa.conferma'), [
        'postazione_id' => $postazione->id,
        'coperti' => 0,
        'metodo_pagamento' => 'contante',
        'righe' => [['menu_item_id' => $acqua->id, 'quantita' => 1]],
    ])
        ->assertStatus(409)
        ->assertJsonPath('claim_perso', true);

    expect(Comanda::query()->where('serata_id', $serata->id)->count())->toBe(0);
});

it('avvisa se la postazione selezionata non è mappata', function () {
    $orfana = Postazione::query()->create(['nome' => 'Cassa C']);

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $orfana->id,
    ])
        ->assertOk()
        ->assertJsonPath('mappata', false)
        ->assertJsonFragment([
            'warning' => 'Questa postazione non è ancora collegata al cassetto — chiedi a chi gestisce le Impostazioni di completare il collegamento.',
        ]);
});

it('mostra un messaggio chiaro in conferma se la postazione non è mappata', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $orfana = Postazione::query()->create(['nome' => 'Cassa C']);
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    expect(fn () => app(ComandaService::class)->confermaEStampa(
        $serata,
        $orfana,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    ))->toThrow(
        \RuntimeException::class,
        'Questa postazione non è ancora collegata al cassetto — chiedi a chi gestisce le Impostazioni di completare il collegamento.'
    );
});

it('crea e mappa una postazione anche a serata aperta', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    expect(Serata::corrente())->not->toBeNull();

    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(ImpostazioniPage::class)
        ->set('nuovaPostazione', 'Cassa C')
        ->call('aggiungiPostazione');

    $nuova = Postazione::query()->where('nome', 'Cassa C')->firstOrFail();

    Livewire::test(ImpostazioniPage::class)
        ->set('mapPostazione', $nuova->id)
        ->set('mapPunto', $puntoId)
        ->set('mapValidoDa', now()->toDateString())
        ->call('mappa');

    expect($nuova->fresh()->puntoCassaAttivo())->not->toBeNull();
});
