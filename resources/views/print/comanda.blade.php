@extends('layouts.print')

@section('title', 'Comanda #'.$comanda->numero_progressivo)

@section('content')
@php
    $tutte = $righe;
    $zonaDi = function (?string $area): string {
        return match ($area) {
            'cucina_1', 'cucina' => 'cucina_1',
            'cucina_2' => 'cucina_2',
            'griglia' => 'griglia',
            default => 'coperto', // cliente e altro → coperto / sala
        };
    };

    $cucina1 = $righe->filter(fn ($r) => $zonaDi($r['area_stampa'] ?? null) === 'cucina_1');
    $cucina2 = $righe->filter(fn ($r) => $zonaDi($r['area_stampa'] ?? null) === 'cucina_2');
    $griglia = $righe->filter(fn ($r) => $zonaDi($r['area_stampa'] ?? null) === 'griglia');
    $coperto = $righe->filter(fn ($r) => $zonaDi($r['area_stampa'] ?? null) === 'coperto');
    $haCongelati = $righe->contains(fn ($r) => ! empty($r['congelato']));
    $metodo = $comanda->metodo_pagamento;
    $nome = $impostazioni->intestazione_nome;
    $anno = $impostazioni->intestazione_anno;
    $sottotitolo = $impostazioni->intestazione_sottotitolo;
    $num = $comanda->numero_progressivo;
    $numSerata = $numeroDiSerata ?? $comanda->numeroDiSerata();

    $zoneProduzione = [
        ['key' => 'cucina_1', 'label' => 'CUCINA 1', 'righe' => $cucina1],
        ['key' => 'cucina_2', 'label' => 'CUCINA 2', 'righe' => $cucina2],
        ['key' => 'griglia', 'label' => 'GRIGLIA', 'righe' => $griglia],
    ];

    $zoneCameriere = [
        ['key' => 'coperto', 'label' => 'COPERTO', 'righe' => $coperto],
        ['key' => 'cucina_1', 'label' => 'CUCINA 1', 'righe' => $cucina1],
        ['key' => 'cucina_2', 'label' => 'CUCINA 2', 'righe' => $cucina2],
        ['key' => 'griglia', 'label' => 'GRIGLIA', 'righe' => $griglia],
    ];
@endphp

@unless ($autoPrint)
<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="{{ route('cassa', absolute: false) }}">Torna alla cassa</a>
    <p>Comanda #{{ $num }} — {{ number_format($comanda->totale, 2, ',', '.') }} €</p>
</div>
@endunless

<div class="print-sheet">
    {{-- Foglio preforato A4 landscape: 3 terzi uguali (Cliente | Produzione | Cameriere) --}}
    <section class="tag-cliente">
        <div class="tag-brand">{{ $nome }} {{ $anno }}</div>
        @if ($sottotitolo)
            <div class="tag-sub">{{ $sottotitolo }}</div>
        @endif
        <div class="tag-head">
            <span class="tag-role">CLIENTE</span>
            <span class="tag-num">Comanda {{ $numSerata }} di stasera</span>
        </div>
        <div class="meta-small">rif. #{{ $num }} · {{ $comanda->serata->data->format('d/m/Y') }} · {{ $comanda->created_at->format('H:i') }}</div>

        <div class="tag-body">
            <div class="tag-line tag-line-head">
                <span>Q.tà</span>
                <span>Piatto</span>
                <span class="tag-importo">Prezzo</span>
                <span class="tag-importo">Totale</span>
            </div>
            @foreach ($tutte as $r)
                <div class="tag-line">
                    <strong>{{ $r['quantita'] }}</strong>
                    <span>{{ $r['nome'] }}@if (! empty($r['congelato'])) *@endif</span>
                    <span class="tag-importo">{{ number_format($r['prezzo_unitario'], 2, ',', '.') }}</span>
                    <span class="tag-importo">{{ number_format($r['importo'], 2, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        @if (filled($impostazioni->comunicazione_comanda))
            <div class="tag-comunicazione">{{ $impostazioni->comunicazione_comanda }}</div>
        @endif

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
        @if ($haCongelati)
            <div class="tag-nota-congelato">* Prodotto surgelato o congelato all'origine.</div>
        @endif
    </section>

    <div class="tag-produzione">
        @foreach ($zoneProduzione as $zona)
            <section class="tag-box-zona" data-zona="{{ $zona['key'] }}">
                <div class="tag-box-head">
                    <span class="tag-role">{{ $zona['label'] }}</span>
                    <span class="tag-num">comanda num. #{{ $num }}</span>
                </div>
                <div class="tag-body">
                    @forelse ($zona['righe'] as $r)
                        <div class="tag-line-check">
                            <span class="tag-qty">{{ $r['quantita'] }}</span>
                            <span class="dotted">{{ $r['nome'] }}</span>
                            <span class="check-box" aria-hidden="true"></span>
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
        @endforeach
    </div>

    <section class="tag-cameriere">
        <div class="tag-head tag-head--compact">
            <span class="tag-role">CAMERIERE</span>
            <span class="tag-num">comanda num. #{{ $num }}</span>
        </div>
        <div class="tag-body">
            @foreach ($zoneCameriere as $zona)
                <div class="tag-cameriere-zona" data-zona-cameriere="{{ $zona['key'] }}">
                    <div class="tag-cameriere-zona-lbl">{{ $zona['label'] }}</div>
                    @forelse ($zona['righe'] as $r)
                        <div class="tag-line-check">
                            <span class="tag-qty">{{ $r['quantita'] }}</span>
                            <span class="dotted">{{ $r['nome'] }}</span>
                            <span class="check-box" aria-hidden="true"></span>
                        </div>
                    @empty
                        <div class="meta-small">—</div>
                    @endforelse
                </div>
            @endforeach
        </div>
        <div class="campo-mano campo-mano--full">
            <span class="campo-mano-lbl">Tavolo</span>
            <span class="campo-mano-linea"></span>
        </div>
    </section>
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
