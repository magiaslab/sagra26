@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="mb-6 rounded-lg bg-white px-6 py-8 text-center shadow-sm ring-1 ring-sagra-line/80">
    <h1 class="m-0 text-3xl font-semibold text-sagra-ink">{{ $impostazioni->intestazione_nome }}</h1>
    <p class="my-1 text-xl font-semibold text-sagra-dark">{{ $impostazioni->intestazione_anno }}</p>
    @if ($impostazioni->intestazione_sottotitolo)
        <p class="mb-0 mt-1.5 text-sagra-muted">{{ $impostazioni->intestazione_sottotitolo }}</p>
    @endif
</div>

<div class="divide-y divide-sagra-line overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80 sm:grid sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-3">
    <a class="flex flex-col px-5 py-5 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
       href="{{ route('cassa', absolute: false) }}">
        <h2 class="mb-1.5 mt-0 text-xl font-semibold text-sagra-dark">Cassa</h2>
        <p class="m-0 text-base text-sagra-muted">Inserimento comande da tastiera e stampa</p>
    </a>
    <a class="flex flex-col px-5 py-5 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline"
       href="{{ route('riepilogo', absolute: false) }}">
        <h2 class="mb-1.5 mt-0 text-xl font-semibold text-sagra-dark">Riepilogo live</h2>
        <p class="m-0 text-base text-sagra-muted">Coperti, vendite e incassi in tempo reale</p>
    </a>
    <a class="flex flex-col px-5 py-5 text-sagra-ink no-underline transition hover:bg-sagra-softer hover:no-underline sm:col-span-2 lg:col-span-1"
       href="{{ route('gestione.dashboard', absolute: false) }}">
        <h2 class="mb-1.5 mt-0 text-xl font-semibold text-sagra-dark">Gestione</h2>
        <p class="m-0 text-base text-sagra-muted">Serate, menù, chiusura, report (PIN)</p>
    </a>
</div>
@endsection
