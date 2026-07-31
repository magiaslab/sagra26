<?php

namespace App\Livewire\Gestione;

use App\Models\Impostazione;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Pin extends Component
{
    public string $pin = '';

    public bool $recupero = false;

    public string $codiceMaster = '';

    public string $nuovoPin = '';

    public string $confermaPin = '';

    public ?string $errore = null;

    public ?string $messaggio = null;

    public function apriRecupero(): void
    {
        $this->recupero = true;
        $this->errore = null;
        $this->messaggio = null;
        $this->pin = '';
        $this->codiceMaster = '';
        $this->nuovoPin = '';
        $this->confermaPin = '';
    }

    public function annullaRecupero(): void
    {
        $this->recupero = false;
        $this->errore = null;
        $this->messaggio = null;
        $this->codiceMaster = '';
        $this->nuovoPin = '';
        $this->confermaPin = '';
    }

    public function sblocca(): void
    {
        $this->errore = null;
        $this->messaggio = null;

        $key = $this->rateLimitKey('pin');
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->errore = 'Troppi tentativi, riprova tra qualche minuto.';
            $this->pin = '';

            return;
        }

        RateLimiter::hit($key, 600);

        $atteso = Impostazione::corrente()->pin_gestione;
        if (hash_equals((string) $atteso, $this->pin)) {
            RateLimiter::clear($key);
            session(['gestione_sbloccata' => true]);
            $this->redirect(route('gestione.dashboard'), navigate: true);

            return;
        }

        $this->errore = 'PIN non corretto.';
        $this->pin = '';
    }

    public function reimposta(): void
    {
        $this->errore = null;
        $this->messaggio = null;

        $key = $this->rateLimitKey('master');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->errore = 'Troppi tentativi, riprova tra qualche minuto.';
            $this->codiceMaster = '';

            return;
        }

        RateLimiter::hit($key, 600);

        $master = (string) (config('gestione.pin_master_reset') ?? '');
        if ($master === '' || ! hash_equals($master, $this->codiceMaster)) {
            $this->errore = 'Codice di sblocco non valido.';
            $this->codiceMaster = '';

            return;
        }

        if (! preg_match('/^\d{4}$/', $this->nuovoPin)) {
            $this->errore = 'Il nuovo PIN deve essere di 4 cifre.';

            return;
        }

        if (! hash_equals($this->nuovoPin, $this->confermaPin)) {
            $this->errore = 'Nuovo PIN e conferma non coincidono.';

            return;
        }

        Impostazione::corrente()->update(['pin_gestione' => $this->nuovoPin]);

        RateLimiter::clear($key);
        RateLimiter::clear($this->rateLimitKey('pin'));

        Log::warning('PIN gestione reimpostato via codice master', [
            'ip' => request()->ip(),
        ]);

        session(['gestione_sbloccata' => true]);
        $this->redirect(route('gestione.dashboard'), navigate: true);
    }

    private function rateLimitKey(string $scope): string
    {
        return 'gestione-'.$scope.':'.request()->ip();
    }

    public function render()
    {
        return view('livewire.gestione.pin')
            ->layout('layouts.app', ['impostazioni' => Impostazione::corrente()]);
    }
}
