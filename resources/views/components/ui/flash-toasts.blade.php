{{-- Converte session flash Laravel in toast Alpine al mount. --}}
@if (session('status') || session('error') || session('warning'))
    <div
        x-data
        x-init="
            @if (session('status'))
                Alpine.store('toasts').push({ message: @js(session('status')), type: 'ok' });
            @endif
            @if (session('error'))
                Alpine.store('toasts').push({ message: @js(session('error')), type: 'danger' });
            @endif
            @if (session('warning'))
                Alpine.store('toasts').push({ message: @js(session('warning')), type: 'warn' });
            @endif
        "
        class="hidden"
    ></div>
@endif
