@extends('layouts.app')

@section('title', 'Cassa')
@section('main_class', 'cassa-main')

@section('content')
@php
    $menuJson = $menu->values()->toJson(JSON_UNESCAPED_UNICODE);
    $stockJson = json_encode((object) $stock, JSON_UNESCAPED_UNICODE);
@endphp

@if (!$serata)
    <div class="alert alert-warn">
        Nessuna serata aperta. Apri una serata dall'area
        <a href="{{ route('gestione.serate') }}">Gestione → Serate</a>.
    </div>
@endif

<div
    class="cassa-wrap"
    x-data="cassaApp({
        menu: {{ $menuJson }},
        stock: {{ $stockJson }},
        postazioneId: {{ (int) $postazioneId }},
        serataAperta: {{ $serata ? 'true' : 'false' }},
        csrf: '{{ csrf_token() }}',
        urls: {
            conferma: '{{ route('cassa.conferma') }}',
            stock: '{{ route('cassa.stock') }}',
            richiamo: '{{ url('/cassa/richiamo') }}',
            postazione: '{{ route('cassa.postazione') }}'
        }
    })"
    @keydown.window="onKey($event)"
>
    <div class="cassa-menu" x-ref="menuList">
        <template x-for="(group, gIdx) in grouped" :key="group.categoria">
            <div>
                <div class="cat-header" x-text="group.categoria"></div>
                <template x-for="item in group.items" :key="item.id">
                    <div
                        class="cassa-row"
                        :class="{ active: activeId === item.id }"
                        :data-id="item.id"
                        @click="setActive(item.id)"
                    >
                        <div class="qty" x-text="qty[item.id] || ''"></div>
                        <div>
                            <div x-text="item.nome"></div>
                            <div
                                class="stock-info"
                                :class="{ esaurito: item.stock_limitato && (stock[item.id] ?? 0) <= 0 }"
                                x-show="item.stock_limitato"
                                x-text="stockLabel(item)"
                            ></div>
                        </div>
                        <div class="prezzo" x-text="formatEuro(item.prezzo)"></div>
                        <div class="prezzo" x-text="qty[item.id] ? formatEuro(item.prezzo * qty[item.id]) : ''"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <aside class="cassa-side">
        <div class="panel" style="padding:.75rem">
            <label class="label">Postazione</label>
            <select class="input" x-model.number="postazioneId" @change="salvaPostazione()">
                @foreach ($postazioni as $p)
                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                @endforeach
            </select>
        </div>

        <div class="cassa-totale">
            <div class="lbl" style="font-size:.8rem;text-transform:uppercase;color:#555">Totale</div>
            <div class="cifra" x-text="formatEuro(totale)"></div>
            <div style="margin-top:.5rem">
                Coperti:
                <strong x-text="coperti"></strong>
                <span style="color:#555;font-size:.8rem">(voce Coperto)</span>
            </div>
            <div x-show="comandaId" style="margin-top:.4rem;font-size:.85rem">
                Modifica #<strong x-text="numeroRichiamato"></strong>
                <span x-show="correzioniCount > 0" class="badge" style="margin-left:.35rem"
                      x-text="'corretta ' + correzioniCount + (correzioniCount === 1 ? ' volta' : ' volte')"></span>
            </div>
            <div x-show="comandaId" class="field" style="margin-top:.6rem;text-align:left">
                <label class="label">Motivo (facoltativo)</label>
                <input class="input" type="text" maxlength="255" x-model="motivo"
                       placeholder="Motivo (facoltativo)" autocomplete="off">
            </div>
        </div>

        <div class="help-keys">
            <div><kbd>↑</kbd>/<kbd>↓</kbd>/<kbd>Invio</kbd> naviga</div>
            <div><kbd>+</kbd>/<kbd>-</kbd> quantità · <kbd>Canc</kbd> azzera</div>
            <div><kbd>F9</kbd> conferma · <kbd>F2</kbd> richiama</div>
            <div><kbd>Esc</kbd> reset / chiudi</div>
        </div>

        <div x-show="errore" class="alert alert-danger" x-text="errore" style="margin:0"></div>
        <div x-show="messaggio" class="alert alert-ok" x-text="messaggio" style="margin:0"></div>
    </aside>

    {{-- Modal pagamento --}}
    <div class="modal-backdrop" x-show="modalPagamento" x-cloak @keydown.escape.window="chiudiModal()">
        <div class="modal" @click.stop>
            <h2>Metodo di pagamento</h2>
            <p>Totale: <strong x-text="formatEuro(totale)"></strong></p>
            <div class="pay-choices">
                <button type="button" :class="{ 'selected-contante': metodo === 'contante' }" @click="scegliMetodo('contante')">
                    <kbd>C</kbd><br>€ CONTANTE
                </button>
                <button type="button" :class="{ 'selected-pos': metodo === 'pos' }" @click="scegliMetodo('pos')">
                    <kbd>P</kbd><br>▭ POS
                </button>
            </div>
            <p style="font-size:.85rem;color:#555">Premi <kbd>C</kbd> o <kbd>P</kbd>, poi anteprima.</p>
            <button class="btn" type="button" @click="chiudiModal()">Annulla (Esc)</button>
        </div>
    </div>

    {{-- Modal anteprima --}}
    <div class="modal-backdrop" x-show="modalAnteprima" x-cloak @keydown.escape.window="chiudiModal()">
        <div class="modal" style="min-width:min(640px,94vw)" @click.stop>
            <h2>Anteprima stampa — Invio conferma</h2>
            <p>
                Metodo:
                <span class="badge" :class="metodo === 'contante' ? 'badge-double' : ''"
                      x-text="metodo === 'contante' ? '€ CONTANTE' : '▭ POS'"></span>
                · Totale <strong x-text="formatEuro(totale)"></strong>
            </p>
            <ul style="columns:2;font-size:.9rem">
                <template x-for="r in righeOrdine" :key="r.id">
                    <li><strong x-text="r.q"></strong> × <span x-text="r.nome"></span>
                        — <span x-text="formatEuro(r.importo)"></span></li>
                </template>
            </ul>
            <div style="display:flex;gap:.5rem;margin-top:1rem">
                <button class="btn btn-primary" type="button" @click="inviaConferma()" :disabled="busy">
                    Conferma e stampa (Invio)
                </button>
                <button class="btn" type="button" @click="chiudiModal()">Indietro</button>
            </div>
            <div x-show="errore" class="alert alert-danger" style="margin-top:1rem" x-text="errore"></div>
        </div>
    </div>

    {{-- Modal richiamo --}}
    <div class="modal-backdrop" x-show="modalRichiamo" x-cloak>
        <div class="modal" @click.stop>
            <h2>Richiamo comanda</h2>
            <label class="label">Numero progressivo</label>
            <input class="input" type="number" x-model="richiamoNumero" x-ref="richiamoInput"
                   @keydown.enter.prevent="eseguiRichiamo()">
            <div style="display:flex;gap:.5rem;margin-top:1rem">
                <button class="btn btn-primary" type="button" @click="eseguiRichiamo()">Carica (Invio)</button>
                <button class="btn" type="button" @click="chiudiModal()">Annulla</button>
            </div>
            <div x-show="errore" class="alert alert-danger" style="margin-top:1rem" x-text="errore"></div>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>[x-cloak]{display:none!important} .cassa-main{max-width:none;padding:.5rem 1rem;}</style>
@endpush

@push('scripts')
<script>
function cassaApp(cfg) {
    return {
        menu: cfg.menu,
        stock: cfg.stock || {},
        qty: {},
        activeId: cfg.menu[0]?.id ?? null,
        postazioneId: cfg.postazioneId,
        serataAperta: cfg.serataAperta,
        csrf: cfg.csrf,
        urls: cfg.urls,
        modalPagamento: false,
        modalAnteprima: false,
        modalRichiamo: false,
        metodo: null,
        comandaId: null,
        numeroRichiamato: null,
        correzioniCount: 0,
        motivo: '',
        richiamoNumero: '',
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

        get totale() {
            let t = 0;
            for (const item of this.menu) {
                const q = this.qty[item.id] || 0;
                if (q) t += q * item.prezzo;
            }
            return Math.round(t * 100) / 100;
        },

        get coperti() {
            const coperto = this.menu.find(i => i.nome === 'Coperto');
            return coperto ? (this.qty[coperto.id] || 0) : 0;
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

        stockLabel(item) {
            const r = this.stock[item.id];
            if (r === undefined || r === null) return '';
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
            let q = (this.qty[item.id] || 0) + delta;
            if (q < 0) q = 0;
            if (item.stock_limitato) {
                const max = this.stock[item.id] ?? 0;
                if (q > max) {
                    this.errore = `Stock insufficiente per ${item.nome} (rimasti ${max})`;
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
                if (item?.stock_limitato && r.q > (this.stock[item.id] ?? 0)) {
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
                const res = await fetch(this.urls.conferma, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        postazione_id: this.postazioneId,
                        coperti: this.coperti,
                        metodo_pagamento: this.metodo,
                        comanda_id: this.comandaId,
                        motivo: this.comandaId ? (this.motivo || null) : null,
                        righe: this.righeOrdine.map(r => ({
                            menu_item_id: r.id,
                            quantita: r.q,
                        })),
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Errore salvataggio');
                if (data.stock) this.stock = data.stock;
                this.chiudiModal();
                this.messaggio = 'Comanda #' + data.numero + ' stampata';
                this.resetComanda();
                const w = window.open(data.print_url + '?print=1', '_blank');
                if (!w) {
                    // popup bloccato: naviga nella stessa finestra in iframe nascosto
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = data.print_url + '?print=1';
                    document.body.appendChild(iframe);
                }
            } catch (e) {
                this.errore = e.message;
            } finally {
                this.busy = false;
            }
        },

        apriRichiamo() {
            this.richiamoNumero = '';
            this.errore = null;
            this.modalRichiamo = true;
            this.$nextTick(() => this.$refs.richiamoInput?.focus());
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
                if (e.key === 'Escape') { e.preventDefault(); this.chiudiModal(); }
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
                const cur = this.qty[item.id] || 0;
                let next = cur * 10 + parseInt(e.key, 10);
                if (item.stock_limitato) next = Math.min(next, this.stock[item.id] ?? 0);
                this.qty = { ...this.qty, [item.id]: next };
            }
        },
    };
}
</script>
@endpush
