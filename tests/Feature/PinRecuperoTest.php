<?php

use App\Livewire\Gestione\Pin;
use App\Models\Impostazione;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    config(['gestione.pin_master_reset' => 'master-segreto-test']);
    RateLimiter::clear('gestione-master:127.0.0.1');
    RateLimiter::clear('gestione-pin:127.0.0.1');
});

it('non cambia il PIN con codice master errato e non autentica', function () {
    $prima = Impostazione::corrente()->pin_gestione;

    Livewire::test(Pin::class)
        ->call('apriRecupero')
        ->set('codiceMaster', 'sbagliato')
        ->set('nuovoPin', '9999')
        ->set('confermaPin', '9999')
        ->call('reimposta')
        ->assertSet('errore', 'Codice di sblocco non valido.')
        ->assertSessionMissing('gestione_sbloccata');

    expect(Impostazione::corrente()->fresh()->pin_gestione)->toBe($prima)
        ->and(session('gestione_sbloccata'))->toBeNull();
});

it('con codice master corretto aggiorna il PIN e autentica in Gestione', function () {
    Livewire::test(Pin::class)
        ->call('apriRecupero')
        ->set('codiceMaster', 'master-segreto-test')
        ->set('nuovoPin', '5678')
        ->set('confermaPin', '5678')
        ->call('reimposta')
        ->assertRedirect('/gestione-fi')
        ->assertSessionHas('gestione_sbloccata', true);

    expect(Impostazione::corrente()->fresh()->pin_gestione)->toBe('5678');
});

it('blocca il recupero dopo troppi tentativi falliti di codice master', function () {
    $component = Livewire::test(Pin::class)->call('apriRecupero');

    for ($i = 0; $i < 5; $i++) {
        $component
            ->set('codiceMaster', 'errato-'.$i)
            ->set('nuovoPin', '1111')
            ->set('confermaPin', '1111')
            ->call('reimposta')
            ->assertSet('errore', 'Codice di sblocco non valido.');
    }

    $pinPrima = Impostazione::corrente()->pin_gestione;

    // Il 6° tentativo è bloccato anche se il codice sarebbe corretto
    $component
        ->set('codiceMaster', 'master-segreto-test')
        ->set('nuovoPin', '2222')
        ->set('confermaPin', '2222')
        ->call('reimposta')
        ->assertSet('errore', 'Troppi tentativi, riprova tra qualche minuto.')
        ->assertSessionMissing('gestione_sbloccata');

    expect(Impostazione::corrente()->fresh()->pin_gestione)->toBe($pinPrima);
});
