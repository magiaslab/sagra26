@props([
    'title',
    'subtitle' => null,
])
<div {{ $attributes->class('mb-6 flex flex-wrap items-center justify-between gap-3') }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-sagra-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-sagra-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
