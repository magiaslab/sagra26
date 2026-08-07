<?php

use App\Livewire\Gestione\Sospesi;
use App\Livewire\Report\ReportHub;
use App\Models\Comanda;
use App\Models\Edizione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\ComandaService;
use App\Services\EdizioneService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('le pagine gestione principali rispondono 200 dopo il pin', function () {
    $routes = [
        'gestione.dashboard',
        'gestione.edizione',
        'gestione.serate',
        'gestione.menu',
        'gestione.chiusura',
        'gestione.sospesi',
        'gestione.omaggi',
        'gestione.report',
        'gestione.impostazioni',
        'gestione.stato',
        'gestione.guida',
        'home',
        'riepilogo',
    ];

    foreach ($routes as $name) {
        $this->withSession(['gestione_sbloccata' => true])
            ->get(route($name))
            ->assertOk();
    }

    $this->get(route('cassa'))->assertOk();
});

it('queryEdizione è vuota se nessuna edizione è aperta', function () {
    $svc = app(EdizioneService::class);
    $edizione = $svc->assicuratiCorrente();
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    app(SerataService::class)->chiudi($serata);
    $svc->chiudi($edizione);

    expect(Edizione::corrente())->toBeNull()
        ->and(Serata::queryEdizione()->count())->toBe(0);
});

it('non riapre serate di un’edizione archiviata', function () {
    $edizioni = app(EdizioneService::class);
    $edizione = $edizioni->assicuratiCorrente();
    $puntoId = PuntoCassa::query()->first()->id;
    $serataService = app(SerataService::class);
    $serata = $serataService->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $serataService->chiudi($serata);
    $edizioni->chiudi($edizione);

    expect(fn () => $serataService->riapri($serata->fresh()))
        ->toThrow(RuntimeException::class, 'edizione');
});

it('il foglio consegna non crasha senza punto cassa', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    Livewire::test(ReportHub::class)
        ->set('tipo', 'consegna')
        ->set('puntoCassaId', null)
        ->assertSee('Seleziona un punto cassa');
});

it('la stampa rifiuta le comande annullate', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );
    app(ComandaService::class)->annulla($comanda, 'errore test');

    $this->get(route('cassa.stampa', $comanda))->assertNotFound();
});

it('il richiamo espone il menu_item anche se disattivato', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $acqua->id, 'quantita' => 2]],
        0,
        'contante',
    );

    $acqua->update(['attivo' => false]);

    $this->getJson(route('cassa.richiamo', $comanda->numero_progressivo))
        ->assertOk()
        ->assertJsonPath('righe.0.menu_item_id', $acqua->id)
        ->assertJsonPath('righe.0.menu_item.id', $acqua->id)
        ->assertJsonPath('righe.0.menu_item.attivo', false)
        ->assertJsonPath('righe.0.nome', 'Acqua Naturale 1L');
});

it('chiusura sospeso omaggio con pin errato mostra errore nel modal', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
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

    Livewire::test(Sospesi::class)
        ->call('apriChiusura', $comanda->id)
        ->set('metodo', 'omaggio')
        ->set('pin', '0000')
        ->set('autorizzatoDa', 'Mario')
        ->set('nominativo', 'Ospite')
        ->call('confermaChiusura')
        ->assertSet('errore', 'PIN non valido.')
        ->assertSet('chiudiId', $comanda->id)
        ->assertSee('PIN non valido');
});

it('annulla restituisce lo stock aggiornato', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazione = Postazione::query()->firstOrFail();
    $limitato = MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $postazione,
        [['menu_item_id' => $limitato->id, 'quantita' => 1]],
        0,
        'contante',
    );

    $this->postJson(route('cassa.postazione'), ['postazione_id' => $postazione->id])->assertOk();

    $this->postJson(route('cassa.annulla', $comanda), ['motivo' => 'sbagliato'])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['stock']);
});
