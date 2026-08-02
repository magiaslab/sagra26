<?php

use App\Livewire\Gestione\StatoSistema;
use App\Services\SystemStatusService;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

it('richiede il PIN di gestione', function () {
    $this->get(route('gestione.stato'))
        ->assertRedirect(route('gestione.pin'));
});

it('mostra la pagina stato sistema dopo lo sblocco PIN', function () {
    $backupDir = storage_path('backups');
    File::ensureDirectoryExists($backupDir);
    $fresh = $backupDir.'/database-'.now()->format('Ymd-His').'.sqlite';
    File::put($fresh, 'sqlite-backup-test');

    $this->withSession(['gestione_sbloccata' => true]);

    Livewire::test(StatoSistema::class)
        ->assertSee('Stato sistema')
        ->assertSee('Database')
        ->assertSee('Backup')
        ->assertSee('Spazio disco')
        ->assertSee('Aggiorna');

    File::delete($fresh);
});

it('segna danger se non ci sono backup', function () {
    $backupDir = storage_path('backups');
    File::ensureDirectoryExists($backupDir);
    foreach (File::files($backupDir) as $file) {
        if (str_starts_with($file->getFilename(), 'database-')) {
            File::delete($file->getPathname());
        }
    }

    $report = app(SystemStatusService::class)->collect();
    $backup = collect($report['checks'])->firstWhere('key', 'backup');

    expect($backup['status'])->toBe('danger')
        ->and($report['overall'])->toBe('danger');
});

it('segna ok il backup se recente', function () {
    $backupDir = storage_path('backups');
    File::ensureDirectoryExists($backupDir);
    $fresh = $backupDir.'/database-'.now()->format('Ymd-His').'.sqlite';
    File::put($fresh, 'sqlite-backup-test');

    $report = app(SystemStatusService::class)->collect();
    $backup = collect($report['checks'])->firstWhere('key', 'backup');
    $database = collect($report['checks'])->firstWhere('key', 'database');

    expect($backup['status'])->toBe('ok')
        ->and($database['status'])->toBe('ok');

    File::delete($fresh);
});

it('segna warn se il backup è vecchio oltre la soglia', function () {
    $backupDir = storage_path('backups');
    File::ensureDirectoryExists($backupDir);
    foreach (File::files($backupDir) as $file) {
        if (str_starts_with($file->getFilename(), 'database-')) {
            File::delete($file->getPathname());
        }
    }

    $stale = $backupDir.'/database-stale-test.sqlite';
    File::put($stale, 'old');
    touch($stale, now()->subMinutes(SystemStatusService::BACKUP_WARN_MINUTES + 1)->getTimestamp());

    $report = app(SystemStatusService::class)->collect();
    $backup = collect($report['checks'])->firstWhere('key', 'backup');

    expect($backup['status'])->toBe('warn');

    File::delete($stale);
});
