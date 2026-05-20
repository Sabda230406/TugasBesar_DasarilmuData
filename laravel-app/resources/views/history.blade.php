@extends('layouts.app')

@section('content')
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
							@endphp
							<tr class="history-row"
								data-input="{{ strtolower($item->input_data) }}"
								data-prediction="{{ strtolower($predictionLabel) }}"
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
								<td colspan="3">
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
				<div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
					<div class="text-muted small">Halaman {{ $data->currentPage() }} dari {{ $data->lastPage() }}</div>
					{{ $data->links('pagination::bootstrap-5') }}
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
