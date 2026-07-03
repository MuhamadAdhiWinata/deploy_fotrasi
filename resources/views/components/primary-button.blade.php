@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge(['class' => 'bg-primary text-white border-3 border-dark px-5 py-2.5 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0px_0px_#1a1a1a] transition-all disabled:opacity-50']) }}>
    {{ $slot }}
</button>
