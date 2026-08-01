<div>
    <div class="panel mx-auto mt-8 max-w-md text-center">
        @if (!$recupero)
            <h1 class="mt-0 mb-2 text-2xl font-extrabold text-sagra-ink">Area gestione</h1>
            <p class="mt-0 mb-4 text-sagra-muted">Inserisci il PIN per continuare.</p>
            @if ($errore)
                <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
            @endif
            <form wire:submit="sblocca">
                <input class="input text-center text-xl tracking-[0.3em]" type="password" inputmode="numeric" autocomplete="one-time-code"
                       wire:model="pin" autofocus maxlength="12">
                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">Sblocca</button>
                </div>
            </form>
            <p class="mt-5 text-sm">
                <button type="button" class="border-0 bg-transparent px-1 py-0.5 font-semibold text-sagra-muted underline underline-offset-2 hover:text-sagra" wire:click="apriRecupero">
                    PIN dimenticato?
                </button>
            </p>
        @else
            <h1 class="mt-0 mb-2 text-2xl font-extrabold text-sagra-ink">Recupero PIN</h1>
            <p class="mt-0 mb-4 text-sagra-muted">Inserisci il codice di sblocco e scegli un nuovo PIN a 4 cifre.</p>
            @if ($errore)
                <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
            @endif
            <form wire:submit="reimposta" class="text-left">
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
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <button class="btn btn-primary" type="submit">Reimposta e entra</button>
                    <button class="btn" type="button" wire:click="annullaRecupero">Annulla</button>
                </div>
            </form>
        @endif
    </div>
</div>
