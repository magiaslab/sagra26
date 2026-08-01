@extends('layouts.cassa')

@section('title', 'Cassa')

@section('content')
@php
    $menuJson = $menu->values()->toJson(JSON_UNESCAPED_UNICODE);
    $stockJson = json_encode((object) $stock, JSON_UNESCAPED_UNICODE);
    $postazioniJson = $postazioni->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome])->values()->toJson(JSON_UNESCAPED_UNICODE);
@endphp

<div
    class="cassa-app"
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
        <div class="cassa-banner-warn">
            Nessuna serata aperta.
            <a :href="urls.gestione">Apri da Gestione → Serate</a>
        </div>
    @endif

    <header class="cassa-chrome">
        <div class="cassa-chrome-left">
            <label class="cassa-postazione">
                <span class="cassa-postazione-lbl">Postazione</span>
                <select x-model.number="postazioneId" @change="salvaPostazione()" aria-label="Seleziona postazione cassa">
                    <template x-for="p in postazioni" :key="p.id">
                        <option :value="p.id" x-text="p.nome"></option>
                    </template>
                </select>
            </label>
            <span class="cassa-chrome-sep" aria-hidden="true">·</span>
            <span class="cassa-comanda-label">
                COMANDA
                <strong x-text="numeroDisplay"></strong>
                <span class="cassa-edit-badge" x-show="comandaId" x-cloak
                      x-text="correzioniCount > 0 ? ('corr. ×' + correzioniCount) : 'modifica'"></span>
            </span>
        </div>

        <div class="cassa-chrome-center">
            <div class="cassa-chrome-brand" x-text="brand"></div>
            <nav class="cassa-chrome-nav" aria-label="Uscita cassa">
                <a :href="urls.gestione">Gestione</a>
                <span class="cassa-chrome-nav-sep" aria-hidden="true">·</span>
                <a :href="urls.home">Home</a>
            </nav>
        </div>

        <div class="cassa-chrome-right">
            <div class="cassa-kpi">
                <span class="cassa-kpi-lbl">Coperti</span>
                <span class="cassa-kpi-val" x-text="coperti"></span>
            </div>
            <div class="cassa-kpi cassa-kpi-totale">
                <span class="cassa-kpi-lbl">Totale</span>
                <span class="cassa-kpi-val" x-text="formatEuro(totale)"></span>
            </div>
        </div>
    </header>

    <div class="cassa-shortcuts" aria-hidden="true">
        <span class="cassa-shortcuts-item"><kbd>↓</kbd><kbd>Invio</kbd> riga dopo</span>
        <span class="cassa-shortcuts-item"><kbd>↑</kbd> riga prima</span>
        <span class="cassa-shortcuts-item"><kbd>+</kbd><kbd>-</kbd> quantità</span>
        <span class="cassa-shortcuts-item"><kbd>Canc</kbd> azzera</span>
        <span class="cassa-shortcuts-item"><kbd>F9</kbd> conferma</span>
        <span class="cassa-shortcuts-item"><kbd>F2</kbd> richiama</span>
        <span class="cassa-shortcuts-item"><kbd>Esc</kbd> annulla</span>
    </div>

    <div class="cassa-body-panel">
        <div class="cassa-menu-grid" x-ref="menuList">
            <template x-for="group in grouped" :key="group.categoria">
                <section class="cassa-cat">
                    <h2 class="cassa-cat-title" x-text="group.categoria"></h2>
                    <template x-for="item in group.items" :key="item.id">
                        <div
                            class="cassa-row"
                            :class="{
                                active: activeId === item.id,
                                filled: (qty[item.id] || 0) > 0
                            }"
                            :data-id="item.id"
                            @click="setActive(item.id)"
                        >
                            <div class="cassa-row-main">
                                <div class="cassa-row-nome" x-text="item.nome"></div>
                                <div
                                    class="cassa-row-stock"
                                    :class="stockStateClass(item)"
                                    x-show="item.stock_limitato || !serataAperta"
                                    x-text="stockLabel(item)"
                                ></div>
                            </div>
                            <div class="cassa-row-prezzo" x-text="formatEuro(item.prezzo)"></div>
                            <div
                                class="cassa-row-qty"
                                :class="{ blocked: isStockBlocked(item) }"
                                x-text="qty[item.id] || ''"
                            ></div>
                        </div>
                    </template>
                </section>
            </template>
        </div>
    </div>

    <footer class="cassa-footer">
        <div class="cassa-footer-meta">
            <span class="cassa-footer-count" x-text="righeOrdine.length + ' voci · ' + coperti + ' coperti'"></span>
            <div class="cassa-footer-motivo" :class="{ 'is-visible': comandaId }">
                <input class="input input-motivo" type="text" maxlength="255" x-model="motivo"
                       placeholder="Motivo correzione (facoltativo)" autocomplete="off"
                       :tabindex="comandaId ? 0 : -1" :aria-hidden="!comandaId">
            </div>
            <span class="cassa-flash alert-danger" x-show="errore" x-text="errore" x-cloak></span>
            <span class="cassa-flash alert-ok" x-show="messaggio" x-text="messaggio" x-cloak></span>
        </div>
        <div class="cassa-footer-actions">
            <button type="button" class="btn btn-primary btn-cassa-main" @click="apriPagamento()" :disabled="!serataAperta">
                Conferma e stampa <kbd>F9</kbd>
            </button>
            <div class="cassa-footer-actions-secondary">
                <button type="button" class="btn btn-sm" @click="apriRichiamo()">Richiama <kbd>F2</kbd></button>
                <button type="button" class="btn btn-sm" @click="resetComanda()">Annulla <kbd>Esc</kbd></button>
            </div>
        </div>
    </footer>

    {{-- Modal pagamento --}}
    <div class="modal-backdrop" x-show="modalPagamento" x-cloak @keydown.escape.window="chiudiModal()">
        <div class="modal modal-pay" @click.stop>
            <h2>
                COMANDA N.<span x-text="numeroDisplay"></span>
                <span class="modal-pay-tot">· <span x-text="formatEuro(totale)"></span></span>
            </h2>
            <p class="modal-pay-q">Come paga il cliente?</p>
            <div class="pay-choices">
                <button type="button" class="pay-btn pay-contante" @click="scegliMetodo('contante')">
                    <span class="pay-key">C</span>
                    <span class="pay-label">Contante</span>
                </button>
                <button type="button" class="pay-btn pay-pos" @click="scegliMetodo('pos')">
                    <span class="pay-key">P</span>
                    <span class="pay-label">POS</span>
                </button>
            </div>
            <p class="modal-pay-hint">Esc per annullare</p>
        </div>
    </div>

    {{-- Modal anteprima A4 --}}
    <div class="modal-backdrop" x-show="modalAnteprima" x-cloak @keydown.escape.window="chiudiModal()">
        <div class="modal modal-a4" @click.stop>
            <div class="modal-a4-head">
                <h2>Anteprima A4 — 27 cm utili (297 mm meno margini di stampa)</h2>
                <span class="badge" :class="metodo === 'contante' ? 'badge-double' : ''"
                      x-text="metodo === 'contante' ? 'CONTANTE' : 'POS'"></span>
            </div>

            <div class="a4-preview">
                <div class="a4-sheet">
                    <section class="a4-tag a4-cliente">
                        <div class="a4-brand" x-text="brand"></div>
                        <div class="a4-sub" x-text="sottotitolo" x-show="sottotitolo"></div>
                        <div class="a4-head">
                            <span class="a4-role">CLIENTE</span>
                            <span class="a4-num" x-text="'n.' + numeroDisplay"></span>
                        </div>
                        <template x-for="r in righeOrdine" :key="'c-'+r.id">
                            <div class="a4-line">
                                <strong x-text="r.q"></strong>
                                <span x-text="r.nome"></span>
                                <span x-text="formatEuro(r.importo).replace(/\s/g,'')"></span>
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
                                        <span class="a4-box"></span>
                                        <span class="a4-dotted"><strong x-text="r.q"></strong> <span x-text="r.nome"></span></span>
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
                                        <span class="a4-box"></span>
                                        <span class="a4-dotted"><strong x-text="r.q"></strong> <span x-text="r.nome"></span></span>
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
                                    <strong x-text="r.q"></strong>
                                    <span x-text="r.nome"></span>
                                </div>
                            </template>
                            <div class="a4-empty" x-show="righeGriglia.length === 0">—</div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="modal-a4-actions">
                <button class="btn" type="button" @click="chiudiModal()">Annulla (Esc)</button>
                <button class="btn btn-primary" type="button" @click="inviaConferma()" :disabled="busy">
                    Stampa <kbd>Invio</kbd>
                </button>
            </div>
            <div x-show="errore" class="alert alert-danger" style="margin-top:.75rem" x-text="errore"></div>
        </div>
    </div>

    {{-- Modal richiamo --}}
    <div class="modal-backdrop" x-show="modalRichiamo" x-cloak>
        <div class="modal modal-richiamo" @click.stop>
            <h2>Richiama una comanda</h2>
            <p class="richiamo-hint">
                Tocca la riga per correggerla. «Annulla» è un'altra cosa: da usare solo se l'ordine non va più fatto — richiede un motivo e non si torna indietro.
            </p>

            <label class="label">Numero progressivo</label>
            <div class="richiamo-cerca">
                <input class="input" type="number" x-model="richiamoNumero" x-ref="richiamoInput"
                       @keydown.enter.prevent="eseguiRichiamo()" placeholder="Es. 42">
                <button class="btn btn-primary" type="button" @click="eseguiRichiamo()">Carica (Invio)</button>
            </div>

            <h3 class="storico-titolo">Ultime comande</h3>
            <div class="storico-lista" x-show="storico.length === 0">
                <p class="storico-vuoto">Nessuna comanda stampata ancora in questa serata.</p>
            </div>
            <div class="storico-lista storico-lista--righe" x-show="storico.length > 0">
                <template x-for="c in storico" :key="c.comanda_id">
                    <div class="storico-riga" :class="{ 'is-annullata': c.stato === 'annullata', 'is-confirming': annulloId === c.comanda_id }">
                        <template x-if="c.stato === 'annullata'">
                            <div class="storico-annullata">
                                <div class="storico-annullata-top">
                                    <span class="storico-num barrato">n.<span x-text="c.numero"></span></span>
                                    <span class="badge badge-annullata">ANNULLATA</span>
                                </div>
                                <div class="storico-motivo">
                                    Motivo: <span x-text="c.motivo_annullo || '—'"></span>
                                </div>
                            </div>
                        </template>

                        <template x-if="c.stato !== 'annullata'">
                            <div>
                                <div class="storico-azioni">
                                    <button type="button" class="btn-correggi" @click="caricaDaStorico(c)">
                                        <span class="storico-num">n.<span x-text="c.numero"></span></span>
                                        <span class="storico-meta">
                                            <span x-text="c.n_righe + ' voci · ' + c.coperti + ' cop.'"></span>
                                            <span class="badge" x-text="c.metodo_pagamento === 'contante' ? 'CONT' : (c.metodo_pagamento === 'pos' ? 'POS' : 'MISTO')"></span>
                                        </span>
                                        <span class="storico-totale" x-text="formatEuro(c.totale)"></span>
                                        <span class="storico-correggi-label">Correggi →</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-annulla-riga"
                                        @click.stop="apriConfermaAnnullo(c)"
                                        title="Annulla comanda (irreversibile)"
                                    >Annulla</button>
                                </div>
                                <template x-if="annulloId === c.comanda_id">
                                    <div class="annullo-conferma">
                                        <p class="annullo-testata">
                                            Annullare definitivamente la comanda n.<strong x-text="c.numero"></strong>?
                                            Non sarà più possibile richiamarla né correggerla.
                                        </p>
                                        <input class="input input-annullo" type="text" maxlength="255"
                                               x-model="annulloMotivo" x-ref="annulloMotivoInput"
                                               placeholder="Motivo (obbligatorio)"
                                               @keydown.enter.prevent="annulloMotivo.trim().length >= 2 && confermaAnnullo()">
                                        <div class="annullo-bottoni">
                                            <button type="button" class="btn" @click="chiudiConfermaAnnullo()">
                                                Torna indietro
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-conferma-annullo"
                                                :disabled="annulloMotivo.trim().length < 2 || busy"
                                                @click="confermaAnnullo()"
                                            >Conferma annullamento</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div style="display:flex;gap:.5rem;margin-top:1rem;justify-content:flex-end">
                <button class="btn" type="button" @click="chiudiModal()">Chiudi (Esc)</button>
            </div>
            <div x-show="errore" class="alert alert-danger" style="margin-top:1rem" x-text="errore"></div>
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

        stockStateClass(item) {
            if (!this.serataAperta) return 'stato-noserata';
            if (!item?.stock_limitato) return '';
            const r = this.stockResiduo(item);
            if (r === null) return 'stato-mancante';
            if (r <= 0) return 'stato-esaurito';
            return 'stato-ok';
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
