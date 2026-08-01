/**
 * Toast coordinati (Alpine store + helper globale).
 * Livewire: $this->dispatch('toast', message: '...', type: 'ok')
 * Session flash: <x-ui.flash-toasts />
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('toasts', {
        items: [],
        push({ message, type = 'ok', timeout = 4200 } = {}) {
            if (! message) {
                return;
            }
            const normalized = type === 'success' ? 'ok'
                : (type === 'error' ? 'danger' : type);
            const id = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            this.items.push({ id, message: String(message), type: normalized });
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

window.sagraToast = (message, type = 'ok', timeout = 4200) => {
    const detail = { message, type, timeout };
    if (window.Alpine?.store('toasts')) {
        Alpine.store('toasts').push(detail);
        return;
    }
    window.addEventListener('alpine:initialized', () => {
        Alpine.store('toasts').push(detail);
    }, { once: true });
};
