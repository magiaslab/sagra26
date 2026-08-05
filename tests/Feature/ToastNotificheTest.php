<?php

use App\Livewire\Gestione\ChiusuraForm;
use App\Livewire\Gestione\ImpostazioniPage;
use App\Livewire\Gestione\MenuCrud;
use App\Livewire\Gestione\Serate;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('layout gestione espone host toast e bridge livewire', function () {
    $html = $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('aria-live="polite"')
        ->toContain('Livewire.on(\'toast\'')
        ->toContain('sagraToast');
});

it('salvataggio voce menù emette toast ok', function () {
    $catId = MenuItem::query()->firstOrFail()->categoria_id;

    Livewire::test(MenuCrud::class)
        ->set('nome', 'Toast Test Voce')
        ->set('prezzo', '3.50')
        ->set('categoria_id', $catId)
        ->call('salva')
        ->assertDispatched('toast', message: 'Voce menù creata.', type: 'ok');
});

it('aggiornamento voce menù emette toast ok', function () {
    $item = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    Livewire::test(MenuCrud::class)
        ->call('edit', $item->id)
        ->set('prezzo', (string) ((float) $item->prezzo + 0.10))
        ->call('salva')
        ->assertDispatched('toast', message: 'Voce menù aggiornata.', type: 'ok');
});

it('impostazioni salvate emettono un solo toast senza doppio flash', function () {
    Livewire::test(ImpostazioniPage::class)
        ->call('salvaIntestazione')
        ->assertDispatched('toast', message: 'Impostazioni salvate.', type: 'ok');

    expect(session('status'))->toBeNull();
});

it('chiusura salvata emette toast', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(ChiusuraForm::class)
        ->set('serataId', $serata->id)
        ->set('puntoCassaId', $puntoId)
        ->call('carica')
        ->set('totale_pos', 0)
        ->set('totale_z', 0)
        ->call('salva')
        ->assertDispatched('toast', message: 'Chiusura salvata.', type: 'ok');
});

it('rifornimento stock emette toast', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $item = MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->firstOrFail();
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(Serate::class)
        ->set("rifornimenti.{$item->id}", '5')
        ->call('rifornisciStock', $item->id)
        ->assertDispatched('toast', message: 'Stock aggiornato (+5).', type: 'ok');
});
