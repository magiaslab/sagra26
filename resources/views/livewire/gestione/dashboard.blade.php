<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Gestione"
        subtitle="Apertura serata, menù, chiusure e report"
    >
        <x-slot:actions>
            @if ($serata)
                <span class="inline-flex min-h-9 items-center rounded-full border border-sagra/35 bg-sagra-softer px-3 py-1 text-sm font-bold text-sagra-dark">
                    Serata aperta · {{ $serata->data->format('d/m/Y') }}
                </span>
            @else
                <span class="inline-flex min-h-9 items-center rounded-full border border-sagra-warn/35 bg-sagra-amber-soft px-3 py-1 text-sm font-bold text-sagra-warn">
                    Nessuna serata aperta
                </span>
                <a class="btn btn-primary btn-sm" href="{{ route('gestione.serate', absolute: false) }}">Apri serata</a>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="mt-1 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <a class="flex min-h-[8.5rem] flex-col gap-1.5 rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:-translate-y-px hover:border-sagra hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.serate', absolute: false) }}">
            <span class="text-[0.72rem] font-extrabold uppercase tracking-wider text-sagra">Operativo</span>
            <h2 class="m-0 text-[1.35rem] font-bold text-sagra-ink">Serate</h2>
            <p class="m-0 text-[0.95rem] leading-snug text-sagra-muted">Apertura, stock limitati e chiusura della serata</p>
        </a>
        <a class="flex min-h-[8.5rem] flex-col gap-1.5 rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:-translate-y-px hover:border-sagra hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.menu', absolute: false) }}">
            <span class="text-[0.72rem] font-extrabold uppercase tracking-wider text-sagra">Catalogo</span>
            <h2 class="m-0 text-[1.35rem] font-bold text-sagra-ink">Menù</h2>
            <p class="m-0 text-[0.95rem] leading-snug text-sagra-muted">Voci, prezzi, stock default e aree stampa</p>
        </a>
        <a class="flex min-h-[8.5rem] flex-col gap-1.5 rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:-translate-y-px hover:border-sagra hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.chiusura', absolute: false) }}">
            <span class="text-[0.72rem] font-extrabold uppercase tracking-wider text-sagra">Fine turno</span>
            <h2 class="m-0 text-[1.35rem] font-bold text-sagra-ink">Chiusura cassa</h2>
            <p class="m-0 text-[0.95rem] leading-snug text-sagra-muted">Conta pezzi e riconciliazione a tre vie</p>
        </a>
        <a class="flex min-h-[8.5rem] flex-col gap-1.5 rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:-translate-y-px hover:border-sagra hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.report', absolute: false) }}">
            <span class="text-[0.72rem] font-extrabold uppercase tracking-wider text-sagra">Stampe</span>
            <h2 class="m-0 text-[1.35rem] font-bold text-sagra-ink">Report</h2>
            <p class="m-0 text-[0.95rem] leading-snug text-sagra-muted">Cucina, griglia, bevande, economico, consegna</p>
        </a>
        <a class="flex min-h-[8.5rem] flex-col gap-1.5 rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:-translate-y-px hover:border-sagra hover:bg-sagra-softer hover:no-underline"
           href="{{ route('gestione.impostazioni', absolute: false) }}">
            <span class="text-[0.72rem] font-extrabold uppercase tracking-wider text-sagra">Sistema</span>
            <h2 class="m-0 text-[1.35rem] font-bold text-sagra-ink">Impostazioni</h2>
            <p class="m-0 text-[0.95rem] leading-snug text-sagra-muted">Postazioni, punti cassa e PIN gestione</p>
        </a>
    </div>
</div>
