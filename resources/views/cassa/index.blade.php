@extends('layouts.cassa')

@section('title', 'Cassa')

@section('content')

@php
    $menuJson = $menu->values()->toJson(JSON_UNESCAPED_UNICODE);
    $stockJson = json_encode((object) $stock, JSON_UNESCAPED_UNICODE);
    $postazioniJson = $postazioni->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome])->values()->toJson(JSON_UNESCAPED_UNICODE);
@endphp

<div
    class="flex h-dvh flex-col overflow-hidden font-sans text-[1.05rem]"
    x-data="cassaApp({
        menu: {{ $menuJson }},
        stock: {{ $stockJson }},
        postazioni: {{ $postazioniJson }},
        postazioneId: {{ (int) $postazioneId }},
        serataAperta: {{ $serata ? 'true' : 'false' }},
        prossimoNumero: {{ (int) $prossimoNumero }},
        brand: @js(($impostazioni->intestazione_nome ?? 'Sagra').' '.($impostazioni->intestazione_anno ?? '')),
        sottotitolo: @js($impostazioni->intestazione_sottotitolo ?? ''),
        csrf: '{{ csrf_token() }}',
        urls: {
            conferma: '{{ route('cassa.conferma', absolute: false) }}',
            stock: '{{ route('cassa.stock', absolute: false) }}',
            richiamo: '/cassa/richiamo',
            storico: '{{ route('cassa.storico', absolute: false) }}',
            annulla: '/cassa/annulla',
            postazione: '{{ route('cassa.postazione', absolute: false) }}',
            gestione: '{{ route('gestione.dashboard', absolute: false) }}',
            home: '{{ route('home', absolute: false) }}'
        }
    })"
    @keydown.window="onKey($event)"
>
    @if (!$serata)
        <div class="bg-sagra-warn-soft px-4 py-2.5 text-sm font-medium text-sagra-warn ring-1 ring-inset ring-sagra-warn/25">
            Nessuna serata aperta.
            <a class="font-semibold underline underline-offset-2" :href="urls.gestione">Apri da Gestione → Serate</a>
        </div>
    @endif

    <header class="border-b border-sagra-dark/20 bg-sagra text-white">
        <div class="flex h-14 items-center gap-4 px-4">
            <div class="flex min-w-0 items-center gap-3">
                <select
                    x-model.number="postazioneId"
                    @change="salvaPostazione()"
                    aria-label="Seleziona postazione cassa"
                    class="h-9 max-w-[10rem] cursor-pointer rounded-md border-0 bg-white/10 px-2.5 text-sm font-medium text-white ring-1 ring-inset ring-white/25 focus:outline-none focus:ring-2 focus:ring-white/50"
                >
                    <template x-for="p in postazioni" :key="p.id">
                        <option :value="p.id" x-text="p.nome" class="text-black"></option>
                    </template>
                </select>
                <div class="flex items-baseline gap-2 whitespace-nowrap text-sm">
                    <span class="font-medium text-white/70">Comanda</span>
                    <span class="text-xl font-semibold tabular-nums" x-text="numeroDisplay"></span>
                    <span class="text-xs font-medium text-white/65"
                          x-show="comandaId" x-cloak
                          x-text="correzioniCount > 0 ? ('corr. ×' + correzioniCount) : 'modifica'"></span>
                </div>
            </div>

            <div class="mx-auto hidden h-full min-w-0 items-stretch gap-6 sm:flex">
                <span class="flex items-center truncate text-sm font-semibold tracking-wide" x-text="brand"></span>
                <nav class="flex items-stretch gap-1" aria-label="Uscita cassa">
                    <a class="inline-flex items-center border-b-2 border-transparent px-2 text-sm font-medium text-white/75 no-underline hover:border-white/40 hover:text-white" :href="urls.gestione">Gestione</a>
                    <a class="inline-flex items-center border-b-2 border-transparent px-2 text-sm font-medium text-white/75 no-underline hover:border-white/40 hover:text-white" :href="urls.home">Home</a>
                </nav>
            </div>

            <div class="ml-auto flex items-center gap-6">
                <div class="text-right">
                    <div class="text-[0.68rem] font-medium uppercase tracking-wide text-white/65">Coperti</div>
                    <div class="text-xl font-semibold tabular-nums leading-none" x-text="coperti"></div>
                </div>
                <div class="text-right">
                    <div class="text-[0.68rem] font-medium uppercase tracking-wide text-white/65">Totale</div>
                    <div class="text-2xl font-semibold tabular-nums leading-none" x-text="formatEuro(totale)"></div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-sagra-line bg-white px-4 py-2">
        <div class="flex min-w-0 flex-wrap items-center gap-x-1 gap-y-1 text-[0.78rem] font-medium text-sagra-muted" aria-hidden="true">
            <span class="inline-flex items-center gap-1 px-2 py-0.5"><kbd>↓</kbd><kbd>Invio</kbd> riga dopo</span>
            <span class="inline-flex items-center gap-1 border-l border-sagra-line px-2 py-0.5"><kbd>↑</kbd> riga prima</span>
            <span class="inline-flex items-center gap-1 border-l border-sagra-line px-2 py-0.5"><kbd>+</kbd><kbd>-</kbd> quantità</span>
            <span class="inline-flex items-center gap-1 border-l border-sagra-line px-2 py-0.5"><kbd>Canc</kbd> azzera</span>
            <span class="inline-flex items-center gap-1 border-l border-sagra-line px-2 py-0.5"><kbd>F9</kbd> conferma</span>
            <span class="inline-flex items-center gap-1 border-l border-sagra-line px-2 py-0.5"><kbd>F2</kbd> richiama</span>
            <span class="inline-flex items-center gap-1 border-l border-sagra-line px-2 py-0.5"><kbd>Esc</kbd> annulla</span>
        </div>
        <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
            <span class="mr-1 whitespace-nowrap text-sm font-medium text-sagra-muted" x-text="righeOrdine.length + ' voci · ' + coperti + ' coperti'"></span>
            <div class="w-44 min-h-9" :class="comandaId ? 'visible' : 'invisible pointer-events-none'">
                <input class="block h-9 w-full rounded-md bg-white px-2.5 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="text" maxlength="255" x-model="motivo"
                       placeholder="Motivo correzione" autocomplete="off"
                       :tabindex="comandaId ? 0 : -1" :aria-hidden="!comandaId">
            </div>
            <button type="button" class="inline-flex h-9 items-center rounded-md bg-sagra px-3.5 text-sm font-semibold text-white hover:bg-sagra-dark disabled:opacity-50" @click="apriPagamento()" :disabled="!serataAperta">
                Conferma e stampa <kbd class="ml-1.5 border-white/40 bg-black/15 text-inherit">F9</kbd>
            </button>
            <button type="button" class="inline-flex h-9 items-center rounded-md bg-white px-3 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" @click="apriRichiamo()">Richiama <kbd class="ml-1">F2</kbd></button>
            <button type="button" class="inline-flex h-9 items-center rounded-md bg-white px-3 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" @click="resetComanda()">Annulla <kbd class="ml-1">Esc</kbd></button>
        </div>
    </div>

    <div class="flex-1 overflow-auto bg-sagra-bg px-4 py-4">
        <div class="mx-auto grid max-w-[1200px] grid-cols-1 content-start gap-x-8 gap-y-5 md:grid-cols-2" x-ref="menuList">
            <template x-for="group in grouped" :key="group.categoria">
                <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
                    <h2 class="border-b border-sagra-line px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-sagra-muted" x-text="group.categoria"></h2>
                    <div class="divide-y divide-sagra-line/70">
                        <template x-for="item in group.items" :key="item.id">
                            <div
                                class="grid min-h-12 cursor-pointer grid-cols-[1fr_auto_3.5rem] items-center gap-3 px-4 py-2.5 transition"
                                :class="{
                                    'bg-sagra-softer': qtyOf(item) > 0 && activeId !== item.id,
                                    'bg-sagra-softer ring-2 ring-inset ring-sagra': activeId === item.id
                                }"
                                :data-id="item.id"
                                @click="setActive(item.id)"
                            >
                                <div class="min-w-0">
                                    <div class="truncate text-[1.05rem] font-semibold text-sagra-ink" x-text="item.nome"></div>
                                    <div
                                        class="mt-0.5 text-xs font-medium"
                                        :class="stockStateClass(item)"
                                        x-show="item.stock_limitato || !serataAperta"
                                        x-text="stockLabel(item)"
                                    ></div>
                                </div>
                                <div class="whitespace-nowrap text-[0.98rem] tabular-nums text-sagra-muted" x-text="formatEuro(item.prezzo)"></div>
                                <div
                                    class="flex h-10 items-center justify-center rounded-md text-center text-lg font-bold tabular-nums ring-1 ring-inset"
                                    :class="qtyBoxClass(item)"
                                    x-text="qtyOf(item) || ''"
                                ></div>
                            </div>
                        </template>
                    </div>
                </section>
            </template>
        </div>
    </div>

    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/45" x-show="modalPagamento" x-cloak @keydown.escape.window="chiudiModal()">
        <div class="w-[min(420px,92vw)] rounded-lg bg-white p-6 text-center shadow-xl ring-1 ring-sagra-line" @click.stop>
            <h2 class="text-lg font-semibold text-sagra-ink">
                Comanda n.<span x-text="numeroDisplay"></span>
                <span class="text-sagra-muted"> · <span x-text="formatEuro(totale)"></span></span>
            </h2>
            <p class="mt-2 text-sm text-sagra-muted">Come paga il cliente?</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <button type="button" class="rounded-md bg-sagra px-3 py-4 text-base font-semibold text-white hover:bg-sagra-dark" @click="scegliMetodo('contante')">
                    <span class="block font-mono text-xs text-white/70">C</span>
                    Contante
                </button>
                <button type="button" class="rounded-md bg-white px-3 py-4 text-base font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" @click="scegliMetodo('pos')">
                    <span class="block font-mono text-xs text-sagra-muted">P</span>
                    POS
                </button>
            </div>
            <p class="mt-4 text-xs text-sagra-muted">Esc per annullare</p>
        </div>
    </div>

    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/45 p-2 sm:p-3" x-show="modalAnteprima" x-cloak @keydown.escape.window="chiudiModal()">
        <div class="flex h-[96vh] w-[min(1100px,98vw)] flex-col overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-sagra-line" @click.stop>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-sagra-line px-4 py-2.5">
                <h2 class="m-0 text-sm font-bold sm:text-base">Anteprima stampa — Invio per confermare</h2>
                <span class="rounded bg-sagra-softer px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-sagra"
                      :class="metodo === 'contante' ? 'ring-2 ring-sagra-ink' : ''"
                      x-text="metodo === 'contante' ? 'CONTANTE' : 'POS'"></span>
            </div>
            <div class="a4-preview min-h-0 flex-1" x-ref="a4Fit">
                <div class="a4-scale" :style="a4ScaleStyle">
                    <div class="a4-sheet" x-ref="a4Sheet">
                        <section class="a4-tag a4-cliente">
                            <div class="a4-brand" x-text="brand"></div>
                            <div class="a4-sub" x-text="sottotitolo" x-show="sottotitolo"></div>
                            <div class="a4-head">
                                <span class="a4-role">CLIENTE</span>
                                <span class="a4-num" x-text="'n.' + numeroDisplay"></span>
                            </div>
                            <div class="a4-line a4-line-head">
                                <span>Q.tà</span>
                                <span>Piatto</span>
                                <span class="a4-importo">Prezzo</span>
                                <span class="a4-importo">Totale</span>
                            </div>
                            <template x-for="r in righeOrdine" :key="'c-'+r.id">
                                <div class="a4-line">
                                    <strong x-text="r.q"></strong>
                                    <span x-text="r.nome"></span>
                                    <span class="a4-importo" x-text="formatEuro(r.prezzo).replace(/\s/g,'')"></span>
                                    <span class="a4-importo" x-text="formatEuro(r.importo).replace(/\s/g,'')"></span>
                                </div>
                            </template>
                            <div class="a4-totale">TOTALE PAGATO <span x-text="formatEuro(totale)"></span></div>
                            <div class="a4-pay" :class="metodo === 'contante' ? 'a4-pay--contante' : 'a4-pay--pos'"
                                 x-text="metodo === 'contante' ? '€ CONTANTE' : '▭ POS'"></div>
                        </section>
                        <div class="a4-right">
                            <div class="a4-top">
                                <section class="a4-tag a4-cucina">
                                    <div class="a4-brand" x-text="brand"></div>
                                    <div class="a4-head">
                                        <span class="a4-role">CUCINA</span>
                                        <span class="a4-num" x-text="'n.' + numeroDisplay"></span>
                                    </div>
                                    <template x-for="r in righeCucina" :key="'k-'+r.id">
                                        <div class="a4-check">
                                            <span class="a4-qty" x-text="r.q"></span>
                                            <span class="a4-dotted" x-text="r.nome"></span>
                                            <span class="a4-box"></span>
                                        </div>
                                    </template>
                                    <div class="a4-empty" x-show="righeCucina.length === 0">—</div>
                                    <div class="a4-mano"><span>Cameriere</span><span class="a4-linea"></span></div>
                                </section>
                                <section class="a4-tag a4-cameriere">
                                    <div class="a4-brand" x-text="brand"></div>
                                    <div class="a4-head">
                                        <span class="a4-role">CAMERIERE</span>
                                        <span class="a4-num" x-text="'n.' + numeroDisplay"></span>
                                    </div>
                                    <div class="a4-mano a4-mano--top"><span>Tavolo</span><span class="a4-linea"></span></div>
                                    <template x-for="r in righeOrdine" :key="'w-'+r.id">
                                        <div class="a4-check">
                                            <span class="a4-qty" x-text="r.q"></span>
                                            <span class="a4-dotted" x-text="r.nome"></span>
                                            <span class="a4-box"></span>
                                        </div>
                                    </template>
                                </section>
                            </div>
                            <section class="a4-tag a4-griglia">
                                <div class="a4-griglia-head">
                                    <div>
                                        <div class="a4-brand" x-text="brand"></div>
                                        <div class="a4-role">GRIGLIA</div>
                                    </div>
                                    <div class="a4-mano a4-mano--inline"><span>Cameriere</span><span class="a4-linea"></span></div>
                                    <span class="a4-num" x-text="'n.' + numeroDisplay"></span>
                                </div>
                                <template x-for="r in righeGriglia" :key="'g-'+r.id">
                                    <div class="a4-line-griglia">
                                        <span class="a4-qty" x-text="r.q"></span>
                                        <span x-text="r.nome"></span>
                                    </div>
                                </template>
                                <div class="a4-empty" x-show="righeGriglia.length === 0">—</div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 justify-end gap-2 border-t border-sagra-line bg-white px-4 py-3">
                <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" type="button" @click="chiudiModal()">Annulla (Esc)</button>
                <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark disabled:opacity-50" type="button" @click="inviaConferma()" :disabled="busy">
                    Conferma e stampa <span class="ml-2 font-mono text-xs text-white/80">Invio</span>
                </button>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/45" x-show="modalRichiamo" x-cloak>
        <div class="max-h-[90vh] w-[min(27rem,94vw)] overflow-auto rounded-lg bg-white p-5 shadow-xl ring-1 ring-sagra-line" @click.stop>
            <h2 class="text-lg font-semibold text-sagra-ink">Richiama una comanda</h2>
            <p class="mb-3 text-xs leading-relaxed text-sagra-muted">
                Tocca la riga per correggerla. «Annulla» è un'altra cosa: da usare solo se l'ordine non va più fatto — richiede un motivo e non si torna indietro.
            </p>
            <label class="mb-1 block text-sm font-medium text-sagra-ink">Numero progressivo</label>
            <div class="mb-2 flex gap-2">
                <input class="block h-10 min-w-0 flex-1 rounded-md bg-white px-3 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" type="number" x-model="richiamoNumero" x-ref="richiamoInput"
                       @keydown.enter.prevent="eseguiRichiamo()" placeholder="Es. 42">
                <button class="inline-flex shrink-0 items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" type="button" @click="eseguiRichiamo()">Carica (Invio)</button>
            </div>
            <h3 class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-sagra-muted">Ultime comande</h3>
            <div class="max-h-[42vh] overflow-auto" x-show="storico.length === 0">
                <p class="rounded-md bg-sagra-bg p-4 text-center text-sm text-sagra-muted ring-1 ring-inset ring-sagra-line/80">Nessuna comanda stampata ancora in questa serata.</p>
            </div>
            <div class="max-h-[42vh] overflow-auto rounded-lg ring-1 ring-sagra-line/80" x-show="storico.length > 0">
                <div class="divide-y divide-sagra-line">
                <template x-for="c in storico" :key="c.comanda_id">
                    <div class="bg-white"
                         :class="{ 'opacity-60': c.stato === 'annullata', 'bg-sagra-danger-soft/40': annulloId === c.comanda_id }">
                        <template x-if="c.stato === 'annullata'">
                            <div class="px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-base font-semibold line-through text-neutral-500">n.<span x-text="c.numero"></span></span>
                                    <span class="text-xs font-medium text-neutral-500">Annullata</span>
                                </div>
                                <div class="mt-1 text-xs text-neutral-500">Motivo: <span x-text="c.motivo_annullo || '—'"></span></div>
                            </div>
                        </template>
                        <template x-if="c.stato !== 'annullata'">
                            <div>
                                <div class="flex items-stretch">
                                    <button type="button" class="flex min-h-10 flex-1 flex-wrap items-center gap-2 bg-white px-3 py-2 text-left hover:bg-sagra-softer" @click="caricaDaStorico(c)">
                                        <span class="font-mono text-base font-semibold">n.<span x-text="c.numero"></span></span>
                                        <span class="flex min-w-0 flex-1 flex-wrap items-center gap-2 text-xs text-sagra-muted">
                                            <span x-text="c.n_righe + ' voci · ' + c.coperti + ' cop.'"></span>
                                            <span class="text-xs font-medium text-sagra" x-text="c.metodo_pagamento === 'contante' ? 'CONT' : (c.metodo_pagamento === 'pos' ? 'POS' : 'MISTO')"></span>
                                        </span>
                                        <span class="font-mono text-sm font-semibold tabular-nums" x-text="formatEuro(c.totale)"></span>
                                        <span class="ml-auto whitespace-nowrap text-xs font-medium text-sagra">Correggi →</span>
                                    </button>
                                    <button type="button" class="min-w-[4.5rem] border-l border-sagra-line bg-white px-3 text-xs font-semibold text-sagra-danger hover:bg-sagra-danger-soft"
                                            @click.stop="apriConfermaAnnullo(c)" title="Annulla comanda (irreversibile)">Annulla</button>
                                </div>
                                <template x-if="annulloId === c.comanda_id">
                                    <div class="border-t border-sagra-danger/30 bg-sagra-danger-soft p-3">
                                        <p class="mb-2 text-xs font-medium leading-snug text-sagra-danger">
                                            Annullare definitivamente la comanda n.<strong x-text="c.numero"></strong>?
                                            Non sarà più possibile richiamarla né correggerla.
                                        </p>
                                        <input class="mb-2 block h-10 w-full rounded-md bg-white px-3 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-danger focus:ring-2 focus:ring-sagra-danger" type="text" maxlength="255"
                                               x-model="annulloMotivo" x-ref="annulloMotivoInput"
                                               placeholder="Motivo (obbligatorio)"
                                               @keydown.enter.prevent="annulloMotivo.trim().length >= 2 && confermaAnnullo()">
                                        <div class="mt-1 flex flex-wrap justify-end gap-2">
                                            <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" @click="chiudiConfermaAnnullo()">Torna indietro</button>
                                            <button type="button" class="inline-flex items-center rounded-md bg-sagra-danger px-3 py-2 text-sm font-semibold text-white hover:bg-red-950"
                                                    :disabled="annulloMotivo.trim().length < 2 || busy"
                                                    @click="confermaAnnullo()">Conferma annullamento</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" type="button" @click="chiudiModal()">Chiudi (Esc)</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('head')
<style>[x-cloak]{display:none!important}</style>
@endpush

@push('scripts')
<script>
function cassaApp(cfg) {
    return {
        menu: cfg.menu,
        stock: cfg.stock || {},
        postazioni: cfg.postazioni || [],
        qty: {},
        activeId: cfg.menu[0]?.id ?? null,
        postazioneId: cfg.postazioneId,
        serataAperta: cfg.serataAperta,
        prossimoNumero: cfg.prossimoNumero || 1,
        brand: cfg.brand,
        sottotitolo: cfg.sottotitolo || '',
        csrf: cfg.csrf,
        urls: cfg.urls,
        modalPagamento: false,
        modalAnteprima: false,
        modalRichiamo: false,
        a4Scale: 1,
        metodo: null,
        comandaId: null,
        numeroRichiamato: null,
        comandaVersion: null,
        correzioniCount: 0,
        motivo: '',
        richiamoNumero: '',
        storico: [],
        annulloId: null,
        annulloMotivo: '',
        errore: null,
        messaggio: null,
        busy: false,
        pollTimer: null,

        get grouped() {
            const map = new Map();
            for (const item of this.menu) {
                if (!map.has(item.categoria)) map.set(item.categoria, []);
                map.get(item.categoria).push(item);
            }
            return [...map.entries()].map(([categoria, items]) => ({ categoria, items }));
        },

        get numeroDisplay() {
            return this.numeroRichiamato ?? this.prossimoNumero;
        },

        get totale() {
            let t = 0;
            for (const item of this.menu) {
                const q = this.qty[item.id] || 0;
                if (q) t += q * item.prezzo;
            }
            return Math.round(t * 100) / 100;
        },

        get coperti() {
            return this.menu
                .filter(i => i.is_coperto)
                .reduce((sum, i) => sum + (this.qty[i.id] || 0), 0);
        },

        get righeOrdine() {
            return this.menu
                .filter(i => (this.qty[i.id] || 0) > 0)
                .map(i => ({
                    id: i.id,
                    nome: i.nome,
                    q: this.qty[i.id],
                    prezzo: i.prezzo,
                    importo: Math.round(this.qty[i.id] * i.prezzo * 100) / 100,
                    area_stampa: i.area_stampa,
                }));
        },

        get righeCucina() {
            return this.righeOrdine.filter(r => r.area_stampa === 'cucina');
        },

        get righeGriglia() {
            return this.righeOrdine.filter(r => r.area_stampa === 'griglia');
        },

        get flatIds() {
            return this.menu.map(i => i.id);
        },

        init() {
            this.pollTimer = setInterval(() => this.pollStock(), 5000);
            this.$nextTick(() => this.scrollActive());
            this.$watch('errore', (v) => { if (v) window.sagraToast?.(v, 'danger'); });
            this.$watch('messaggio', (v) => { if (v) window.sagraToast?.(v, 'ok'); });
            this.$watch('modalAnteprima', (open) => {
                if (open) this.$nextTick(() => this.fitAnteprima());
            });
            this._onResizeAnteprima = () => {
                if (this.modalAnteprima) this.fitAnteprima();
            };
            window.addEventListener('resize', this._onResizeAnteprima);
        },

        get a4ScaleStyle() {
            const s = this.a4Scale || 1;
            return {
                transform: `scale(${s})`,
                width: '270mm',
                height: '190mm',
            };
        },

        fitAnteprima() {
            const box = this.$refs.a4Fit;
            if (!box) return;
            // Foglio A4 landscape utile: 270×190 mm → CSS px a 96dpi
            const sheetW = 270 * (96 / 25.4);
            const sheetH = 190 * (96 / 25.4);
            const pad = 16;
            const sx = (box.clientWidth - pad) / sheetW;
            const sy = (box.clientHeight - pad) / sheetH;
            this.a4Scale = Math.max(0.35, Math.min(sx, sy, 1.15));
        },

        formatEuro(n) {
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0);
        },

        stockResiduo(item) {
            if (!item?.stock_limitato) return null;
            if (!Object.prototype.hasOwnProperty.call(this.stock, item.id)
                && !Object.prototype.hasOwnProperty.call(this.stock, String(item.id))) {
                return null;
            }
            const r = this.stock[item.id] ?? this.stock[String(item.id)];
            return r === undefined || r === null ? null : Number(r);
        },

        isStockBlocked(item) {
            if (!this.serataAperta) return true;
            if (!item?.stock_limitato) return false;
            const r = this.stockResiduo(item);
            return r === null || r <= 0;
        },

        qtyOf(item) {
            if (!item) return 0;
            const q = this.qty[item.id] ?? this.qty[String(item.id)];
            return q ? Number(q) : 0;
        },

        qtyBoxClass(item) {
            const q = this.qtyOf(item);
            const active = this.activeId === item.id;
            if (this.isStockBlocked(item) && q <= 0) {
                return 'bg-neutral-100 text-neutral-400 ring-neutral-200';
            }
            if (q > 0) {
                return active
                    ? 'bg-sagra text-white ring-sagra'
                    : 'bg-sagra-soft text-sagra-dark ring-sagra/40';
            }
            return active
                ? 'bg-white text-sagra-ink ring-sagra'
                : 'bg-sagra-bg text-sagra-ink ring-sagra-line';
        },

        stockStateClass(item) {
            if (!this.serataAperta) return 'text-sagra-warn';
            if (!item?.stock_limitato) return '';
            const r = this.stockResiduo(item);
            if (r === null) return 'text-sagra-warn';
            if (r <= 0) return 'text-sagra-danger';
            return 'text-sagra-muted';
        },

        stockLabel(item) {
            if (!this.serataAperta) {
                return item?.stock_limitato ? 'serata chiusa' : '';
            }
            if (!item?.stock_limitato) return '';
            const r = this.stockResiduo(item);
            if (r === null) return 'stock non impostato';
            if (r <= 0) return 'ESAURITO';
            return 'rimasti ' + r;
        },

        setActive(id) {
            this.activeId = id;
            this.$nextTick(() => this.scrollActive());
        },

        scrollActive() {
            const el = this.$refs.menuList?.querySelector(`[data-id="${this.activeId}"]`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        },

        move(delta) {
            const ids = this.flatIds;
            const idx = ids.indexOf(this.activeId);
            const next = Math.max(0, Math.min(ids.length - 1, idx + delta));
            this.setActive(ids[next]);
        },

        changeQty(delta) {
            const item = this.menu.find(i => i.id === this.activeId);
            if (!item) return;
            if (!this.serataAperta) {
                this.errore = 'Nessuna serata aperta.';
                return;
            }
            let q = (this.qty[item.id] || 0) + delta;
            if (q < 0) q = 0;
            if (item.stock_limitato) {
                const max = this.stockResiduo(item);
                if (max === null) {
                    this.errore = `Stock non impostato per ${item.nome} — riapri/verifica la serata.`;
                    return;
                }
                if (q > max) {
                    this.errore = max <= 0
                        ? `${item.nome} esaurito`
                        : `Stock insufficiente per ${item.nome} (rimasti ${max})`;
                    q = max;
                } else {
                    this.errore = null;
                }
            }
            if (q === 0) {
                const copy = { ...this.qty };
                delete copy[item.id];
                this.qty = copy;
            } else {
                this.qty = { ...this.qty, [item.id]: q };
            }
        },

        azzeraRiga() {
            const copy = { ...this.qty };
            delete copy[this.activeId];
            this.qty = copy;
        },

        resetComanda() {
            this.qty = {};
            this.comandaId = null;
            this.numeroRichiamato = null;
            this.comandaVersion = null;
            this.correzioniCount = 0;
            this.motivo = '';
            this.metodo = null;
            this.errore = null;
            this.messaggio = null;
            this.activeId = this.menu[0]?.id ?? null;
            this.$nextTick(() => this.scrollActive());
        },

        chiudiModal() {
            this.modalPagamento = false;
            this.modalAnteprima = false;
            this.modalRichiamo = false;
            this.annulloId = null;
            this.annulloMotivo = '';
            this.errore = null;
        },

        apriPagamento() {
            if (!this.serataAperta) {
                this.errore = 'Nessuna serata aperta.';
                return;
            }
            if (this.righeOrdine.length === 0) {
                this.errore = 'Comanda vuota.';
                return;
            }
            for (const r of this.righeOrdine) {
                const item = this.menu.find(i => i.id === r.id);
                if (!item?.stock_limitato) continue;
                const max = this.stockResiduo(item);
                if (max === null) {
                    this.errore = `Stock non impostato per ${item.nome}`;
                    return;
                }
                if (r.q > max) {
                    this.errore = `Stock insufficiente per ${item.nome}`;
                    return;
                }
            }
            this.metodo = null;
            this.errore = null;
            this.modalPagamento = true;
        },

        scegliMetodo(m) {
            this.metodo = m;
            this.modalPagamento = false;
            this.modalAnteprima = true;
            this.$nextTick(() => {
                this.fitAnteprima();
                // Secondo passaggio dopo layout definitivo del modal
                requestAnimationFrame(() => this.fitAnteprima());
            });
        },

        async inviaConferma() {
            if (!this.metodo || this.busy) return;
            this.busy = true;
            this.errore = null;
            try {
                const payload = {
                    postazione_id: this.postazioneId,
                    coperti: this.coperti,
                    metodo_pagamento: this.metodo,
                    comanda_id: this.comandaId,
                    motivo: this.comandaId ? (this.motivo || null) : null,
                    righe: this.righeOrdine.map(r => ({
                        menu_item_id: r.id,
                        quantita: r.q,
                    })),
                };
                if (this.comandaId && this.comandaVersion != null) {
                    payload.version = this.comandaVersion;
                }
                const res = await fetch(this.urls.conferma, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (res.status === 409 || data.conflitto) {
                    this.errore = data.error || 'Qualcuno ha già corretto questa comanda nel frattempo';
                    const num = this.numeroRichiamato;
                    this.chiudiModal();
                    this.messaggio = null;
                    if (num) {
                        this.richiamoNumero = String(num);
                        await this.eseguiRichiamo();
                        this.messaggio = 'Comanda ricaricata — controlla lo stato aggiornato prima di riprovare.';
                    }
                    return;
                }
                if (!res.ok) throw new Error(data.error || 'Errore salvataggio');
                if (data.stock) this.stock = data.stock;
                if (data.numero) this.prossimoNumero = data.numero + 1;
                this.chiudiModal();
                // Stessa finestra: non usare window.open/_blank, altrimenti
                // Chrome --app/--kiosk-printing perde la modalità kiosk.
                window.location.href = data.print_url + (data.print_url.includes('?') ? '&' : '?') + 'print=1';
                return;
            } catch (e) {
                this.errore = e.message;
            } finally {
                this.busy = false;
            }
        },

        apriRichiamo() {
            this.richiamoNumero = '';
            this.errore = null;
            this.annulloId = null;
            this.annulloMotivo = '';
            this.modalRichiamo = true;
            this.caricaStorico();
            this.$nextTick(() => this.$refs.richiamoInput?.focus());
        },

        async caricaStorico() {
            try {
                const res = await fetch(this.urls.storico, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.storico = data.comande || [];
            } catch (_) {
                this.storico = [];
            }
        },

        caricaDaStorico(c) {
            if (!c || c.stato === 'annullata') return;
            this.richiamoNumero = String(c.numero);
            this.eseguiRichiamo();
        },

        apriConfermaAnnullo(c) {
            if (!c || c.stato === 'annullata') return;
            this.errore = null;
            this.annulloId = c.comanda_id;
            this.annulloMotivo = '';
            this.$nextTick(() => this.$refs.annulloMotivoInput?.focus());
        },

        chiudiConfermaAnnullo() {
            this.annulloId = null;
            this.annulloMotivo = '';
        },

        async confermaAnnullo() {
            const motivo = (this.annulloMotivo || '').trim();
            if (motivo.length < 2 || !this.annulloId || this.busy) return;
            this.busy = true;
            this.errore = null;
            const id = this.annulloId;
            try {
                const res = await fetch(this.urls.annulla + '/' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ motivo }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Annullamento fallito');
                this.storico = this.storico.map(c =>
                    c.comanda_id === id
                        ? { ...c, stato: 'annullata', motivo_annullo: motivo }
                        : c
                );
                if (this.comandaId === id) {
                    this.resetComanda();
                }
                this.chiudiConfermaAnnullo();
                this.messaggio = 'Comanda annullata.';
            } catch (e) {
                this.errore = e.message;
            } finally {
                this.busy = false;
            }
        },

        async eseguiRichiamo() {
            const n = parseInt(this.richiamoNumero, 10);
            if (!n) { this.errore = 'Numero non valido'; return; }
            try {
                const res = await fetch(this.urls.richiamo + '/' + n, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Non trovata');
                const q = {};
                for (const r of data.righe) q[r.menu_item_id] = r.quantita;
                this.qty = q;
                this.comandaId = data.comanda_id;
                this.numeroRichiamato = data.numero;
                this.comandaVersion = data.version ?? 1;
                this.correzioniCount = data.correzioni_count || 0;
                this.motivo = '';
                this.chiudiModal();
                this.messaggio = 'Caricata comanda #' + data.numero
                    + (this.correzioniCount ? ' (già corretta ' + this.correzioniCount + '×)' : '');
            } catch (e) {
                this.errore = e.message;
            }
        },

        async salvaPostazione() {
            await fetch(this.urls.postazione, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ postazione_id: this.postazioneId }),
            });
        },

        async pollStock() {
            try {
                const res = await fetch(this.urls.stock, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.stock) this.stock = data.stock;
            } catch (_) {}
        },

        onKey(e) {
            const tag = (e.target.tagName || '').toLowerCase();
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select';

            if (this.modalPagamento) {
                if (e.key === 'c' || e.key === 'C') { e.preventDefault(); this.scegliMetodo('contante'); }
                if (e.key === 'p' || e.key === 'P') { e.preventDefault(); this.scegliMetodo('pos'); }
                if (e.key === 'Escape') { e.preventDefault(); this.chiudiModal(); }
                return;
            }
            if (this.modalAnteprima) {
                if (e.key === 'Enter') { e.preventDefault(); this.inviaConferma(); }
                if (e.key === 'Escape') { e.preventDefault(); this.chiudiModal(); }
                return;
            }
            if (this.modalRichiamo) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    if (this.annulloId) this.chiudiConfermaAnnullo();
                    else this.chiudiModal();
                }
                return;
            }

            if (e.key === 'F9') { e.preventDefault(); this.apriPagamento(); return; }
            if (e.key === 'F2') { e.preventDefault(); this.apriRichiamo(); return; }
            if (e.key === 'Escape') { e.preventDefault(); this.resetComanda(); return; }

            if (typing) return;

            if (e.key === 'ArrowDown' || e.key === 'Enter') { e.preventDefault(); this.move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.move(-1); }
            else if (e.key === '+' || e.key === '=') { e.preventDefault(); this.changeQty(1); }
            else if (e.key === '-' || e.key === '_') { e.preventDefault(); this.changeQty(-1); }
            else if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); this.azzeraRiga(); }
            else if (/^[0-9]$/.test(e.key)) {
                e.preventDefault();
                const item = this.menu.find(i => i.id === this.activeId);
                if (!item) return;
                if (!this.serataAperta) {
                    this.errore = 'Nessuna serata aperta.';
                    return;
                }
                const cur = this.qty[item.id] || 0;
                let next = cur * 10 + parseInt(e.key, 10);
                if (item.stock_limitato) {
                    const max = this.stockResiduo(item);
                    if (max === null) {
                        this.errore = `Stock non impostato per ${item.nome} — riapri/verifica la serata.`;
                        return;
                    }
                    next = Math.min(next, max);
                    if (max <= 0) this.errore = `${item.nome} esaurito`;
                }
                this.qty = { ...this.qty, [item.id]: next };
            }
        },
    };
}
</script>
@endpush
