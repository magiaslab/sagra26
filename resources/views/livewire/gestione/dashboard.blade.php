<div>
    <x-gestione.subnav />
    <div class="mb-4 rounded-lg border border-sagra-amber/40 bg-sagra-amber-soft px-4 py-3 text-sm text-sagra-ink">
        <strong>Anteprima Filament v5</strong> (branch di test):
        <a class="font-semibold text-sagra underline" href="/gestione-fi">apri il nuovo pannello Gestione</a>
        — la Cassa non è toccata; questa UI Livewire resta come fallback.
    </div>
    <x-gestione.page-header
        title="Gestione"
        subtitle="Apertura serata, menù, chiusure e report"
    >
        <x-slot:actions>
            @if ($serata)
                <span class="inline-flex min-h-9 items-center rounded-md bg-sagra-softer px-3 py-1.5 text-sm font-medium text-sagra-dark">
                    Serata aperta · {{ $serata->data->format('d/m/Y') }}
                </span>
            @else
                <span class="inline-flex min-h-9 items-center rounded-md bg-sagra-amber-soft px-3 py-1.5 text-sm font-medium text-sagra-warn">
                    Nessuna serata aperta
                </span>
                <a class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" href="{{ route('gestione.serate', absolute: false) }}">Apri serata</a>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="divide-y divide-sagra-line overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.serate', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Operativo</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Serate</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Apertura, stock limitati e chiusura della serata</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.menu', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Catalogo</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Menù</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Voci, prezzi, stock default e aree stampa</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.chiusura', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Fine turno</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Chiusura cassa</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Conta pezzi e riconciliazione a tre vie</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.sospesi', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">In serata</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Sospesi</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Comande da saldare: elenco e chiusura / incasso</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.omaggi', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">In serata</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Omaggi</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Elenco omaggi con ospite e autorizzatore · export CSV</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.report', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Stampe</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Report</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Cucina, griglia, bevande, economico, consegna</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.impostazioni', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Sistema</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Impostazioni</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Postazioni, punti cassa e PIN gestione</p>
            </div>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.stato', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Sistema</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Stato sistema</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Database, backup e spazio disco</p>
            </div>
        </a>
    </div>

    <h2 class="mb-3 mt-8 text-base font-semibold text-sagra-ink">Documenti e aiuto</h2>
    <div class="divide-y divide-sagra-line overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.guida', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Aiuto</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Guida operativa cassa</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">Come aprire la serata, prendere comande, correggere, chiudere — passo dopo passo</p>
            </div>
            <span class="self-center text-sm font-medium text-sagra">Apri →</span>
        </a>
        <a class="flex items-start gap-4 px-5 py-4 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.documenti.liberatoria', absolute: false) }}">
            <div class="flex-1">
                <span class="text-xs font-medium uppercase tracking-wide text-sagra">Moduli</span>
                <h2 class="mt-0.5 text-lg font-semibold text-sagra-ink">Liberatoria volontari minori</h2>
                <p class="mt-0.5 text-sm text-sagra-muted">PDF da scaricare, stampare e far firmare al genitore/tutore</p>
            </div>
            <span class="self-center text-sm font-medium text-sagra">Scarica PDF ↓</span>
        </a>
    </div>
</div>
