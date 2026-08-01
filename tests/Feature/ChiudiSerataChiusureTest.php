<?php

use App\Livewire\Gestione\Serate;
use App\Models\Chiusura;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('avvisa se mancano chiusure cassa e non chiude al primo click', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    expect(Serata::corrente())->not->toBeNull();

    Livewire::test(Serate::class)
        ->call('chiudi')
        ->assertSet('puntiCassaMancanti', [PuntoCassa::query()->first()->nome])
        ->assertSee('Chiusure cassa incomplete');

    expect(Serata::corrente())->not->toBeNull()
        ->and(Serata::corrente()->stato)->toBe('aperta');
});

it('chiude dopo conferma esplicita anche con chiusure incomplete', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(Serate::class)
        ->call('chiudi')
        ->call('forzaChiusura')
        ->assertSet('puntiCassaMancanti', []);

    expect(Serata::corrente())->toBeNull();
});

it('chiude direttamente se tutte le chiusure hanno chiusa_at', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Chiusura::query()
        ->where('serata_id', $serata->id)
        ->update(['chiusa_at' => now()]);

    Livewire::test(Serate::class)
        ->call('chiudi')
        ->assertSet('puntiCassaMancanti', []);

    expect(Serata::corrente())->toBeNull()
        ->and($serata->fresh()->stato)->toBe('chiusa');
});
