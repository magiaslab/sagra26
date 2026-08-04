@extends('layouts.print')

@section('title', 'Comanda #'.$comanda->numero_progressivo)

@section('content')
@php
    $diff = $diffCorrezione ?? null;
    $isCorrezione = is_array($diff) && ($diff['voci'] ?? []) !== [];

    $zonaDi = function (?string $area): string {
        return match ($area) {
            'cucina_1', 'cucina' => 'cucina_1',
            'cucina_2' => 'cucina_2',
            'griglia' => 'griglia',
            default => 'coperto',
        };
    };

    $etichettaStato = function (array $v): string {
        return match ($v['stato']) {
            'aggiunta' => 'AGGIUNTA',
            'tolta' => 'TOLTA',
            'aumentata' => '+'.$v['delta_q'],
            'ridotta' => $v['delta_q'].' (era '.($v['quantita'] - $v['delta_q']).')',
            default => 'già ok',
        };
    };

    $classeVoce = function (array $v): string {
        return match ($v['stato']) {
            'aggiunta', 'aumentata' => 'tag-voce--aggiunta',
            'tolta' => 'tag-voce--tolta',
            'ridotta' => 'tag-voce--ridotta',
            default => 'tag-voce--invariata',
        };
    };

    if ($isCorrezione) {
        $vociStampa = collect($diff['voci']);
    } else {
        $vociStampa = $righe->map(fn ($r) => [
            'menu_item_id' => $r['menu_item_id'] ?? 0,
            'nome' => $r['nome'],
            'quantita' => $r['quantita'],
            'stato' => 'normale',
            'delta_q' => 0,
            'prezzo_unitario' => $r['prezzo_unitario'],
            'importo' => $r['importo'],
            'area_stampa' => $r['area_stampa'] ?? null,
            'congelato' => ! empty($r['congelato']),
        ]);
    }

    $filtraZona = fn ($key) => $vociStampa->filter(
        fn ($v) => $zonaDi($v['area_stampa'] ?? null) === $key
    );

    $cucina1 = $filtraZona('cucina_1');
    $cucina2 = $filtraZona('cucina_2');
    $griglia = $filtraZona('griglia');
    $coperto = $filtraZona('coperto');
    $haCongelati = $vociStampa->contains(fn ($v) => ! empty($v['congelato']) && ($v['stato'] ?? '') !== 'tolta');
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

    $deltaImporto = $isCorrezione ? (float) $diff['delta_importo'] : 0.0;
@endphp

@unless ($autoPrint)
<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="{{ route('cassa', absolute: false) }}">Torna alla cassa</a>
    <p>Comanda #{{ $num }} — {{ number_format($comanda->totale, 2, ',', '.') }} €</p>
</div>
@endunless

<div class="print-sheet {{ $isCorrezione ? 'print-sheet--correzione' : '' }}">
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

        @if ($isCorrezione)
            <div class="tag-corr-banner">CORREZIONE — già in corso · non è una comanda nuova</div>
        @endif

        <div class="tag-body">
            <div class="tag-line tag-line-head">
                <span>Q.tà</span>
                <span>Piatto</span>
                <span class="tag-importo">Prezzo</span>
                <span class="tag-importo">Totale</span>
            </div>
            @foreach ($vociStampa as $v)
                @php
                    $stato = $v['stato'] ?? 'normale';
                    $isMod = in_array($stato, ['aggiunta', 'tolta', 'aumentata', 'ridotta', 'invariata'], true);
                    $importo = $stato === 'tolta'
                        ? round($v['quantita'] * $v['prezzo_unitario'], 2)
                        : round(($stato === 'normale' ? ($v['importo'] ?? $v['quantita'] * $v['prezzo_unitario']) : $v['quantita'] * $v['prezzo_unitario']), 2);
                @endphp
                <div class="tag-line {{ $isMod ? $classeVoce($v) : '' }}">
                    <strong>{{ $v['quantita'] }}</strong>
                    <span>
                        {{ $v['nome'] }}@if (! empty($v['congelato']) && $stato !== 'tolta') *@endif
                        @if ($isMod && $stato !== 'invariata')
                            <em class="tag-voce-lbl">{{ $etichettaStato($v) }}</em>
                        @elseif ($stato === 'invariata')
                            <em class="tag-voce-lbl">già ok</em>
                        @endif
                    </span>
                    <span class="tag-importo">{{ number_format($v['prezzo_unitario'], 2, ',', '.') }}</span>
                    <span class="tag-importo">{{ number_format($importo, 2, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        @if ($isCorrezione)
            <div class="tag-corr-riepilogo">
                @if ($deltaImporto > 0)
                    Da chiedere {{ number_format($deltaImporto, 2, ',', '.') }} €
                @elseif ($deltaImporto < 0)
                    Da restituire {{ number_format(abs($deltaImporto), 2, ',', '.') }} €
                @else
                    Nessuna differenza di cassa
                @endif
                <span class="tag-corr-riepilogo-sub">
                    prima {{ number_format($diff['totale_precedente'], 2, ',', '.') }} € → ora {{ number_format($diff['totale_attuale'], 2, ',', '.') }} €
                    @if (filled($diff['motivo'] ?? null))
                        · {{ $diff['motivo'] }}
                    @endif
                </span>
            </div>
        @endif

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
                    <span class="tag-role">{{ $zona['label'] }}{{ $isCorrezione ? ' · CORR.' : '' }}</span>
                    <span class="tag-num">comanda num. #{{ $num }}</span>
                </div>
                @if ($isCorrezione)
                    <div class="tag-corr-hint">Barrato = già in corso · in evidenza = modifica</div>
                @endif
                <div class="tag-body">
                    @forelse ($zona['righe'] as $v)
                        @php $stato = $v['stato'] ?? 'normale'; @endphp
                        <div class="tag-line-check {{ $stato !== 'normale' ? $classeVoce($v) : '' }}">
                            <span class="tag-qty">
                                @if ($stato === 'aumentata')
                                    +{{ $v['delta_q'] }}
                                @elseif ($stato === 'ridotta')
                                    {{ $v['delta_q'] }}
                                @elseif ($stato === 'tolta')
                                    −{{ $v['quantita'] }}
                                @else
                                    {{ $v['quantita'] }}
                                @endif
                            </span>
                            <span class="dotted">
                                {{ $v['nome'] }}
                                @if ($stato === 'aggiunta') <em class="tag-voce-lbl">AGGIUNTA</em>
                                @elseif ($stato === 'tolta') <em class="tag-voce-lbl">TOLTA</em>
                                @elseif ($stato === 'aumentata') <em class="tag-voce-lbl">ora {{ $v['quantita'] }}</em>
                                @elseif ($stato === 'ridotta') <em class="tag-voce-lbl">ora {{ $v['quantita'] }}</em>
                                @elseif ($stato === 'invariata') <em class="tag-voce-lbl">già ok</em>
                                @endif
                            </span>
                            @if (! in_array($stato, ['invariata', 'tolta'], true))
                                <span class="check-box" aria-hidden="true"></span>
                            @else
                                <span class="check-box check-box--muted" aria-hidden="true"></span>
                            @endif
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
            <span class="tag-role">CAMERIERE{{ $isCorrezione ? ' · CORR.' : '' }}</span>
            <span class="tag-num">comanda num. #{{ $num }}</span>
        </div>
        @if ($isCorrezione)
            <div class="tag-corr-hint">Barrato = già in corso · in evidenza = modifica</div>
        @endif
        <div class="tag-body">
            @foreach ($zoneCameriere as $zona)
                <div class="tag-cameriere-zona" data-zona-cameriere="{{ $zona['key'] }}">
                    <div class="tag-cameriere-zona-lbl">{{ $zona['label'] }}</div>
                    @forelse ($zona['righe'] as $v)
                        @php $stato = $v['stato'] ?? 'normale'; @endphp
                        <div class="tag-line-check {{ $stato !== 'normale' ? $classeVoce($v) : '' }}">
                            <span class="tag-qty">
                                @if ($stato === 'aumentata')
                                    +{{ $v['delta_q'] }}
                                @elseif ($stato === 'ridotta')
                                    {{ $v['delta_q'] }}
                                @elseif ($stato === 'tolta')
                                    −{{ $v['quantita'] }}
                                @else
                                    {{ $v['quantita'] }}
                                @endif
                            </span>
                            <span class="dotted">
                                {{ $v['nome'] }}
                                @if ($stato === 'aggiunta') <em class="tag-voce-lbl">AGGIUNTA</em>
                                @elseif ($stato === 'tolta') <em class="tag-voce-lbl">TOLTA</em>
                                @elseif ($stato === 'aumentata') <em class="tag-voce-lbl">ora {{ $v['quantita'] }}</em>
                                @elseif ($stato === 'ridotta') <em class="tag-voce-lbl">ora {{ $v['quantita'] }}</em>
                                @elseif ($stato === 'invariata') <em class="tag-voce-lbl">già ok</em>
                                @endif
                            </span>
                            @if (! in_array($stato, ['invariata', 'tolta'], true))
                                <span class="check-box" aria-hidden="true"></span>
                            @else
                                <span class="check-box check-box--muted" aria-hidden="true"></span>
                            @endif
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
