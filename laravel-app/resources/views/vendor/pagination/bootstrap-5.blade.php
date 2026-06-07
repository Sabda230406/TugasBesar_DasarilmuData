@if ($paginator->hasPages())
	@php
		$itemName = $itemName ?? 'data';
		$ariaLabel = $ariaLabel ?? 'Navigasi halaman';
	@endphp

	<div class="app-pagination">
		@if($showSummary ?? true)
			<div class="app-pagination-summary">
				Menampilkan {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} {{ $itemName }}
			</div>
		@endif

		<nav aria-label="{{ $ariaLabel }}">
			<ul class="pagination">
				<li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
					@if($paginator->onFirstPage())
						<span class="page-link page-link-nav" aria-hidden="true">&lsaquo;</span>
					@else
						<a class="page-link page-link-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&lsaquo;</a>
					@endif
				</li>

				@foreach ($elements as $element)
					@if (is_string($element))
						<li class="page-item disabled" aria-disabled="true">
							<span class="page-link page-link-ellipsis">{{ $element }}</span>
						</li>
					@endif

					@if (is_array($element))
						@foreach ($element as $page => $url)
							<li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}">
								@if ($page == $paginator->currentPage())
									<span class="page-link" aria-current="page">{{ $page }}</span>
								@else
									<a class="page-link" href="{{ $url }}">{{ $page }}</a>
								@endif
							</li>
						@endforeach
					@endif
				@endforeach

				<li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
					@if($paginator->hasMorePages())
						<a class="page-link page-link-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&rsaquo;</a>
					@else
						<span class="page-link page-link-nav" aria-hidden="true">&rsaquo;</span>
					@endif
				</li>
			</ul>
		</nav>
	</div>
@endif
