@extends('layouts.print')

@section('title', 'Comanda #'.$comanda->numero_progressivo)

@section('content')
@php
    $tutte = $righe;
    $cucina = $righe->filter(fn ($r) => $r['area_stampa'] === 'cucina');
    $griglia = $righe->filter(fn ($r) => $r['area_stampa'] === 'griglia');
    $metodo = $comanda->metodo_pagamento;
    $nome = $impostazioni->intestazione_nome;
    $anno = $impostazioni->intestazione_anno;
    $sottotitolo = $impostazioni->intestazione_sottotitolo;
    $num = $comanda->numero_progressivo;
@endphp

@unless ($autoPrint)
<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="{{ route('cassa', absolute: false) }}">Torna alla cassa</a>
    <p>Comanda #{{ $num }} — {{ number_format($comanda->totale, 2, ',', '.') }} €</p>
</div>
@endunless

<div class="print-sheet">
    {{-- 1. CLIENTE: colonna sinistra intera --}}
    <section class="tag-cliente">
        <div class="tag-brand">{{ $nome }} {{ $anno }}</div>
        @if ($sottotitolo)
            <div class="tag-sub">{{ $sottotitolo }}</div>
        @endif
        <div class="tag-head">
            <span class="tag-role">CLIENTE</span>
            <span class="tag-num">n.{{ $num }}</span>
        </div>
        <div class="meta-small">{{ $comanda->serata->data->format('d/m/Y') }} · {{ $comanda->created_at->format('H:i') }}</div>

        <div class="tag-body">
            @foreach ($tutte as $r)
                <div class="tag-line">
                    <strong>{{ $r['quantita'] }}</strong>
                    <span>{{ $r['nome'] }}</span>
                    <span class="tag-importo">{{ number_format($r['importo'], 2, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="totale-print">
            TOTALE PAGATO {{ number_format($comanda->totale, 2, ',', '.') }} €
        </div>
        <div class="pay-badge pay-badge--{{ $metodo }}">
            @if ($metodo === 'contante')
                € CONTANTE
            @elseif ($metodo === 'pos')
                ▭ POS
            @else
                MISTO
            @endif
        </div>
    </section>

    <div class="tag-right">
        <div class="tag-top">
            {{-- 2. CUCINA --}}
            <section class="tag-cucina">
                <div class="tag-brand">{{ $nome }} {{ $anno }}</div>
                <div class="tag-head">
                    <span class="tag-role">CUCINA</span>
                    <span class="tag-num">n.{{ $num }}</span>
                </div>
                <div class="tag-body">
                    @forelse ($cucina as $r)
                        <div class="tag-line-check">
                            <span class="check-box" aria-hidden="true"></span>
                            <span class="dotted"><strong>{{ $r['quantita'] }}</strong> {{ $r['nome'] }}</span>
                        </div>
                    @empty
                        <div class="meta-small">— nessuna voce —</div>
                    @endforelse
                </div>
                <div class="campo-mano campo-mano--full">
                    <span class="campo-mano-lbl">Cameriere</span>
                    <span class="campo-mano-linea"></span>
                </div>
            </section>

            {{-- 3. CAMERIERE --}}
            <section class="tag-cameriere">
                <div class="tag-brand">{{ $nome }} {{ $anno }}</div>
                <div class="tag-head">
                    <span class="tag-role">CAMERIERE</span>
                    <span class="tag-num">n.{{ $num }}</span>
                </div>
                <div class="campo-mano campo-mano--full campo-mano--top">
                    <span class="campo-mano-lbl">Tavolo</span>
                    <span class="campo-mano-linea"></span>
                </div>
                <div class="tag-body">
                    @foreach ($tutte as $r)
                        <div class="tag-line-check">
                            <span class="check-box" aria-hidden="true"></span>
                            <span class="dotted"><strong>{{ $r['quantita'] }}</strong> {{ $r['nome'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- 4. GRIGLIA: sotto Cucina+Cameriere, senza checkbox --}}
        <section class="tag-griglia">
            <div class="tag-griglia-head">
                <div class="tag-griglia-brand">
                    <div class="tag-brand">{{ $nome }} {{ $anno }}</div>
                    <span class="tag-role">GRIGLIA</span>
                </div>
                <div class="campo-mano campo-mano--full campo-mano--inline">
                    <span class="campo-mano-lbl">Cameriere</span>
                    <span class="campo-mano-linea"></span>
                </div>
                <span class="tag-num">n.{{ $num }}</span>
            </div>
            <div class="tag-body tag-body--griglia">
                @forelse ($griglia as $r)
                    <div class="tag-line-griglia">
                        <strong>{{ $r['quantita'] }}</strong>
                        <span>{{ $r['nome'] }}</span>
                    </div>
                @empty
                    <div class="meta-small">— nessuna voce —</div>
                @endforelse
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
window.__autoPrint = @json((bool) $autoPrint);

let giaStampato = false;

function avviaStampaAutomatica() {
    if (giaStampato) return;
    giaStampato = true;
    window.print();
}

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.replace('/cassa');
        return;
    }
    if (window.__autoPrint) {
        avviaStampaAutomatica();
    }
});

window.addEventListener('afterprint', () => {
    window.location.replace('/cassa');
});
</script>
@endpush
@endsection
