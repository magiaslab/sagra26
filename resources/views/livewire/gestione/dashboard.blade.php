<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Gestione"
        subtitle="Apertura serata, menù, chiusure e report"
    />

    @if ($serata)
        <div class="alert alert-ok">Serata aperta: <strong>{{ $serata->data->format('d/m/Y') }}</strong></div>
    @else
        <div class="alert alert-warn">Nessuna serata aperta.</div>
    @endif

    <div class="home-cards">
        <a class="home-card" href="{{ route('gestione.serate', absolute: false) }}">
            <h2>Serate</h2>
            <p>Apertura, stock e chiusura serata</p>
        </a>
        <a class="home-card" href="{{ route('gestione.menu', absolute: false) }}">
            <h2>Menù</h2>
            <p>Voci, prezzi, stock default, aree stampa</p>
        </a>
        <a class="home-card" href="{{ route('gestione.chiusura', absolute: false) }}">
            <h2>Chiusura cassa</h2>
            <p>Conta pezzi e riconciliazione a tre vie</p>
        </a>
        <a class="home-card" href="{{ route('gestione.report', absolute: false) }}">
            <h2>Report / Stampe</h2>
            <p>Cucina, statistiche, economico, consegna</p>
        </a>
        <a class="home-card" href="{{ route('gestione.impostazioni', absolute: false) }}">
            <h2>Impostazioni</h2>
            <p>Postazioni, punti cassa, PIN</p>
        </a>
    </div>
</div>
