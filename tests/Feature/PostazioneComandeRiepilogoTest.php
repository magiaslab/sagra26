<?php

use App\Livewire\RiepilogoLive;
use App\Models\Postazione;
use App\Models\PuntoCassa;
use App\Models\MenuItem;
use App\Services\ComandaService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('il richiamo espone la postazione che ha emesso la comanda', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazioni = Postazione::query()->orderBy('id')->get();
    $a = $postazioni[0];
    $b = $postazioni[1];
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();

    $comanda = app(ComandaService::class)->confermaEStampa(
        $serata,
        $a,
        [['menu_item_id' => $acqua->id, 'quantita' => 1]],
        0,
        'contante',
    );

    // Richiamo da qualsiasi postazione: resta visibile chi l’ha emessa.
    $this->getJson(route('cassa.richiamo', $comanda->numero_progressivo))
        ->assertOk()
        ->assertJsonPath('postazione_id', $a->id)
        ->assertJsonPath('postazione', $a->nome);

    expect($comanda->fresh()->postazione_id)->toBe($a->id)
        ->and($comanda->fresh()->postazione_id)->not->toBe($b->id);
});

it('il riepilogo mostra quante comande ha fatto ciascuna postazione', function () {
    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $postazioni = Postazione::query()->orderBy('id')->get();
    $a = $postazioni[0];
    $b = $postazioni[1];
    $acqua = MenuItem::query()->where('nome', 'Acqua Naturale 1L')->firstOrFail();
    $service = app(ComandaService::class);

    $service->confermaEStampa($serata, $a, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'contante');
    $service->confermaEStampa($serata, $a, [['menu_item_id' => $acqua->id, 'quantita' => 1]], 0, 'pos');
    $service->confermaEStampa($serata, $b, [['menu_item_id' => $acqua->id, 'quantita' => 2]], 0, 'contante');

    Livewire::test(RiepilogoLive::class)
        ->assertSee('Comande per postazione')
        ->assertSee($a->nome)
        ->assertSee($b->nome)
        ->assertViewHas('dati', function (array $dati) use ($a, $b) {
            $per = collect($dati['per_postazione'])->keyBy('nome');

            return ($per[$a->nome]['n'] ?? 0) === 2
                && ($per[$b->nome]['n'] ?? 0) === 1;
        });

    $this->get(route('riepilogo'))
        ->assertOk()
        ->assertSee('Comande per postazione')
        ->assertSee('comande');
});

it('la ui richiamo è allargata e mostra la postazione in lista', function () {
    $html = $this->get(route('cassa'))->assertOk()->getContent();

    expect($html)
        ->toContain('w-[min(58rem,96vw)]')
        ->toContain('>Correggi</button>')
        ->toContain('>Ristampa</button>')
        ->toContain('>Annulla</button>')
        ->toContain('c.postazione')
        ->toContain('Emessa da')
        ->toContain('postazioneOriginale')
        ->toContain('max-h-[calc(100dvh-1.5rem)]')
        ->toContain('overflow-y-auto overscroll-contain');
});
