@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="panel home-hero">
    <h1 class="home-hero-title">{{ $impostazioni->intestazione_nome }}</h1>
    <p class="home-hero-year">{{ $impostazioni->intestazione_anno }}</p>
    @if ($impostazioni->intestazione_sottotitolo)
        <p class="home-hero-sub">{{ $impostazioni->intestazione_sottotitolo }}</p>
    @endif
</div>

<div class="home-cards">
    <a class="home-card" href="{{ route('cassa', absolute: false) }}">
        <h2>Cassa</h2>
        <p>Inserimento comande da tastiera e stampa</p>
    </a>
    <a class="home-card" href="{{ route('riepilogo', absolute: false) }}">
        <h2>Riepilogo live</h2>
        <p>Coperti, vendite e incassi in tempo reale</p>
    </a>
    <a class="home-card" href="{{ route('gestione.dashboard', absolute: false) }}">
        <h2>Gestione</h2>
        <p>Serate, menù, chiusura, report (PIN)</p>
    </a>
</div>
@endsection
