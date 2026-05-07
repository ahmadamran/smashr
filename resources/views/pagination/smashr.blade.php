@if ($paginator->hasPages())
    @php
        $pageUrl = fn (?string $url) => $url ? preg_replace('/^http:\/\//', 'https://', $url) : null;
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="rounded-lg bg-white p-3 shadow-lg">
        <div class="flex flex-col gap-3 sm:hidden">
            <div class="flex items-center justify-between gap-3">
                @if ($paginator->onFirstPage())
                    <span class="flex-1 rounded-md border border-blue-950/10 px-4 py-3 text-center text-sm font-black uppercase text-blue-950/30">Previous</span>
                @else
                    <a href="{{ $pageUrl($paginator->previousPageUrl()) }}" rel="prev" class="flex-1 rounded-md border border-blue-950/10 px-4 py-3 text-center text-sm font-black uppercase text-[#071a80]">Previous</a>
                @endif

                @if ($paginator->hasMorePages())
                    <a href="{{ $pageUrl($paginator->nextPageUrl()) }}" rel="next" class="flex-1 rounded-md bg-[#071a80] px-4 py-3 text-center text-sm font-black uppercase text-white">Next</a>
                @else
                    <span class="flex-1 rounded-md bg-blue-950/10 px-4 py-3 text-center text-sm font-black uppercase text-blue-950/30">Next</span>
                @endif
            </div>
            <p class="text-center text-xs font-bold uppercase tracking-wide text-blue-950/50">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }} | {{ $paginator->total() }} results
            </p>
        </div>

        <div class="hidden items-center justify-between gap-4 sm:flex">
            <p class="text-sm font-bold text-blue-950/60">
                Showing
                <span class="font-black text-[#071a80]">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-black text-[#071a80]">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-black text-[#071a80]">{{ $paginator->total() }}</span>
                results
            </p>

            <div class="flex flex-wrap items-center justify-end gap-1">
                @if ($paginator->onFirstPage())
                    <span class="rounded-md border border-blue-950/10 px-3 py-2 text-sm font-black text-blue-950/30">Prev</span>
                @else
                    <a href="{{ $pageUrl($paginator->previousPageUrl()) }}" rel="prev" class="rounded-md border border-blue-950/10 px-3 py-2 text-sm font-black text-[#071a80] hover:border-[#d6a31d]">Prev</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-3 py-2 text-sm font-black text-blue-950/40">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="rounded-md bg-[#071a80] px-3 py-2 text-sm font-black text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $pageUrl($url) }}" class="rounded-md border border-blue-950/10 px-3 py-2 text-sm font-black text-[#071a80] hover:border-[#d6a31d]">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $pageUrl($paginator->nextPageUrl()) }}" rel="next" class="rounded-md bg-[#071a80] px-3 py-2 text-sm font-black text-white">Next</a>
                @else
                    <span class="rounded-md bg-blue-950/10 px-3 py-2 text-sm font-black text-blue-950/30">Next</span>
                @endif
            </div>
        </div>
    </nav>
@endif
