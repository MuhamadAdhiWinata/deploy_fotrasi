@props(['active' => false, 'href' => '#'])

@php
$classes = $active
    ? 'flex items-center gap-3 px-4 py-3 text-sm font-bold uppercase border-3 bg-primary text-white border-primary'
    : 'flex items-center gap-3 px-4 py-3 text-sm font-bold uppercase border-3 border-transparent text-dark hover:border-dark/20';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
