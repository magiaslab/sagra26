<?php

use App\Livewire\Gestione\ImpostazioniPage;
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
        ->assertJsonPath('ok', false);

    expect($postazione->fresh()->claimed_session_id)->toBe('altra-sessione');
});

it('prende il controllo con force=true', function () {
    $postazione = Postazione::query()->firstOrFail();
    $postazione->claim('altra-sessione');

    $this->postJson(route('cassa.postazione'), [
        'postazione_id' => $postazione->id,
        'force' => true,
    ])->assertOk()->assertJson(['ok' => true]);

    expect($postazione->fresh()->claimed_session_id)->toBe(session('postazione_claim_token'));
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

    $this->getJson(route('cassa.stock'))->assertOk();

    expect($postazione->fresh()->claimed_at->getTimestamp())->toBeGreaterThan($primaTs);
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
