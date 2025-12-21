@if ($paginator->hasPages())
    <nav>
        <ul class="pagination pagination-sm mb-0">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link">‹</span>
                </li>
            @else
                <li class="page-item">
                    <button class="page-link"
                            wire:click="previousPage"
                            data-scroll="false">
                        ‹
                    </button>
                </li>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}">
                            <button class="page-link"
                                    wire:click="gotoPage({{ $page }})"
                                    data-scroll="false">
                                {{ $page }}
                            </button>
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <button class="page-link"
                            wire:click="nextPage"
                            data-scroll="false">
                        ›
                    </button>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">›</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
