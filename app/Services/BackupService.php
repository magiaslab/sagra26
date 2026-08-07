<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class BackupService
{
    /** Allineato a deploy/backup.sh (~24h a 5'). */
    public const RETENTION = 288;

    public function directory(): string
    {
        $dir = storage_path('backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function databasePath(): string
    {
        $path = (string) config('database.connections.'.config('database.default').'.database');
        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('Database su file non configurato (serve un percorso SQLite).');
        }
        if (! File::exists($path)) {
            throw new RuntimeException('Database SQLite non trovato: '.$path);
        }

        return $path;
    }

    public function usesFileDatabase(): bool
    {
        $path = (string) config('database.connections.'.config('database.default').'.database');

        return $path !== '' && $path !== ':memory:' && File::exists($path);
    }

    /**
     * @return Collection<int, array{filename: string, path: string, size: int, mtime: int, created_label: string, size_label: string}>
     */
    public function list(): Collection
    {
        $dir = $this->directory();

        return collect(File::files($dir))
            ->filter(fn (SplFileInfo $f) => $this->isBackupFilename($f->getFilename()))
            ->sortByDesc(fn (SplFileInfo $f) => $f->getMTime())
            ->values()
            ->map(function (SplFileInfo $f) {
                $mtime = $f->getMTime();

                return [
                    'filename' => $f->getFilename(),
                    'path' => $f->getPathname(),
                    'size' => $f->getSize(),
                    'mtime' => $mtime,
                    'created_label' => date('d/m/Y H:i:s', $mtime),
                    'size_label' => $this->formatBytes($f->getSize()),
                ];
            });
    }

    public function resolve(string $filename): string
    {
        $filename = basename($filename);
        if (! $this->isBackupFilename($filename)) {
            throw new RuntimeException('Nome backup non valido.');
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;
        if (! File::exists($path) || ! File::isFile($path)) {
            throw new RuntimeException('Backup non trovato.');
        }

        $realDir = realpath($this->directory());
        $realFile = realpath($path);
        if ($realDir === false || $realFile === false || ! str_starts_with($realFile, $realDir.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Percorso backup non valido.');
        }

        return $realFile;
    }

    /**
     * Crea un backup immediato della connessione corrente.
     *
     * @return array{filename: string, path: string}
     */
    public function createNow(): array
    {
        $dest = $this->directory().'/database-'.now()->format('Ymd-His').'.sqlite';
        if (File::exists($dest)) {
            $dest = $this->directory().'/database-'.now()->format('Ymd-His').'-'.substr(uniqid(), -4).'.sqlite';
        }

        // VACUUM INTO funziona sia su file sia su :memory:
        try {
            $escaped = str_replace("'", "''", $dest);
            DB::statement("VACUUM INTO '{$escaped}'");
        } catch (Throwable $e) {
            if (! $this->usesFileDatabase()) {
                throw new RuntimeException('Backup fallito: '.$e->getMessage(), 0, $e);
            }

            $this->copySqliteFile($this->databasePath(), $dest);
        }

        if (! File::exists($dest) || File::size($dest) === 0) {
            throw new RuntimeException('Backup creato ma file vuoto o assente.');
        }

        $this->prune();

        return ['filename' => basename($dest), 'path' => $dest];
    }

    /**
     * Ripristina il database da un backup (solo SQLite su file).
     * Prima salva uno snapshot di sicurezza dello stato attuale.
     *
     * @return array{restored: string, safety: string}
     */
    public function restore(string $filename): array
    {
        if (! $this->usesFileDatabase()) {
            throw new RuntimeException('Il ripristino è disponibile solo con database SQLite su file.');
        }

        $backupPath = $this->resolve($filename);
        $dbPath = $this->databasePath();

        $safetyName = 'database-before-restore-'.now()->format('Ymd-His').'.sqlite';
        $safetyPath = $this->directory().'/'.$safetyName;

        try {
            DB::statement("VACUUM INTO '".str_replace("'", "''", $safetyPath)."'");
        } catch (Throwable) {
            $this->copySqliteFile($dbPath, $safetyPath);
        }

        try {
            DB::purge(config('database.default'));
            $this->copySqliteFile($backupPath, $dbPath);
            @chmod($dbPath, 0664);

            DB::reconnect(config('database.default'));
            DB::select('select 1 as ok');
        } catch (Throwable $e) {
            try {
                DB::purge(config('database.default'));
                if (File::exists($safetyPath)) {
                    $this->copySqliteFile($safetyPath, $dbPath);
                    DB::reconnect(config('database.default'));
                }
            } catch (Throwable) {
                // ignore
            }

            throw new RuntimeException('Ripristino fallito: '.$e->getMessage(), 0, $e);
        }

        $this->prune();

        return [
            'restored' => basename($backupPath),
            'safety' => $safetyName,
        ];
    }

    public function delete(string $filename): void
    {
        $path = $this->resolve($filename);
        File::delete($path);
    }

    public function isBackupFilename(string $filename): bool
    {
        return (bool) preg_match(
            '/^database-(\d{8}-\d{6}(-\w+)?|before-restore-\d{8}-\d{6})\.sqlite$/',
            $filename
        );
    }

    public function prune(): void
    {
        $files = collect(File::files($this->directory()))
            ->filter(fn (SplFileInfo $f) => (bool) preg_match('/^database-\d{8}-\d{6}(-\w+)?\.sqlite$/', $f->getFilename()))
            ->sortByDesc(fn (SplFileInfo $f) => $f->getMTime())
            ->values();

        foreach ($files->slice(self::RETENTION) as $old) {
            File::delete($old->getPathname());
        }
    }

    private function copySqliteFile(string $from, string $to): void
    {
        if (! File::exists($from)) {
            throw new RuntimeException('File sorgente assente: '.$from);
        }

        if ($this->sqlite3Available()) {
            $toEsc = str_replace("'", "''", $to);
            $result = Process::timeout(60)->run([
                'sqlite3',
                $from,
                ".backup '{$toEsc}'",
            ]);
            if ($result->successful() && File::exists($to) && File::size($to) > 0) {
                return;
            }
        }

        if (! File::copy($from, $to)) {
            throw new RuntimeException('Copia backup fallita.');
        }
    }

    private function sqlite3Available(): bool
    {
        try {
            return Process::run(['sqlite3', '-version'])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
