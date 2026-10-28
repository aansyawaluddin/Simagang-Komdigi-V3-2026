{{-- Pagination ringkas gaya Workspace. Variabel: $paginator (LengthAwarePaginator), $label (mis. 'kelompok') --}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect(range(1, $last))->filter(fn ($p) => $p === 1 || $p === $last || abs($p - $current) <= 1)->values();
    @endphp
    <nav class="ws-pagination" aria-label="Halaman {{ $label }}">
        <p class="text-sm text-slate-500">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} {{ $label }}
        </p>
        <ul class="flex items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="ws-pagination__link" aria-disabled="true"><i class="fas fa-chevron-left text-xs"></i><span class="sr-only">Sebelumnya</span></span>
                @else
                    <a class="ws-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fas fa-chevron-left text-xs"></i><span class="sr-only">Sebelumnya</span></a>
                @endif
            </li>
            @foreach ($pages as $i => $page)
                @if ($i > 0 && $page - $pages[$i - 1] > 1)
                    <li><span class="ws-pagination__gap" aria-hidden="true">…</span></li>
                @endif
                <li>
                    @if ($page === $current)
                        <span class="ws-pagination__link ws-pagination__link--current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="ws-pagination__link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a class="ws-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="fas fa-chevron-right text-xs"></i><span class="sr-only">Berikutnya</span></a>
                @else
                    <span class="ws-pagination__link" aria-disabled="true"><i class="fas fa-chevron-right text-xs"></i><span class="sr-only">Berikutnya</span></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
