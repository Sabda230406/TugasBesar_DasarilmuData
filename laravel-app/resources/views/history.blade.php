@extends('layouts.app')

@section('content')
	@php
		$modelIconFor = function ($name) {
			$name = strtolower((string) $name);
			return str_contains($name, 'knn') ? 'fa-diagram-project' : (str_contains($name, 'svm') ? 'fa-vector-square' : 'fa-tree');
		};
	@endphp

	<style>
		.history-hero {
			background: linear-gradient(135deg, rgba(15, 118, 110, 0.14) 0%, rgba(15, 118, 110, 0.02) 100%);
			border: 1px solid rgba(15, 118, 110, 0.18);
			border-radius: 18px;
			padding: 1.5rem 1.75rem;
		}

		.stat-card {
			border-radius: 16px;
			border: 1px solid rgba(214, 226, 234, 0.9);
			background: #fff;
			box-shadow: 0 16px 30px rgba(15, 32, 50, 0.08);
			padding: 1.25rem;
		}

		.stat-value {
			font-size: 1.8rem;
			font-weight: 800;
			color: #0f172a;
		}

		.table thead {
			background: #f8fafc;
		}

		.table td {
			vertical-align: middle;
		}

		.history-table th,
		.history-table td {
			padding: 0.9rem 1rem;
		}

		.history-table th:nth-child(1),
		.history-table td:nth-child(1) {
			width: 70px;
			text-align: center;
		}

		.history-table th:nth-child(2),
		.history-table td:nth-child(2) {
			width: 200px;
			text-align: center;
			white-space: nowrap;
		}

		.history-table th:nth-child(3),
		.history-table td:nth-child(3) {
			width: 150px;
			text-align: center;
		}

		.history-table th:nth-child(4),
		.history-table td:nth-child(4) {
			width: 180px;
			text-align: center;
		}

		.input-card {
			background: #f8fafc;
			border: 1px solid rgba(214, 226, 234, 0.9);
			border-radius: 12px;
			padding: 0.75rem 0.9rem;
			max-width: 520px;
		}

		.input-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 0.5rem;
		}

		.input-item {
			background: #ffffff;
			border: 1px solid rgba(214, 226, 234, 0.9);
			border-radius: 10px;
			padding: 0.45rem 0.6rem;
		}

		.input-key {
			font-size: 0.68rem;
			text-transform: uppercase;
			letter-spacing: 0.1em;
			color: rgba(15, 23, 42, 0.5);
			font-weight: 700;
		}

		.input-value {
			font-size: 0.85rem;
			font-weight: 600;
			color: #0f172a;
		}

		.input-label {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			font-size: 0.75rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.12em;
			color: rgba(15, 23, 42, 0.55);
			margin-bottom: 0.35rem;
		}

		.prediction-badge {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			padding: 0.4rem 0.7rem;
			border-radius: 999px;
			background: rgba(16, 185, 129, 0.12);
			color: #047857;
			font-weight: 700;
			font-size: 0.85rem;
		}

		.prediction-badge.high {
			background: rgba(239, 68, 68, 0.12);
			color: #b91c1c;
		}

		.time-badge {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			padding: 0.4rem 0.7rem;
			border-radius: 999px;
			background: rgba(59, 130, 246, 0.1);
			color: #1d4ed8;
			font-weight: 600;
			font-size: 0.8rem;
		}

		.model-badge {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			padding: 0.4rem 0.7rem;
			border-radius: 999px;
			background: rgba(15, 118, 110, 0.12);
			color: #0f5e57;
			font-weight: 800;
			font-size: 0.8rem;
			white-space: nowrap;
		}

		.filter-bar {
			display: flex;
			flex-wrap: wrap;
			gap: 0.75rem;
			align-items: center;
			margin-bottom: 1.25rem;
		}

		.filter-input {
			border-radius: 12px;
			border: 1px solid rgba(148, 163, 184, 0.4);
			padding: 0.6rem 0.9rem;
			min-width: 220px;
		}

		.filter-select {
			border-radius: 12px;
			border: 1px solid rgba(148, 163, 184, 0.4);
			padding: 0.5rem 0.75rem;
			font-size: 0.85rem;
			min-width: 160px;
		}

		.filter-panel {
			border-radius: 16px;
			border: 1px solid rgba(148, 163, 184, 0.2);
			background: #f8fafc;
			padding: 1rem 1.25rem;
		}

		.filter-title {
			font-size: 1rem;
			font-weight: 800;
			color: #0f172a;
		}

		.filter-help {
			font-size: 0.88rem;
			color: #64748b;
			margin-bottom: 0;
		}

		.filter-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			padding: 0.35rem 0.75rem;
			border-radius: 999px;
			background: rgba(15, 118, 110, 0.12);
			color: #0f5e57;
			font-size: 0.75rem;
			font-weight: 700;
			white-space: nowrap;
		}

		.filter-meta {
			font-size: 0.85rem;
			color: #64748b;
		}

		.detail-btn {
			border-radius: 999px;
			padding: 0.45rem 0.9rem;
			font-weight: 700;
			font-size: 0.85rem;
		}

		.modal-input-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 0.65rem;
		}

		.empty-state {
			text-align: center;
			padding: 2.5rem 1rem;
			color: #94a3b8;
		}

		.history-pagination {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 1rem;
			padding-top: 1rem;
			border-top: 1px solid rgba(148, 163, 184, 0.16);
		}

		.pagination-summary {
			color: #64748b;
			font-size: 0.86rem;
			font-weight: 700;
		}

		.history-pagination .pagination {
			display: flex;
			flex-wrap: wrap;
			justify-content: flex-end;
			gap: 0.45rem;
			margin-bottom: 0;
		}

		.history-pagination .page-item .page-link {
			min-width: 38px;
			height: 38px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border: 1px solid rgba(148, 163, 184, 0.22);
			border-radius: 12px;
			background: #f8fafc;
			color: #475569;
			font-weight: 800;
			box-shadow: none;
			transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
		}

		.history-pagination .page-item .page-link:hover {
			border-color: rgba(15, 118, 110, 0.28);
			background: rgba(15, 118, 110, 0.1);
			color: #0f5e57;
		}

		.history-pagination .page-item.active .page-link {
			border-color: #0f766e;
			background: #0f766e;
			color: #ffffff;
		}

		.history-pagination .page-item.disabled .page-link {
			background: #eef2f7;
			color: #94a3b8;
			border-color: rgba(148, 163, 184, 0.16);
		}

		@media (max-width: 575.98px) {
			.history-pagination {
				align-items: stretch;
			}

			.history-pagination .pagination {
				justify-content: flex-start;
			}
		}
	</style>

	<div class="history-hero mb-4">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<div>
				<p class="eyebrow mb-2">Riwayat Prediksi</p>
				<h2 class="h4 fw-bold mb-2"><i class="fa-solid fa-clipboard-list me-2"></i>Monitor hasil prediksi yang sudah tersimpan</h2>
				<p class="mb-0 text-muted">Gunakan riwayat ini untuk evaluasi ulang atau kebutuhan laporan.</p>
			</div>
			<div class="d-flex gap-2">
				<a href="/form" class="btn btn-dark">Prediksi Baru</a>
				<a href="/" class="btn btn-outline-secondary">Kembali ke Landing</a>
			</div>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-md-4">
			<div class="stat-card">
				<p class="text-muted small mb-1">Total Riwayat</p>
				<div class="stat-value">{{ $data->total() }}</div>
				<p class="mb-0 small text-muted">Prediksi tersimpan</p>
			</div>
		</div>
		<div class="col-md-4">
			<div class="stat-card">
				<p class="text-muted small mb-1">Status Sistem</p>
				<div class="stat-value">Aktif</div>
				<p class="mb-0 small text-muted">Model siap menerima input</p>
			</div>
		</div>
		<div class="col-md-4">
			<div class="stat-card">
				<p class="text-muted small mb-1">Data Terbaru</p>
				<div class="stat-value">Realtime</div>
				<p class="mb-0 small text-muted">Update otomatis dari form prediksi</p>
			</div>
		</div>
	</div>

	<div class="card shadow-sm border-0">
		<div class="card-body p-4">
			@php
				$filterKeys = [
					'model' => 'Model',
					'gender' => 'Gender',
					'ever_married' => 'Married',
					'work_type' => 'Work Type',
					'Residence_type' => 'Residence',
					'smoking_status' => 'Smoking',
					'hypertension' => 'Hypertension',
					'heart_disease' => 'Heart Disease',
				];
				$filterOptions = [];
				foreach ($filterKeys as $key => $label) {
					$filterOptions[$key] = [];
				}
				foreach ($data as $row) {
					$payload = json_decode($row->input_data, true);
					if (!is_array($payload)) {
						continue;
					}
					foreach ($filterKeys as $key => $label) {
						if ($key === 'model') {
							$filterOptions[$key][] = $row->model_name ?? 'Decision Tree';
							continue;
						}
						if (isset($payload[$key]) && $payload[$key] !== '') {
							$filterOptions[$key][] = $payload[$key];
						}
					}
				}
				foreach ($filterOptions as $key => $values) {
					$filterOptions[$key] = array_values(array_unique($values));
					sort($filterOptions[$key]);
				}
			@endphp
			<div class="filter-panel mb-3">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
					<div>
						<p class="eyebrow mb-1">Filter Riwayat</p>
						<div class="filter-title">Saring data prediksi sesuai kebutuhan laporan</div>
						<p class="filter-help">Gunakan filter untuk membatasi tampilan berdasarkan karakteristik pasien dan model yang dipakai. Data asli tidak berubah.</p>
					</div>
					<div class="filter-pill"><i class="fa-solid fa-filter"></i> Filter hanya untuk tampilan</div>
				</div>
			</div>
			<div class="filter-bar">
				@foreach($filterKeys as $key => $label)
					<div>
						<select class="form-select filter-select" data-filter-key="{{ $key }}">
							<option value="">{{ $label }}: Semua</option>
							@foreach($filterOptions[$key] as $value)
								<option value="{{ strtolower((string) $value) }}">{{ $value }}</option>
							@endforeach
						</select>
					</div>
				@endforeach
				<div class="filter-meta">
					Menampilkan <span id="historyCount">{{ $data->count() }}</span>
					data ({{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} dari {{ $data->total() }})
				</div>
			</div>
			<div class="table-responsive">
				<table class="table align-middle table-borderless history-table">
					<thead>
						<tr>
							<th>No</th>
							<th>Waktu</th>
							<th>Model</th>
							<th>Hasil Prediksi</th>
							<th>Detail Input</th>
						</tr>
					</thead>
					<tbody>
						@forelse($data as $item)
							@php
								$isHighRisk = (int) $item->prediction === 1;
								$predictionLabel = $isHighRisk ? 'Risiko Tinggi' : 'Risiko Rendah';
								$predictionIcon = $isHighRisk ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
								$predictionClass = $isHighRisk ? 'high' : '';
								$displayTime = $item->created_at
									? $item->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') 
									: $item->created_at;
								$rowPayload = json_decode($item->input_data, true);
								$modelName = $item->model_name ?? 'Decision Tree';
							@endphp
							<tr class="history-row"
								data-input="{{ strtolower($item->input_data) }}"
								data-prediction="{{ strtolower($predictionLabel) }}"
								data-model="{{ strtolower($modelName) }}"
								data-time="{{ strtolower((string) $displayTime) }}"
								data-gender="{{ strtolower((string) ($rowPayload['gender'] ?? '')) }}"
								data-ever_married="{{ strtolower((string) ($rowPayload['ever_married'] ?? '')) }}"
								data-work_type="{{ strtolower((string) ($rowPayload['work_type'] ?? '')) }}"
								data-residence_type="{{ strtolower((string) ($rowPayload['Residence_type'] ?? '')) }}"
								data-smoking_status="{{ strtolower((string) ($rowPayload['smoking_status'] ?? '')) }}"
								data-hypertension="{{ strtolower((string) ($rowPayload['hypertension'] ?? '')) }}"
								data-heart_disease="{{ strtolower((string) ($rowPayload['heart_disease'] ?? '')) }}">
								<td class="fw-semibold text-muted">{{ ($data->firstItem() ?? 0) + $loop->index }}</td>
								<td>
									<span class="time-badge"><i class="fa-regular fa-clock"></i> {{ $displayTime }}</span>
								</td>
								<td>
									<span class="model-badge"><i class="fa-solid {{ $modelIconFor($modelName) }}"></i> {{ $modelName }}</span>
								</td>
								<td>
									<span class="prediction-badge {{ $predictionClass }}">
										<i class="fa-solid {{ $predictionIcon }}"></i>
										{{ $predictionLabel }}
									</span>
								</td>
								<td>
									<button class="btn btn-outline-secondary detail-btn" type="button" data-bs-toggle="modal" data-bs-target="#detailModal{{ $item->id }}">
										<i class="fa-solid fa-list-ul me-1"></i> Lihat Detail
									</button>
								</td>
							</tr>
							<div class="modal fade" id="detailModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-lg modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><i class="fa-solid fa-database me-2"></i>Detail Input</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										</div>
										<div class="modal-body">
											@php
												$inputPayload = json_decode($item->input_data, true);
												$isPayloadArray = is_array($inputPayload);
												$displayPayload = $isPayloadArray ? $inputPayload : [];
											@endphp
											@if($isPayloadArray)
												<div class="input-card">
													<span class="input-label"><i class="fa-solid fa-clipboard-list me-2"></i>Input</span>
													<div class="modal-input-grid">
														@foreach($displayPayload as $key => $value)
															<div class="input-item">
																<div class="input-key">{{ str_replace('_', ' ', $key) }}</div>
																<div class="input-value">{{ is_array($value) ? json_encode($value) : $value }}</div>
															</div>
														@endforeach
													</div>
												</div>
											@else
												<div class="input-value">{{ $item->input_data }}</div>
											@endif
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
										</div>
									</div>
								</div>
							</div>
						@empty
							<tr>
								<td colspan="5">
									<div class="empty-state">
										<i class="fa-regular fa-clock fa-2x mb-2"></i>
										<p class="mb-1 fw-semibold">Belum ada riwayat prediksi</p>
										<p class="mb-0 small">Mulai prediksi untuk melihat histori tersimpan di sini.</p>
									</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
			@if($data->hasPages())
				@php
					$currentPage = $data->currentPage();
					$lastPage = $data->lastPage();
					$startPage = max(1, $currentPage - 1);
					$endPage = min($lastPage, $currentPage + 1);

					if ($currentPage === 1) {
						$endPage = min($lastPage, 3);
					}

					if ($currentPage === $lastPage) {
						$startPage = max(1, $lastPage - 2);
					}
				@endphp
				<div class="history-pagination mt-4">
					<div class="pagination-summary">
						Menampilkan {{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} data
					</div>
					<nav aria-label="Navigasi riwayat">
						<ul class="pagination">
							<li class="page-item {{ $data->onFirstPage() ? 'disabled' : '' }}">
								@if($data->onFirstPage())
									<span class="page-link" aria-hidden="true">&lsaquo;</span>
								@else
									<a class="page-link" href="{{ $data->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lsaquo;</a>
								@endif
							</li>

							@if($startPage > 1)
								<li class="page-item">
									<a class="page-link" href="{{ $data->url(1) }}">1</a>
								</li>
								@if($startPage > 2)
									<li class="page-item disabled">
										<span class="page-link">...</span>
									</li>
								@endif
							@endif

							@for($page = $startPage; $page <= $endPage; $page++)
								<li class="page-item {{ $page === $currentPage ? 'active' : '' }}">
									@if($page === $currentPage)
										<span class="page-link">{{ $page }}</span>
									@else
										<a class="page-link" href="{{ $data->url($page) }}">{{ $page }}</a>
									@endif
								</li>
							@endfor

							@if($endPage < $lastPage)
								@if($endPage < $lastPage - 1)
									<li class="page-item disabled">
										<span class="page-link">...</span>
									</li>
								@endif
								<li class="page-item">
									<a class="page-link" href="{{ $data->url($lastPage) }}">{{ $lastPage }}</a>
								</li>
							@endif

							<li class="page-item {{ $data->hasMorePages() ? '' : 'disabled' }}">
								@if($data->hasMorePages())
									<a class="page-link" href="{{ $data->nextPageUrl() }}" aria-label="Halaman berikutnya">&rsaquo;</a>
								@else
									<span class="page-link" aria-hidden="true">&rsaquo;</span>
								@endif
							</li>
						</ul>
					</nav>
				</div>
			@endif
		</div>
	</div>

	<script>
		const historyRows = Array.from(document.querySelectorAll('.history-row'));
		const historyCount = document.getElementById('historyCount');
		const filterSelects = Array.from(document.querySelectorAll('.filter-select'));

		const updateHistoryFilter = () => {
			const selectFilters = filterSelects.reduce((acc, select) => {
				const key = select.dataset.filterKey;
				const value = select.value;
				if (key && value) {
					acc[key] = value;
				}
				return acc;
			}, {});

			let visibleCount = 0;
			historyRows.forEach((row) => {
				const matchesSelects = Object.entries(selectFilters).every(([key, value]) => {
					const datasetKey = key.toLowerCase();
					const rowValue = row.dataset[datasetKey] || '';
					return rowValue === value;
				});
				const isMatch = matchesSelects;
				row.style.display = isMatch ? '' : 'none';
				if (isMatch) visibleCount += 1;
			});
			historyCount.textContent = visibleCount;
		};

		filterSelects.forEach((select) => {
			select.addEventListener('change', updateHistoryFilter);
		});
	</script>
@endsection
