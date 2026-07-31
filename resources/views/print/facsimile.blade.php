@extends('layouts.print')

@section('title', 'Facsimile menù')

@section('content')
<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="{{ route('gestione.menu') }}">Torna al menù</a>
</div>

<div class="facsimile-sheet">
    @foreach ([1, 2] as $half)
        <section class="facsimile-half">
            <header class="facsimile-head">
                <div class="facsimile-title">{{ $impostazioni->intestazione_nome }}</div>
                <div class="facsimile-anno">{{ $impostazioni->intestazione_anno }}</div>
                @if ($impostazioni->intestazione_sottotitolo)
                    <div class="facsimile-sub">{{ $impostazioni->intestazione_sottotitolo }}</div>
                @endif
                <div class="facsimile-hint">Compila le quantità e consegna in cassa</div>
            </header>

            @foreach ($categorie as $cat)
                <div class="facsimile-cat">{{ $cat->nome }}</div>
                @foreach ($cat->menuItems as $item)
                    <div class="facsimile-row">
                        <span class="facsimile-qty"></span>
                        <span class="facsimile-nome">{{ $item->nome }}</span>
                        <span class="facsimile-prezzo">{{ number_format($item->prezzo, 2, ',', '.') }} €</span>
                    </div>
                @endforeach
            @endforeach
        </section>
        @if ($half === 1)
            <div class="facsimile-cut" aria-hidden="true"></div>
        @endif
    @endforeach
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
