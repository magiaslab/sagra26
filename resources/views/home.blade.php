@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="panel mb-6 px-6 py-8 text-center">
    <h1 class="m-0 text-3xl font-extrabold text-sagra-ink">{{ $impostazioni->intestazione_nome }}</h1>
    <p class="my-1 text-xl font-bold text-sagra-dark">{{ $impostazioni->intestazione_anno }}</p>
    @if ($impostazioni->intestazione_sottotitolo)
        <p class="mt-1.5 mb-0 text-sagra-muted">{{ $impostazioni->intestazione_sottotitolo }}</p>
    @endif
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <a class="flex min-h-28 flex-col rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:border-sagra hover:bg-sagra-softer hover:no-underline"
       href="{{ route('cassa', absolute: false) }}">
        <h2 class="mb-1.5 mt-0 text-xl font-extrabold text-sagra-dark">Cassa</h2>
        <p class="m-0 text-base text-sagra-muted">Inserimento comande da tastiera e stampa</p>
    </a>
    <a class="flex min-h-28 flex-col rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:border-sagra hover:bg-sagra-softer hover:no-underline"
       href="{{ route('riepilogo', absolute: false) }}">
        <h2 class="mb-1.5 mt-0 text-xl font-extrabold text-sagra-dark">Riepilogo live</h2>
        <p class="m-0 text-base text-sagra-muted">Coperti, vendite e incassi in tempo reale</p>
    </a>
    <a class="flex min-h-28 flex-col rounded-md border border-sagra-line border-l-4 border-l-sagra bg-white px-5 py-5 text-sagra-ink no-underline shadow-sm transition hover:border-sagra hover:bg-sagra-softer hover:no-underline"
       href="{{ route('gestione.dashboard', absolute: false) }}">
        <h2 class="mb-1.5 mt-0 text-xl font-extrabold text-sagra-dark">Gestione</h2>
        <p class="m-0 text-base text-sagra-muted">Serate, menù, chiusura, report (PIN)</p>
    </a>
</div>
@endsection
