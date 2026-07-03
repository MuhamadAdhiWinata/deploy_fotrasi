@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'bg-accent border-3 border-dark px-4 py-3 text-sm font-bold text-dark shadow-[3px_3px_0px_0px_#1a1a1a]']) }}>
        {{ $status }}
    </div>
@endif
