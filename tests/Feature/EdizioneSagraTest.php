<?php

use App\Livewire\Gestione\EdizionePage;
use App\Models\Edizione;
use App\Models\Impostazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\EdizioneService;
use App\Services\SerataService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('la migrazione crea un’edizione aperta e le serate nuove la ereditano', function () {
    expect(Edizione::corrente())->not->toBeNull();

    $puntoId = PuntoCassa::query()->first()->id;
    $serata = app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    expect($serata->edizione_id)->toBe(Edizione::corrente()->id);
});

it('non apre una seconda edizione se ce n’è già una aperta', function () {
    app(EdizioneService::class)->assicuratiCorrente();

    expect(fn () => app(EdizioneService::class)->apri((int) date('Y') + 1))
        ->toThrow(RuntimeException::class, 'già un’edizione aperta');
});

it('non chiude l’edizione se c’è una serata aperta', function () {
    $edizione = app(EdizioneService::class)->assicuratiCorrente();
    $puntoId = PuntoCassa::query()->first()->id;
    app(SerataService::class)->apri(now()->toDateString(), null, [], [$puntoId => 50]);

    expect(fn () => app(EdizioneService::class)->chiudi($edizione))
        ->toThrow(RuntimeException::class, 'Chiudi prima la serata');
});

it('chiude l’edizione e permette di aprirne una nuova', function () {
    $svc = app(EdizioneService::class);
    $edizione = $svc->assicuratiCorrente();
    $annoCorrente = $edizione->anno;

    $svc->chiudi($edizione, 'fine sagra');
    expect(Edizione::corrente())->toBeNull()
        ->and($edizione->fresh()->stato)->toBe('archiviata');

    $nuova = $svc->apri($annoCorrente + 1, 'Sagra '.($annoCorrente + 1));
    expect(Edizione::corrente()?->id)->toBe($nuova->id)
        ->and($nuova->anno)->toBe($annoCorrente + 1);
});

it('queryEdizione limita alle serate dell’edizione aperta', function () {
    $svc = app(EdizioneService::class);
    $edizione = $svc->assicuratiCorrente();
    $puntoId = PuntoCassa::query()->first()->id;
    $serataService = app(SerataService::class);

    $s1 = $serataService->apri(now()->toDateString(), null, [], [$puntoId => 50]);
    $serataService->chiudi($s1);

    $svc->chiudi($edizione);
    $nuova = $svc->apri($edizione->anno + 1);
    $s2 = $serataService->apri(now()->addDay()->toDateString(), null, [], [$puntoId => 50]);

    $ids = Serata::queryEdizione()->pluck('id')->all();
    expect($ids)->toContain($s2->id)
        ->and($ids)->not->toContain($s1->id)
        ->and($s2->edizione_id)->toBe($nuova->id);
});

it('la pagina edizione è raggiungibile dopo il pin', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.edizione'))
        ->assertOk()
        ->assertSee('Edizione sagra')
        ->assertSee('Storico edizioni');
});

it('la dashboard gestione linka edizione sagra', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->assertSee('Edizione sagra')
        ->assertSee(route('gestione.edizione', absolute: false), false);
});

it('livewire chiude l’edizione solo digitando CHIUDI', function () {
    app(EdizioneService::class)->assicuratiCorrente();

    Livewire::test(EdizionePage::class)
        ->call('preparaChiusura')
        ->set('confermaChiusura', 'sbagliato')
        ->call('chiudiEdizione')
        ->assertSet('chiediChiusura', true);

    expect(Edizione::corrente())->not->toBeNull();

    Livewire::test(EdizionePage::class)
        ->call('preparaChiusura')
        ->set('confermaChiusura', 'CHIUDI')
        ->set('noteChiusura', 'ok')
        ->call('chiudiEdizione')
        ->assertSet('chiediChiusura', false);

    expect(Edizione::corrente())->toBeNull();
});

it('riaprire un’edizione archiviata aggiorna intestazione_anno', function () {
    $svc = app(EdizioneService::class);
    $edizione = $svc->assicuratiCorrente();
    $anno = $edizione->anno;
    $svc->chiudi($edizione);

    Livewire::test(EdizionePage::class)
        ->call('riapri', $edizione->id);

    expect(Edizione::corrente()?->id)->toBe($edizione->id)
        ->and(Impostazione::corrente()->fresh()->intestazione_anno)->toBe((string) $anno);
});
