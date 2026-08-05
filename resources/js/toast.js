/**
 * Toast coordinati (Alpine store + helper globale).
 *
 * Livewire:  $this->dispatch('toast', message: '...', type: 'ok')
 * Session:   <x-ui.flash-toasts />  (status → ok, error → danger, warning → warn)
 * JS/cassa:  window.sagraToast(msg, 'ok'|'warn'|'danger')
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('toasts', {
        items: [],
        max: 4,
        lastKey: null,
        lastAt: 0,

        push({ message, type = 'ok', timeout = 3600 } = {}) {
            if (! message) {
                return;
            }

            const normalized = type === 'success' ? 'ok'
                : (type === 'error' ? 'danger' : type);
            const text = String(message).trim();
            if (! text) {
                return;
            }

            const now = Date.now();
            const key = `${normalized}:${text}`;
            // Evita doppioni flash+dispatch o doppio click entro 1.2s
            if (this.lastKey === key && (now - this.lastAt) < 1200) {
                return;
            }
            this.lastKey = key;
            this.lastAt = now;

            const id = `${now}-${Math.random().toString(36).slice(2, 8)}`;
            this.items.push({ id, message: text, type: normalized });
            if (this.items.length > this.max) {
                this.items = this.items.slice(-this.max);
            }
            if (timeout > 0) {
                window.setTimeout(() => this.remove(id), timeout);
            }
        },

        remove(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },

        clear() {
            this.items = [];
        },
    });
});

window.sagraToast = (message, type = 'ok', timeout = 3600) => {
    const detail = { message, type, timeout };
    if (window.Alpine?.store('toasts')) {
        Alpine.store('toasts').push(detail);
        return;
    }
    window.addEventListener('alpine:initialized', () => {
        Alpine.store('toasts').push(detail);
    }, { once: true });
};

window.dispatchEvent(new CustomEvent('sagra:toast-ready'));
