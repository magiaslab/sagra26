@extends('layouts.print')

@section('title', 'Facsimile menù')

@section('content')
@php
    $haCongelati = $categorie->contains(
        fn ($cat) => $cat->menuItems->contains(fn ($item) => (bool) $item->congelato)
    );
@endphp
@unless ($autoPrint)
<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="{{ route('gestione.menu', absolute: false) }}">Torna al menù</a>
</div>
@endunless

<div class="facsimile-sheet" data-print-root>
    @foreach ([1, 2] as $half)
        <section class="facsimile-half">
            <header class="facsimile-head">
                <div class="facsimile-title">
                    {{ $impostazioni->intestazione_nome }}
                    @if ($impostazioni->intestazione_anno)
                        <span class="facsimile-anno">{{ $impostazioni->intestazione_anno }}</span>
                    @endif
                </div>
                @if ($impostazioni->intestazione_sottotitolo)
                    <div class="facsimile-sub">{{ $impostazioni->intestazione_sottotitolo }}</div>
                @endif
                <div class="facsimile-hint">Compila le quantità e consegna in cassa</div>
            </header>

            <div class="facsimile-body">
                @foreach ($categorie as $cat)
                    <div class="facsimile-cat">{{ $cat->nome }}</div>
                    @foreach ($cat->menuItems as $item)
                        <div class="facsimile-row">
                            <span class="facsimile-qty"></span>
                            <span class="facsimile-nome">{{ $item->nome }}@if ($item->congelato) *@endif</span>
                            <span class="facsimile-prezzo">{{ number_format($item->prezzo, 2, ',', '.') }} €</span>
                        </div>
                    @endforeach
                @endforeach
            </div>

            @if ($haCongelati)
                <div class="facsimile-nota-congelato">* Prodotto surgelato o congelato all'origine.</div>
            @endif
        </section>
        @if ($half === 1)
            <div class="facsimile-cut" aria-hidden="true"></div>
        @endif
    @endforeach
</div>

@push('scripts')
<script>
window.__autoPrint = @json((bool) $autoPrint);

let giaStampato = false;

function preparaStampaUnaPagina() {
    document.documentElement.classList.add('is-printing');
    document.body.classList.add('is-printing');
    const sheet = document.querySelector('[data-print-root]');
    if (!sheet) return;
    sheet.style.position = 'fixed';
    sheet.style.left = '0';
    sheet.style.top = '0';
    sheet.style.right = '0';
    sheet.style.bottom = '0';
    sheet.style.width = '100%';
    sheet.style.height = '100%';
    sheet.style.margin = '0';
    sheet.style.overflow = 'hidden';
}

function avviaStampaAutomatica() {
    if (giaStampato) return;
    giaStampato = true;
    preparaStampaUnaPagina();
    requestAnimationFrame(() => window.print());
}

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.replace('/gestione/menu');
        return;
    }
    if (window.__autoPrint) {
        avviaStampaAutomatica();
    }
});

window.addEventListener('beforeprint', preparaStampaUnaPagina);

window.addEventListener('afterprint', () => {
    window.location.replace('/gestione/menu');
});
</script>
@endpush
@endsection
