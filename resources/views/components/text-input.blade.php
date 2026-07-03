@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full px-3 py-2.5 text-sm font-semibold border-3 border-dark bg-white shadow-[3px_3px_0px_0px_#1a1a1a] focus:outline-none focus:ring-0 focus:border-primary focus:shadow-[3px_3px_0px_0px_#406093] transition-shadow']) }}>
