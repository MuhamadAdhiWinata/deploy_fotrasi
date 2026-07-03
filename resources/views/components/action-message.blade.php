@props(['on'])

<div x-data="{ shown: false, timeout: null }"
     x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
     x-show.transition.out.opacity.duration.1500ms="shown"
     x-transition:leave.opacity.duration.1500ms
     style="display: none;"
    {{ $attributes->merge(['class' => 'bg-accent border-3 border-dark px-4 py-2 text-sm font-bold text-dark shadow-[3px_3px_0px_0px_#1a1a1a]']) }}>
    {{ $slot->isEmpty() ? 'Saved.' : $slot }}
</div>
