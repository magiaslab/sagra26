@props([
    'type' => 'ok', // ok|warn|danger
])
@php
    $classes = match ($type) {
        'warn' => 'border-sagra-warn bg-sagra-warn-soft text-sagra-warn',
        'danger', 'error' => 'border-sagra-danger bg-sagra-danger-soft text-sagra-danger',
        default => 'border-sagra bg-sagra-softer text-sagra-dark',
    };
@endphp
<div {{ $attributes->class(['mb-4 rounded-md border-2 px-4 py-3 font-semibold', $classes]) }} role="alert">
    {{ $slot }}
</div>
