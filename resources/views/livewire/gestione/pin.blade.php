<div>
    <div class="panel" style="max-width:360px;margin:2rem auto;text-align:center">
        <h1>Area gestione</h1>
        <p>Inserisci il PIN per continuare.</p>
        @if ($errore)
            <div class="alert alert-danger">{{ $errore }}</div>
        @endif
        <form wire:submit="sblocca">
            <input class="input" type="password" wire:model="pin" autofocus maxlength="12" style="text-align:center;font-size:1.4rem;letter-spacing:.3em">
            <div style="margin-top:1rem">
                <button class="btn btn-primary" type="submit">Sblocca</button>
            </div>
        </form>
    </div>
</div>
