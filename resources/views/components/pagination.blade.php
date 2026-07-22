@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Experience pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination-link disabled" aria-disabled="true">&larr; Previous</span>
        @else
            <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Previous</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-ellipsis">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="pagination-link active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
        @else
            <span class="pagination-link disabled" aria-disabled="true">Next &rarr;</span>
        @endif
    </nav>
@endif
