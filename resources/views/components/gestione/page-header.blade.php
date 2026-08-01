@props([
    'title',
    'subtitle' => null,
])
<header {{ $attributes->class('mb-4 flex flex-wrap items-start justify-between gap-3') }}>
    <div class="min-w-0">
        <h1 class="m-0 text-2xl font-extrabold leading-tight text-sagra-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm font-semibold text-sagra-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
