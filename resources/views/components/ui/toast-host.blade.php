<div
    class="pointer-events-none fixed inset-x-0 top-3 z-[200] flex flex-col items-center gap-2 px-3 sm:items-end sm:px-4"
    aria-live="polite"
    aria-relevant="additions"
    x-data
    x-on:toast.window="Alpine.store('toasts').push({ message: $event.detail.message, type: $event.detail.type || 'ok', timeout: $event.detail.timeout ?? 3600 })"
>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div
            class="pointer-events-auto flex w-full max-w-sm items-start gap-2.5 rounded-md px-3.5 py-2.5 shadow-md ring-1"
            :class="{
                'bg-sagra-softer text-sagra-dark ring-sagra/25': t.type === 'ok',
                'bg-sagra-warn-soft text-sagra-warn ring-sagra-warn/25': t.type === 'warn',
                'bg-sagra-danger-soft text-sagra-danger ring-sagra-danger/25': t.type === 'danger',
                'bg-white text-sagra-ink ring-sagra-line': !['ok','warn','danger'].includes(t.type),
            }"
            role="status"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-[-6px]"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                  :class="{
                      'bg-sagra/15 text-sagra-dark': t.type === 'ok',
                      'bg-sagra-warn/15 text-sagra-warn': t.type === 'warn',
                      'bg-sagra-danger/15 text-sagra-danger': t.type === 'danger',
                      'bg-sagra-line/40 text-sagra-muted': !['ok','warn','danger'].includes(t.type),
                  }"
                  aria-hidden="true"
                  x-text="t.type === 'ok' ? '✓' : (t.type === 'warn' ? '!' : (t.type === 'danger' ? '×' : 'i'))"></span>
            <p class="min-w-0 flex-1 pt-0.5 text-sm font-medium leading-snug" x-text="t.message"></p>
            <button
                type="button"
                class="shrink-0 rounded px-1 text-base leading-none opacity-50 hover:opacity-100"
                @click="$store.toasts.remove(t.id)"
                aria-label="Chiudi notifica"
            >&times;</button>
        </div>
    </template>
</div>
