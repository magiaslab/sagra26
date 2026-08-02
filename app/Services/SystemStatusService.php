<?php

namespace App\Services;

use App\Models\Comanda;
use App\Models\Serata;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemStatusService
{
    /** Cron previsto ogni 5 minuti → oltre 15' è warning. */
    public const BACKUP_WARN_MINUTES = 15;

    /** Oltre 60' senza backup → danger. */
    public const BACKUP_DANGER_MINUTES = 60;

    /** Spazio libero sotto questa soglia (bytes) → warning. */
    public const DISK_WARN_BYTES = 500 * 1024 * 1024;

    /** Spazio libero sotto questa soglia (bytes) → danger. */
    public const DISK_DANGER_BYTES = 100 * 1024 * 1024;

    /**
     * @return array{
     *     overall: string,
     *     checked_at: string,
     *     checks: list<array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}>
     * }
     */
    public function collect(): array
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkBackups(),
            $this->checkStorageWritable(),
            $this->checkDiskSpace(),
            $this->checkApp(),
            $this->checkSerata(),
        ];

        return [
            'overall' => $this->worstStatus(array_column($checks, 'status')),
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function checkDatabase(): array
    {
        $path = (string) config('database.connections.'.config('database.default').'.database');
        $detail = [
            'driver' => config('database.default'),
            'path' => $path,
            'exists' => is_string($path) && $path !== '' && File::exists($path),
            'size_bytes' => null,
            'writable' => null,
            'query_ok' => false,
        ];

        try {
            DB::select('select 1 as ok');
            $detail['query_ok'] = true;

            if ($detail['exists']) {
                $detail['size_bytes'] = File::size($path);
                $detail['writable'] = is_writable($path);
            }

            if (! $detail['query_ok']) {
                return $this->check('database', 'Database', 'danger', 'Query di prova fallita.', $detail);
            }

            if ($detail['exists'] && $detail['writable'] === false) {
                return $this->check('database', 'Database', 'warn', 'Database raggiungibile ma file non scrivibile.', $detail);
            }

            $size = $detail['size_bytes'] !== null ? $this->formatBytes((int) $detail['size_bytes']) : 'n/d';

            return $this->check('database', 'Database', 'ok', "Risponde correttamente ({$size}).", $detail);
        } catch (Throwable $e) {
            $detail['error'] = $e->getMessage();

            return $this->check('database', 'Database', 'danger', 'Database non raggiungibile.', $detail);
        }
    }

    /**
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function checkBackups(): array
    {
        $dir = storage_path('backups');
        $files = File::exists($dir)
            ? collect(File::files($dir))
                ->filter(fn ($f) => str_starts_with($f->getFilename(), 'database-') && str_ends_with($f->getFilename(), '.sqlite'))
                ->sortByDesc(fn ($f) => $f->getMTime())
                ->values()
            : collect();

        $latest = $files->first();
        $ageMinutes = $latest ? (int) floor((now()->getTimestamp() - $latest->getMTime()) / 60) : null;

        $detail = [
            'directory' => $dir,
            'count' => $files->count(),
            'latest' => $latest?->getFilename(),
            'latest_mtime' => $latest ? date('c', $latest->getMTime()) : null,
            'age_minutes' => $ageMinutes,
            'script' => base_path('deploy/backup.sh'),
            'script_exists' => File::exists(base_path('deploy/backup.sh')),
        ];

        if (! $latest) {
            return $this->check(
                'backup',
                'Backup',
                'danger',
                'Nessun backup trovato in storage/backups.',
                $detail,
            );
        }

        if ($ageMinutes >= self::BACKUP_DANGER_MINUTES) {
            return $this->check(
                'backup',
                'Backup',
                'danger',
                "Ultimo backup di {$ageMinutes} minuti fa ({$latest->getFilename()}).",
                $detail,
            );
        }

        if ($ageMinutes >= self::BACKUP_WARN_MINUTES) {
            return $this->check(
                'backup',
                'Backup',
                'warn',
                "Ultimo backup di {$ageMinutes} minuti fa (cron atteso ogni 5').",
                $detail,
            );
        }

        return $this->check(
            'backup',
            'Backup',
            'ok',
            "Ultimo backup {$ageMinutes} min fa · {$files->count()} file.",
            $detail,
        );
    }

    /**
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function checkStorageWritable(): array
    {
        $paths = [
            'storage' => storage_path(),
            'framework' => storage_path('framework'),
            'logs' => storage_path('logs'),
            'backups' => storage_path('backups'),
        ];

        $detail = [];
        $bad = [];
        foreach ($paths as $key => $path) {
            if (! File::exists($path)) {
                File::ensureDirectoryExists($path);
            }
            $writable = is_writable($path);
            $detail[$key] = ['path' => $path, 'writable' => $writable];
            if (! $writable) {
                $bad[] = $key;
            }
        }

        if ($bad !== []) {
            return $this->check(
                'storage',
                'Storage',
                'danger',
                'Directory non scrivibili: '.implode(', ', $bad).'.',
                $detail,
            );
        }

        return $this->check('storage', 'Storage', 'ok', 'Directory di lavoro scrivibili.', $detail);
    }

    /**
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function checkDiskSpace(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        $detail = [
            'path' => $path,
            'free_bytes' => $free === false ? null : (int) $free,
            'total_bytes' => $total === false ? null : (int) $total,
        ];

        if ($free === false) {
            return $this->check('disk', 'Spazio disco', 'warn', 'Impossibile leggere lo spazio libero.', $detail);
        }

        $free = (int) $free;
        $pct = ($total && $total > 0) ? round(($free / $total) * 100, 1) : null;
        $label = $this->formatBytes($free).($pct !== null ? " ({$pct}%)" : '');

        if ($free < self::DISK_DANGER_BYTES) {
            return $this->check('disk', 'Spazio disco', 'danger', "Spazio libero critico: {$label}.", $detail);
        }

        if ($free < self::DISK_WARN_BYTES) {
            return $this->check('disk', 'Spazio disco', 'warn', "Spazio libero basso: {$label}.", $detail);
        }

        return $this->check('disk', 'Spazio disco', 'ok', "Liberi {$label}.", $detail);
    }

    /**
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function checkApp(): array
    {
        $detail = [
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'timezone' => config('app.timezone'),
            'health_url' => url('/up'),
        ];

        $status = 'ok';
        $summary = 'Applicazione in esecuzione (PHP '.$detail['php'].').';

        if ($detail['app_env'] === 'production' && $detail['app_debug']) {
            $status = 'warn';
            $summary = 'APP_DEBUG attivo in production.';
        }

        return $this->check('app', 'Applicazione', $status, $summary, $detail);
    }

    /**
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function checkSerata(): array
    {
        $serata = Serata::corrente();
        $comandeOggi = 0;
        if ($serata) {
            $comandeOggi = Comanda::query()->where('serata_id', $serata->id)->count();
        }

        $detail = [
            'aperta' => $serata !== null,
            'data' => $serata?->data?->toDateString(),
            'stato' => $serata?->stato,
            'comande' => $comandeOggi,
        ];

        if (! $serata) {
            return $this->check(
                'serata',
                'Serata',
                'warn',
                'Nessuna serata aperta (ok se fuori orario).',
                $detail,
            );
        }

        return $this->check(
            'serata',
            'Serata',
            'ok',
            'Serata aperta '.$serata->data->format('d/m/Y')." · {$comandeOggi} comande.",
            $detail,
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array{key: string, label: string, status: string, summary: string, detail: array<string, mixed>}
     */
    private function check(string $key, string $label, string $status, string $summary, array $detail): array
    {
        return compact('key', 'label', 'status', 'summary', 'detail');
    }

    /**
     * @param  list<string>  $statuses
     */
    private function worstStatus(array $statuses): string
    {
        if (in_array('danger', $statuses, true)) {
            return 'danger';
        }
        if (in_array('warn', $statuses, true)) {
            return 'warn';
        }

        return 'ok';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
