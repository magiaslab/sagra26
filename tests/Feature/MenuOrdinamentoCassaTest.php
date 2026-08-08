<?php

use App\Livewire\Gestione\MenuCrud;
use App\Models\Categoria;
use App\Models\MenuItem;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('una nuova voce viene inserita in coda alla sua categoria non in fondo al menù', function () {
    $bevande = Categoria::query()->where('nome', 'Bevande')->firstOrFail();
    $maxBevande = (int) MenuItem::query()->where('categoria_id', $bevande->id)->max('ordinamento');
    $dopoBevande = MenuItem::query()->where('ordinamento', '>', $maxBevande)->orderBy('ordinamento')->first();

    Livewire::test(MenuCrud::class)
        ->set('nome', 'Bibita Test Nuova')
        ->set('prezzo', '2.50')
        ->set('categoria_id', $bevande->id)
        ->set('attivo', true)
        ->call('salva')
        ->assertHasNoErrors();

    $nuova = MenuItem::query()->where('nome', 'Bibita Test Nuova')->firstOrFail();
    expect($nuova->categoria_id)->toBe($bevande->id)
        ->and($nuova->ordinamento)->toBe($maxBevande + 1);

    if ($dopoBevande) {
        expect((int) $dopoBevande->fresh()->ordinamento)->toBeGreaterThan($nuova->ordinamento);
    }

    // In cassa l’ordine per categoria+ordinamento include la nuova tra le bevande.
    $idsBevande = MenuItem::query()
        ->where('attivo', true)
        ->join('categorie', 'categorie.id', '=', 'menu_items.categoria_id')
        ->orderBy('categorie.ordinamento')
        ->orderBy('menu_items.ordinamento')
        ->select('menu_items.*')
        ->get()
        ->filter(fn ($i) => $i->categoria_id === $bevande->id)
        ->pluck('id')
        ->values()
        ->all();

    expect($idsBevande)->toContain($nuova->id)
        ->and(array_search($nuova->id, $idsBevande, true))->toBe(count($idsBevande) - 1);
});

it('la pagina sospesi espone il link Modifica verso cassa con richiamo', function () {
    $puntoId = \App\Models\PuntoCassa::query()->first()->id;
    $serata = app(\App\Services\SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = \App\Models\Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(\App\Services\ComandaService::class)->confermaEStampa(
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
        'Mario',
        'Ospite',
    );

    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.sospesi'))
        ->assertOk()
        ->assertSee('Modifica')
        ->assertSee('richiamo='.$comanda->numero_progressivo, false);
});
