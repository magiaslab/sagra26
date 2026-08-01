<?php

use App\Livewire\Gestione\ImpostazioniPage;
use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\Postazione;
use App\Models\PostazionePuntoCassa;
use App\Models\PuntoCassa;
use App\Models\Serata;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('rilanciare SettingsSeeder non crea duplicati postazioni e punti cassa', function () {
    $postazioniPrima = Postazione::query()->count();
    $puntiPrima = PuntoCassa::query()->count();
    $mappaturePrima = PostazionePuntoCassa::query()->count();

    $this->seed(SettingsSeeder::class);

    expect(Postazione::query()->count())->toBe($postazioniPrima)
        ->and(PuntoCassa::query()->count())->toBe($puntiPrima)
        ->and(PostazionePuntoCassa::query()->count())->toBe($mappaturePrima)
        ->and($postazioniPrima)->toBe(2)
        ->and($puntiPrima)->toBe(1)
        ->and(Postazione::query()->pluck('nome')->unique()->count())->toBe(2)
        ->and(PuntoCassa::query()->pluck('nome')->unique()->count())->toBe(1);
});

it('mostra il bottone Elimina per postazioni e punti cassa', function () {
    Livewire::test(ImpostazioniPage::class)
        ->assertSee('Elimina')
        ->assertSee('Cassa A')
        ->assertSee('Cassetto unico');
});

it('elimina una postazione senza riferimenti', function () {
    $orfana = Postazione::query()->create(['nome' => 'Duplicato test']);

    Livewire::test(ImpostazioniPage::class)
        ->call('eliminaPostazione', $orfana->id)
        ->assertSet('errore', '')
        ->assertDispatched('toast');

    expect(Postazione::query()->whereKey($orfana->id)->exists())->toBeFalse();
});

it('blocca eliminazione postazione con comande', function () {
    $postazione = Postazione::query()->where('nome', 'Cassa A')->firstOrFail();
    $punto = PuntoCassa::query()->firstOrFail();
    $serata = Serata::query()->create([
        'data' => now()->toDateString(),
        'stato' => 'aperta',
    ]);

    Comanda::query()->create([
        'numero_progressivo' => 1,
        'serata_id' => $serata->id,
        'postazione_id' => $postazione->id,
        'punto_cassa_id' => $punto->id,
        'coperti' => 0,
        'stato' => 'stampata',
        'metodo_pagamento' => 'contante',
        'totale' => 0,
    ]);

    $msg = 'Non eliminabile: 1 comande già registrate su questa postazione';

    Livewire::test(ImpostazioniPage::class)
        ->call('eliminaPostazione', $postazione->id)
        ->assertSet('errore', $msg)
        ->assertSee($msg);

    expect(Postazione::query()->whereKey($postazione->id)->exists())->toBeTrue();
});

it('blocca eliminazione postazione con mappature', function () {
    $postazione = Postazione::query()->where('nome', 'Cassa A')->firstOrFail();

    expect($postazione->mappature()->count())->toBeGreaterThan(0);

    Livewire::test(ImpostazioniPage::class)
        ->call('eliminaPostazione', $postazione->id)
        ->assertSet('errore', 'Non eliminabile: 1 mappature punto cassa collegate a questa postazione');

    expect(Postazione::query()->whereKey($postazione->id)->exists())->toBeTrue();
});

it('elimina un punto cassa senza riferimenti', function () {
    $orfano = PuntoCassa::query()->create(['nome' => 'Cassetto duplicato', 'attivo' => true]);

    Livewire::test(ImpostazioniPage::class)
        ->call('eliminaPunto', $orfano->id)
        ->assertSet('errore', '')
        ->assertDispatched('toast');

    expect(PuntoCassa::query()->whereKey($orfano->id)->exists())->toBeFalse();
});

it('blocca eliminazione punto cassa con chiusure', function () {
    $punto = PuntoCassa::query()->create(['nome' => 'Punto con chiusura', 'attivo' => true]);
    $serata = Serata::query()->create([
        'data' => now()->toDateString(),
        'stato' => 'aperta',
    ]);

    Chiusura::query()->create([
        'serata_id' => $serata->id,
        'punto_cassa_id' => $punto->id,
        'fondo_iniziale' => 0,
    ]);

    $msg = 'Non eliminabile: 1 chiusure collegate a questo punto cassa';

    Livewire::test(ImpostazioniPage::class)
        ->call('eliminaPunto', $punto->id)
        ->assertSet('errore', $msg)
        ->assertSee($msg);

    expect(PuntoCassa::query()->whereKey($punto->id)->exists())->toBeTrue();
});

it('blocca eliminazione punto cassa con comande', function () {
    $punto = PuntoCassa::query()->create(['nome' => 'Punto con comanda', 'attivo' => true]);
    $postazione = Postazione::query()->create(['nome' => 'Postazione temporanea']);
    $serata = Serata::query()->create([
        'data' => now()->toDateString(),
        'stato' => 'aperta',
    ]);

    Comanda::query()->create([
        'numero_progressivo' => 99,
        'serata_id' => $serata->id,
        'postazione_id' => $postazione->id,
        'punto_cassa_id' => $punto->id,
        'coperti' => 0,
        'stato' => 'stampata',
        'metodo_pagamento' => 'contante',
        'totale' => 0,
    ]);

    $msg = 'Non eliminabile: 1 comande già registrate su questo punto cassa';

    Livewire::test(ImpostazioniPage::class)
        ->call('eliminaPunto', $punto->id)
        ->assertSet('errore', $msg)
        ->assertSee($msg);

    expect(PuntoCassa::query()->whereKey($punto->id)->exists())->toBeTrue();
});
