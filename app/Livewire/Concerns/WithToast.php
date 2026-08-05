<?php

namespace App\Livewire\Concerns;

/**
 * Toast coordinati in tutto il sistema (layout + Alpine store).
 *
 * - {@see toastOk}/{@see toastWarn}/{@see toastDanger}: feedback immediato su azioni Livewire
 * - {@see flashStatus}: per redirect / ricarica pagina (letto da <x-ui.flash-toasts />)
 */
trait WithToast
{
    protected function toast(string $message, string $type = 'ok', ?int $timeout = null): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        if ($timeout !== null) {
            $this->dispatch('toast', message: $message, type: $type, timeout: $timeout);

            return;
        }

        $this->dispatch('toast', message: $message, type: $type);
    }

    protected function toastOk(string $message, ?int $timeout = null): void
    {
        $this->toast($message, 'ok', $timeout);
    }

    protected function toastWarn(string $message, ?int $timeout = null): void
    {
        $this->toast($message, 'warn', $timeout);
    }

    protected function toastDanger(string $message, ?int $timeout = null): void
    {
        $this->toast($message, 'danger', $timeout);
    }

    /**
     * Flash di sessione per redirect o full page load.
     * Chiavi: status (ok), error (danger), warning (warn).
     */
    protected function flashStatus(string $message, string $type = 'ok'): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $key = match ($type) {
            'danger', 'error' => 'error',
            'warn', 'warning' => 'warning',
            default => 'status',
        };

        session()->flash($key, $message);
    }

    /** Toast immediato + flash (utile se l’azione può finire in redirect o restare in pagina). */
    protected function notifyOk(string $message, ?int $timeout = null): void
    {
        $this->toastOk($message, $timeout);
        $this->flashStatus($message, 'ok');
    }
}
