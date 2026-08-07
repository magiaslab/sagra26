<?php

namespace App\Livewire\Gestione;

use App\Livewire\Concerns\WithToast;
use App\Models\Impostazione;
use App\Services\BackupService;
use Livewire\Component;
use Throwable;

class BackupPage extends Component
{
    use WithToast;

    public string $testoConferma = '';

    public ?string $fileDaRipristinare = null;

    public ?string $fileDaEliminare = null;

    public function eseguiOra(BackupService $backups): void
    {
        try {
            $created = $backups->createNow();
            $this->toastOk('Backup creato: '.$created['filename']);
        } catch (Throwable $e) {
            $this->toastDanger($e->getMessage());
        }
    }

    public function chiediRipristino(string $filename): void
    {
        $this->fileDaRipristinare = basename($filename);
        $this->testoConferma = '';
        $this->fileDaEliminare = null;
    }

    public function annullaRipristino(): void
    {
        $this->fileDaRipristinare = null;
        $this->testoConferma = '';
    }

    public function eseguiRipristino(BackupService $backups): void
    {
        if (! $this->fileDaRipristinare) {
            return;
        }

        if (trim($this->testoConferma) !== 'RIPRISTINA') {
            $this->toastWarn('Per confermare digita esattamente RIPRISTINA.');

            return;
        }

        try {
            $result = $backups->restore($this->fileDaRipristinare);
            $this->toastOk(
                'Database ripristinato da '.$result['restored']
                .'. Copia di sicurezza: '.$result['safety']
            );
            $this->annullaRipristino();
        } catch (Throwable $e) {
            $this->toastDanger($e->getMessage());
        }
    }

    public function chiediElimina(string $filename): void
    {
        $this->fileDaEliminare = basename($filename);
        $this->fileDaRipristinare = null;
        $this->testoConferma = '';
    }

    public function annullaElimina(): void
    {
        $this->fileDaEliminare = null;
    }

    public function confermaElimina(BackupService $backups): void
    {
        if (! $this->fileDaEliminare) {
            return;
        }

        try {
            $name = $this->fileDaEliminare;
            $backups->delete($name);
            $this->toastOk('Backup eliminato: '.$name);
            $this->annullaElimina();
        } catch (Throwable $e) {
            $this->toastDanger($e->getMessage());
        }
    }

    public function render(BackupService $backups)
    {
        return view('livewire.gestione.backup', [
            'backups' => $backups->list(),
            'backupDir' => $backups->directory(),
            'retention' => BackupService::RETENTION,
            'scriptExists' => is_file(base_path('deploy/backup.sh')),
        ])->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
