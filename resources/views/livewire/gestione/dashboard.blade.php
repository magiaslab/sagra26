<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Gestione"
        subtitle="Apertura serata, menù, chiusure e report"
    >
        <x-slot:actions>
            @if ($serata)
                <span class="status-chip status-chip--ok">Serata aperta · {{ $serata->data->format('d/m/Y') }}</span>
            @else
                <span class="status-chip status-chip--warn">Nessuna serata aperta</span>
                <a class="btn btn-primary btn-sm" href="{{ route('gestione.serate', absolute: false) }}">Apri serata</a>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="dash-grid">
        <a class="dash-card" href="{{ route('gestione.serate', absolute: false) }}">
            <span class="dash-card-kicker">Operativo</span>
            <h2>Serate</h2>
            <p>Apertura, stock limitati e chiusura della serata</p>
        </a>
        <a class="dash-card" href="{{ route('gestione.menu', absolute: false) }}">
            <span class="dash-card-kicker">Catalogo</span>
            <h2>Menù</h2>
            <p>Voci, prezzi, stock default e aree stampa</p>
        </a>
        <a class="dash-card" href="{{ route('gestione.chiusura', absolute: false) }}">
            <span class="dash-card-kicker">Fine turno</span>
            <h2>Chiusura cassa</h2>
            <p>Conta pezzi e riconciliazione a tre vie</p>
        </a>
        <a class="dash-card" href="{{ route('gestione.report', absolute: false) }}">
            <span class="dash-card-kicker">Stampe</span>
            <h2>Report</h2>
            <p>Cucina, griglia, bevande, economico, consegna</p>
        </a>
        <a class="dash-card" href="{{ route('gestione.impostazioni', absolute: false) }}">
            <span class="dash-card-kicker">Sistema</span>
            <h2>Impostazioni</h2>
            <p>Postazioni, punti cassa e PIN gestione</p>
        </a>
    </div>
</div>
