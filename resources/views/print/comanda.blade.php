@extends('layouts.print')

@section('title', 'Comanda #'.$comanda->numero_progressivo)

@section('content')
@php
    $tutte = $righe;
    $cucina = $righe->filter(fn ($r) => $r['area_stampa'] === 'cucina');
    $griglia = $righe->filter(fn ($r) => $r['area_stampa'] === 'griglia');
    $metodo = $comanda->metodo_pagamento;
@endphp

<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="{{ route('cassa', absolute: false) }}">Torna alla cassa</a>
    <p>Comanda #{{ $comanda->numero_progressivo }} — {{ number_format($comanda->totale, 2, ',', '.') }} €</p>
</div>

<div class="print-sheet">
    {{-- CLIENTE --}}
    <section class="tag-cliente">
        <div class="tag-head">
            <div>
                <div>{{ $impostazioni->intestazione_nome }} {{ $impostazioni->intestazione_anno }}</div>
                <div class="meta-small">CLIENTE</div>
            </div>
            <div class="tag-num">#{{ $comanda->numero_progressivo }}</div>
        </div>
        <div class="meta-small">{{ $comanda->serata->data->format('d/m/Y') }} · {{ $comanda->created_at->format('H:i') }}</div>

        @foreach ($tutte as $r)
            <div class="tag-line">
                <strong>{{ $r['quantita'] }}</strong>
                <span>{{ $r['nome'] }}</span>
                <span>{{ number_format($r['importo'], 2, ',', '.') }}</span>
            </div>
        @endforeach

        <div class="totale-print">
            TOTALE PAGATO: {{ number_format($comanda->totale, 2, ',', '.') }} €
        </div>
        <div class="pay-badge {{ $metodo }}">
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
            {{-- CUCINA --}}
            <section class="tag-cucina">
                <div class="tag-head">
                    <span>CUCINA</span>
                    <span class="tag-num">#{{ $comanda->numero_progressivo }}</span>
                </div>
                @forelse ($cucina as $r)
                    <div class="tag-line-check">
                        <span class="check-box"></span>
                        <span class="dotted"><strong>{{ $r['quantita'] }}</strong> {{ $r['nome'] }}</span>
                    </div>
                @empty
                    <div class="meta-small">— nessuna voce —</div>
                @endforelse
                <div class="campo-mano">
                    Cameriere
                    <div class="linea"></div>
                </div>
            </section>

            {{-- CAMERIERE --}}
            <section class="tag-cameriere">
                <div class="tag-head">
                    <span>CAMERIERE</span>
                    <span class="tag-num">#{{ $comanda->numero_progressivo }}</span>
                </div>
                <div class="campo-mano" style="margin-top:0;border-top:0;padding-top:0">
                    Tavolo
                    <div class="linea"></div>
                </div>
                @foreach ($tutte as $r)
                    <div class="tag-line-check">
                        <span class="check-box"></span>
                        <span class="dotted"><strong>{{ $r['quantita'] }}</strong> {{ $r['nome'] }}</span>
                    </div>
                @endforeach
            </section>
        </div>

        {{-- GRIGLIA --}}
        <section class="tag-griglia">
            <div class="tag-head">
                <span>GRIGLIA</span>
                <span style="flex:1;margin:0 4mm;border-bottom:1px solid #000;font-weight:400;font-size:.85em;padding-left:2mm">Cameriere</span>
                <span class="tag-num">#{{ $comanda->numero_progressivo }}</span>
            </div>
            @forelse ($griglia as $r)
                <div class="tag-line-check">
                    <span class="check-box"></span>
                    <span class="dotted"><strong>{{ $r['quantita'] }}</strong> {{ $r['nome'] }}</span>
                </div>
            @empty
                <div class="meta-small">— nessuna voce —</div>
            @endforelse
        </section>
    </div>
</div>

@if ($autoPrint)
@push('scripts')
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 200);
});
</script>
@endpush
@endif
@endsection
