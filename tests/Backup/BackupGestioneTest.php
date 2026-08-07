<?php

use App\Livewire\Gestione\BackupPage;
use App\Models\Impostazione;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $path = database_path('testing-backup.sqlite');

    DB::purge('sqlite');
    if (File::exists($path)) {
        File::delete($path);
    }
    File::put($path, '');

    $this->app['config']->set('database.connections.sqlite.database', $path);
    // Evita che RefreshDatabaseState (da altri test) riattacchi un PDO :memory:.
    RefreshDatabaseState::$inMemoryConnections = [];
    RefreshDatabaseState::$migrated = false;

    DB::reconnect('sqlite');
    $this->artisan('migrate:fresh', ['--force' => true, '--seed' => true]);

    File::ensureDirectoryExists(storage_path('backups'));
    foreach (File::files(storage_path('backups')) as $file) {
        if (str_starts_with($file->getFilename(), 'database-')) {
            File::delete($file->getPathname());
        }
    }
});

afterEach(function () {
    DB::purge('sqlite');
    $path = database_path('testing-backup.sqlite');
    if (File::exists($path)) {
        File::delete($path);
    }
});

it('richiede il PIN di gestione per la pagina backup', function () {
    $this->get(route('gestione.backup'))
        ->assertRedirect(route('gestione.pin'));
});

it('elenca i backup e permette download dopo PIN', function () {
    $name = 'database-'.now()->format('Ymd-His').'.sqlite';
    File::put(storage_path('backups/'.$name), 'sqlite-backup-test');

    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(BackupPage::class)
        ->assertSee('Backup')
        ->assertSee($name)
        ->assertSee('Esegui backup ora')
        ->assertSee('Scarica');

    $this->get(route('gestione.backup.download', ['filename' => $name]))
        ->assertOk();
});

it('rifiuta download di filename non validi', function () {
    $this->withSession(['gestione_sbloccata' => true]);

    $this->get('/gestione/backup/download/'.rawurlencode('../.env'))
        ->assertNotFound();
});

it('esegue un backup immediato dalla UI', function () {
    $this->withSession(['gestione_sbloccata' => true]);

    expect(app(BackupService::class)->list())->toHaveCount(0);

    Livewire::test(BackupPage::class)
        ->call('eseguiOra')
        ->assertDispatched('toast');

    expect(app(BackupService::class)->list()->count())->toBeGreaterThan(0);
});

it('ripristina un backup con conferma RIPRISTINA e crea safety copy', function () {
    $service = app(BackupService::class);

    Impostazione::corrente()->update(['intestazione_sottotitolo' => 'STATO-A']);
    $created = $service->createNow();
    $backupName = $created['filename'];

    Impostazione::corrente()->update(['intestazione_sottotitolo' => 'STATO-B']);
    expect(Impostazione::corrente()->fresh()->intestazione_sottotitolo)->toBe('STATO-B');

    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(BackupPage::class)
        ->call('chiediRipristino', $backupName)
        ->set('testoConferma', 'sbagliato')
        ->call('eseguiRipristino')
        ->assertSet('fileDaRipristinare', $backupName);

    expect(Impostazione::corrente()->fresh()->intestazione_sottotitolo)->toBe('STATO-B');

    Livewire::test(BackupPage::class)
        ->call('chiediRipristino', $backupName)
        ->set('testoConferma', 'RIPRISTINA')
        ->call('eseguiRipristino')
        ->assertSet('fileDaRipristinare', null)
        ->assertDispatched('toast');

    expect(Impostazione::corrente()->fresh()->intestazione_sottotitolo)->toBe('STATO-A');

    $safety = $service->list()->first(
        fn (array $b) => str_starts_with($b['filename'], 'database-before-restore-')
    );
    expect($safety)->not->toBeNull();
});

it('elimina un backup dopo conferma', function () {
    $name = 'database-'.now()->format('Ymd-His').'.sqlite';
    File::put(storage_path('backups/'.$name), 'x');

    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(BackupPage::class)
        ->call('chiediElimina', $name)
        ->call('confermaElimina')
        ->assertSet('fileDaEliminare', null);

    expect(File::exists(storage_path('backups/'.$name)))->toBeFalse();
});

it('la dashboard e la subnav espongono il link Backup', function () {
    $this->withSession(['gestione_sbloccata' => true])
        ->get(route('gestione.dashboard'))
        ->assertOk()
        ->assertSee('Backup')
        ->assertSee(route('gestione.backup', absolute: false), false);
});
