@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="panel" style="text-align:center;padding:2rem">
    <h1 style="font-size:2rem;margin-bottom:.25rem">{{ $impostazioni->intestazione_nome }}</h1>
    <p style="font-size:1.4rem;margin:.25rem 0">{{ $impostazioni->intestazione_anno }}</p>
    @if ($impostazioni->intestazione_sottotitolo)
        <p style="color:#555">{{ $impostazioni->intestazione_sottotitolo }}</p>
    @endif
</div>

<div class="home-cards">
    <a class="home-card" href="{{ route('cassa') }}">
        <h2>Cassa</h2>
        <p>Inserimento comande da tastiera e stampa</p>
    </a>
    <a class="home-card" href="{{ route('riepilogo') }}">
        <h2>Riepilogo live</h2>
        <p>Coperti, vendite e incassi in tempo reale</p>
    </a>
    <a class="home-card" href="{{ route('gestione.dashboard') }}">
        <h2>Gestione</h2>
        <p>Serate, menù, chiusura, report (PIN)</p>
    </a>
</div>
@endsection
