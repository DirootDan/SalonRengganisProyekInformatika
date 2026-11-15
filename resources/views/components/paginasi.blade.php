@if ($paginator->hasPages())
    <nav class="paginasi-nav" role="navigation" aria-label="Navigasi Halaman">
        @if (method_exists($paginator, 'total'))
            <p class="paginasi-ringkas">
                Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong> sampai <strong>{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong>{{ $paginator->total() }}</strong> data
            </p>
        @endif

        <ul class="paginasi-daftar">
            {{-- Tombol Halaman Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="paginasi-tautan nonaktif" aria-disabled="true" aria-label="Halaman sebelumnya">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            @else
                <li>
                    <a class="paginasi-tautan" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li>
            @endif

            {{-- Nomor Halaman --}}
            @isset($elements)
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li>
                            <span class="paginasi-tautan pemisah" aria-disabled="true">{{ $element }}</span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li aria-current="page">
                                    <span class="paginasi-tautan aktif">{{ $page }}</span>
                                </li>
                            @else
                                <li>
                                    <a class="paginasi-tautan" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endisset

            {{-- Tombol Halaman Berikutnya --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a class="paginasi-tautan" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li>
            @else
                <li>
                    <span class="paginasi-tautan nonaktif" aria-disabled="true" aria-label="Halaman berikutnya">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
