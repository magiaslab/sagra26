@extends('layouts.app')

@section('title', 'Guida operativa')

@section('content')
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Guida operativa cassa"
        subtitle="Istruzioni passo-passo per aprire la serata, usare la cassa e chiudere"
    >
        <x-slot:actions>
            <a class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer print:hidden" href="{{ route('gestione.documenti.guida.download', absolute: false) }}">Scarica file</a>
            <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark print:hidden" type="button" onclick="window.print()">Stampa / PDF</button>
            <a class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer print:hidden" href="{{ route('gestione.dashboard', absolute: false) }}">Torna alla dashboard</a>
        </x-slot:actions>
    </x-gestione.page-header>

    <article class="guida-md rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 sm:p-8">
        {!! $html !!}
    </article>
@endsection

@push('head')
<style>
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        .guida-md { box-shadow: none !important; border: none !important; padding: 0 !important; }
    }
    .guida-md h1 {
        margin: 0 0 0.75rem;
        font-size: 1.6rem;
        font-weight: 700;
        color: #1a1f1c;
    }
    .guida-md h2 {
        margin: 1.75rem 0 0.6rem;
        border-bottom: 1px solid #c5cdc8;
        padding-bottom: 0.35rem;
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a1f1c;
    }
    .guida-md h3 {
        margin: 1.25rem 0 0.4rem;
        font-size: 1.05rem;
        font-weight: 600;
        color: #1a1f1c;
    }
    .guida-md p, .guida-md li {
        font-size: 0.95rem;
        line-height: 1.55;
        color: #1a1f1c;
    }
    .guida-md p { margin: 0.5rem 0; }
    .guida-md ul, .guida-md ol { margin: 0.5rem 0 0.75rem 1.25rem; }
    .guida-md li { margin: 0.25rem 0; }
    .guida-md table {
        width: 100%;
        margin: 0.75rem 0 1rem;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .guida-md th, .guida-md td {
        border: 1px solid #c5cdc8;
        padding: 0.45rem 0.6rem;
        text-align: left;
        vertical-align: top;
    }
    .guida-md th { background: #eef6f1; font-weight: 600; }
    .guida-md code, .guida-md pre {
        font-family: ui-monospace, Consolas, monospace;
        font-size: 0.85rem;
        background: #f4f6f4;
        border-radius: 0.25rem;
    }
    .guida-md code { padding: 0.1rem 0.35rem; }
    .guida-md pre {
        margin: 0.75rem 0;
        padding: 0.75rem 1rem;
        overflow-x: auto;
        border: 1px solid #c5cdc8;
    }
    .guida-md pre code { padding: 0; background: transparent; }
    .guida-md blockquote {
        margin: 0.75rem 0;
        border-left: 3px solid #1e5c43;
        padding: 0.35rem 0 0.35rem 0.85rem;
        color: #5a635e;
        background: #eef6f1;
    }
    .guida-md hr {
        margin: 1.5rem 0;
        border: 0;
        border-top: 1px solid #c5cdc8;
    }
    .guida-md a { color: #1e5c43; text-decoration: underline; }
</style>
@endpush
