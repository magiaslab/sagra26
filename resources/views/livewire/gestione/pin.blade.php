<div>
    <div class="panel pin-panel">
        @if (!$recupero)
            <h1>Area gestione</h1>
            <p>Inserisci il PIN per continuare.</p>
            @if ($errore)
                <div class="alert alert-danger">{{ $errore }}</div>
            @endif
            <form wire:submit="sblocca">
                <input class="input pin-input" type="password" inputmode="numeric" autocomplete="one-time-code"
                       wire:model="pin" autofocus maxlength="12">
                <div class="pin-actions">
                    <button class="btn btn-primary" type="submit">Sblocca</button>
                </div>
            </form>
            <p class="pin-help">
                <button type="button" class="btn-linkish" wire:click="apriRecupero">
                    PIN dimenticato?
                </button>
            </p>
        @else
            <h1>Recupero PIN</h1>
            <p>Inserisci il codice di sblocco e scegli un nuovo PIN a 4 cifre.</p>
            @if ($errore)
                <div class="alert alert-danger">{{ $errore }}</div>
            @endif
            <form wire:submit="reimposta" class="pin-form-left">
                <div class="field">
                    <label class="label">Codice di sblocco</label>
                    <input class="input" type="password" wire:model="codiceMaster" autofocus autocomplete="off">
                </div>
                <div class="field">
                    <label class="label">Nuovo PIN (4 cifre)</label>
                    <input class="input" type="password" inputmode="numeric" maxlength="4" wire:model="nuovoPin" autocomplete="new-password">
                </div>
                <div class="field">
                    <label class="label">Conferma nuovo PIN</label>
                    <input class="input" type="password" inputmode="numeric" maxlength="4" wire:model="confermaPin" autocomplete="new-password">
                </div>
                <div class="pin-form-actions">
                    <button class="btn btn-primary" type="submit">Reimposta e entra</button>
                    <button class="btn" type="button" wire:click="annullaRecupero">Annulla</button>
                </div>
            </form>
        @endif
    </div>
</div>
