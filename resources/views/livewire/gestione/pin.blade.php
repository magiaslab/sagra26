<div>
    <div class="mx-auto mt-8 max-w-md rounded-lg bg-white p-5 text-center shadow-sm ring-1 ring-sagra-line/80">
        @if (!$recupero)
            <h1 class="mb-2 mt-0 text-2xl font-semibold text-sagra-ink">Area gestione</h1>
            <p class="mb-4 mt-0 text-sagra-muted">Inserisci il PIN per continuare.</p>
            @if ($errore)
                <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
            @endif
            <form wire:submit="sblocca">
                <input class="block w-full rounded-md bg-white px-3 py-2 text-center text-xl tracking-[0.3em] text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="password" inputmode="numeric" autocomplete="one-time-code"
                       wire:model="pin" autofocus maxlength="12">
                <div class="mt-4">
                    <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" type="submit">Sblocca</button>
                </div>
            </form>
            <p class="mt-5 text-sm">
                <button type="button" class="border-0 bg-transparent px-1 py-0.5 font-semibold text-sagra-muted underline underline-offset-2 hover:text-sagra" wire:click="apriRecupero">
                    PIN dimenticato?
                </button>
            </p>
        @else
            <h1 class="mb-2 mt-0 text-2xl font-semibold text-sagra-ink">Recupero PIN</h1>
            <p class="mb-4 mt-0 text-sagra-muted">Inserisci il codice di sblocco e scegli un nuovo PIN a 4 cifre.</p>
            @if ($errore)
                <x-ui.alert type="danger">{{ $errore }}</x-ui.alert>
            @endif
            <form wire:submit="reimposta" class="text-left">
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Codice di sblocco</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="password" wire:model="codiceMaster" autofocus autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Nuovo PIN (4 cifre)</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="password" inputmode="numeric" maxlength="4" wire:model="nuovoPin" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Conferma nuovo PIN</label>
                    <input class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="password" inputmode="numeric" maxlength="4" wire:model="confermaPin" autocomplete="new-password">
                </div>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" type="submit">Reimposta e entra</button>
                    <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" type="button" wire:click="annullaRecupero">Annulla</button>
                </div>
            </form>
        @endif
    </div>
</div>
