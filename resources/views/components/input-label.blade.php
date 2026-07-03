@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-bold uppercase tracking-wider text-dark']) }}>
    {{ $value ?? $slot }}
</label>
