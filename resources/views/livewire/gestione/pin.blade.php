<div>
    <div class="panel" style="max-width:420px;margin:2rem auto;text-align:center">
        @if (!$recupero)
            <h1>Area gestione</h1>
            <p>Inserisci il PIN per continuare.</p>
            @if ($errore)
                <div class="alert alert-danger">{{ $errore }}</div>
            @endif
            <form wire:submit="sblocca">
                <input class="input" type="password" inputmode="numeric" autocomplete="one-time-code"
                       wire:model="pin" autofocus maxlength="12"
                       style="text-align:center;font-size:1.4rem;letter-spacing:.3em">
                <div style="margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Sblocca</button>
                </div>
            </form>
            <p style="margin-top:1.25rem;font-size:.85rem">
                <button type="button" class="btn btn-sm" wire:click="apriRecupero" style="border:none;background:transparent;text-decoration:underline;cursor:pointer;color:#555">
                    PIN dimenticato?
                </button>
            </p>
        @else
            <h1>Recupero PIN</h1>
            <p>Inserisci il codice di sblocco e scegli un nuovo PIN a 4 cifre.</p>
            @if ($errore)
                <div class="alert alert-danger">{{ $errore }}</div>
            @endif
            <form wire:submit="reimposta" style="text-align:left">
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
                <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Reimposta e entra</button>
                    <button class="btn" type="button" wire:click="annullaRecupero">Annulla</button>
                </div>
            </form>
        @endif
    </div>
</div>
