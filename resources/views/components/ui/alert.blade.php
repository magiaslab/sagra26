@props([
    'type' => 'ok', // ok|warn|danger
])
@php
    $classes = match ($type) {
        'warn' => 'bg-sagra-warn-soft text-sagra-warn ring-sagra-warn/30',
        'danger', 'error' => 'bg-sagra-danger-soft text-sagra-danger ring-sagra-danger/30',
        default => 'bg-sagra-softer text-sagra-dark ring-sagra/25',
    };
@endphp
<div {{ $attributes->class(['mb-4 rounded-md px-4 py-3 text-sm font-medium ring-1 ring-inset', $classes]) }} role="alert">
    {{ $slot }}
</div>
