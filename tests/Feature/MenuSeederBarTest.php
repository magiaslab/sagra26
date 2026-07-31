<?php

use App\Models\MenuItem;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
});

it('aggiunge le voci bar inattive e non le mostra tra quelle attive', function () {
    $bar = MenuItem::query()->where('bar', true)->orderBy('ordinamento')->get();

    expect($bar)->toHaveCount(15)
        ->and($bar->every(fn (MenuItem $i) => $i->attivo === false))->toBeTrue()
        ->and($bar->pluck('nome'))->toContain('Spritz del Marinaio')
        ->and($bar->pluck('nome'))->toContain('Crepes con Nutella/Marmellata');

    $attivi = MenuItem::query()->where('attivo', true)->pluck('nome');
    expect($attivi)->not->toContain('Spritz del Marinaio')
        ->and($attivi)->not->toContain('Crepes con Nutella/Marmellata')
        ->and($attivi)->toContain('Birra Media 400ml');

    $crepes = MenuItem::query()->where('nome', 'Crepes con Nutella/Marmellata')->firstOrFail();
    expect($crepes->categoria->is_bevande)->toBeFalse()
        ->and($crepes->bar)->toBeTrue();
});

it('rilanciare MenuSeeder non crea duplicati', function () {
    $prima = MenuItem::query()->count();
    $this->seed(MenuSeeder::class);
    $dopo = MenuItem::query()->count();

    expect($dopo)->toBe($prima)
        ->and(MenuItem::query()->pluck('nome')->unique()->count())->toBe($dopo)
        ->and($dopo)->toBe(47); // 32 originali + 15 bar
});
