@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="mt-6">
        <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-dark/50">
                @if ($paginator->firstItem())
                    {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
                @else
                    {{ $paginator->count() }}
                @endif
            </div>
            <div class="flex gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark/30 bg-white border-3 border-dark/20 shadow-[2px_2px_0px_0px_#1a1a1a]/10 cursor-not-allowed">←</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark bg-white border-3 border-dark shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">←</a>
                @endif

                {{-- Pages --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark/30 bg-white border-3 border-dark/20 shadow-[2px_2px_0px_0px_#1a1a1a]/10 cursor-default">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark bg-accent border-3 border-dark shadow-[2px_2px_0px_0px_#1a1a1a] cursor-default">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark bg-white border-3 border-dark shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark bg-white border-3 border-dark shadow-[2px_2px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0px_0px_#1a1a1a] transition-all">→</a>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-dark/30 bg-white border-3 border-dark/20 shadow-[2px_2px_0px_0px_#1a1a1a]/10 cursor-not-allowed">→</span>
                @endif
            </div>
        </div>
    </nav>
@endif
