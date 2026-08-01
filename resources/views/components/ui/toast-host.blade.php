<div
    class="pointer-events-none fixed inset-x-0 top-3 z-[200] flex flex-col items-center gap-2 px-3 sm:items-end sm:px-4"
    x-data
    x-on:toast.window="Alpine.store('toasts').push({ message: $event.detail.message, type: $event.detail.type || 'ok', timeout: $event.detail.timeout ?? 4200 })"
>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div
            class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg border px-3.5 py-3 shadow-lg"
            :class="{
                'border-sagra bg-sagra-softer text-sagra-dark': t.type === 'ok',
                'border-sagra-warn bg-sagra-warn-soft text-sagra-warn': t.type === 'warn',
                'border-sagra-danger bg-sagra-danger-soft text-sagra-danger': t.type === 'danger',
                'border-sagra-line bg-white text-sagra-ink': !['ok','warn','danger'].includes(t.type),
            }"
            role="status"
            x-show="true"
            x-transition.opacity.duration.200ms
        >
            <p class="min-w-0 flex-1 text-sm font-semibold" x-text="t.message"></p>
            <button
                type="button"
                class="shrink-0 rounded px-1.5 text-lg leading-none opacity-60 hover:opacity-100"
                @click="$store.toasts.remove(t.id)"
                aria-label="Chiudi"
            >&times;</button>
        </div>
    </template>
</div>
