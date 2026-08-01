@props([
    'title',
    'subtitle' => null,
])
<header {{ $attributes->class('page-header') }}>
    <div class="page-header-text">
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-header-sub">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="page-header-actions">
            {{ $actions }}
        </div>
    @endisset
</header>
