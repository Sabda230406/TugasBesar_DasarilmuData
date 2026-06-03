@extends('layouts.admin')

@section('content')
	@php
		$versionLabel = function ($run) {
			if ($run->stage === 'legacy_baseline') {
				return 'MODEL_BASELINE';
			}

			return 'MODEL_' . optional($run->finished_at ?? $run->created_at)->format('Ymd_His');
		};

		$statusTone = function ($status) {
			return match ($status) {
				'active' => 'success',
				'available' => 'primary',
				'rejected' => 'danger',
				default => 'secondary',
			};
		};

		$metricValue = function ($version, array|string $keys) {
			$metrics = $version->evaluation_metrics ?: ($version->metrics ?: []);
			$keys = is_array($keys) ? $keys : [$keys];

			foreach ($keys as $key) {
				if (isset($metrics[$key]) && is_numeric($metrics[$key])) {
					return (float) $metrics[$key];
				}
			}

			$report = $metrics['classification_report'] ?? [];
			$strokeReport = $report['1'] ?? $report[1] ?? null;
			if (is_array($strokeReport)) {
				foreach ($keys as $key) {
					if (isset($strokeReport[$key]) && is_numeric($strokeReport[$key])) {
						return (float) $strokeReport[$key];
					}
				}
			}

			return null;
		};

		$formatMetric = function ($value) {
			if (! is_numeric($value)) {
				return '-';
			}

			$value = (float) $value;
			if ($value <= 1) {
				$value *= 100;
			}

			return number_format($value, 2) . '%';
		};

		$averageAccuracy = function ($run) use ($metricValue) {
			$values = $run->modelVersions
				->map(fn ($version) => $metricValue($version, 'accuracy'))
				->filter(fn ($value) => is_numeric($value))
				->map(fn ($value) => (float) $value <= 1 ? (float) $value * 100 : (float) $value);

			if ($values->isEmpty()) {
				return null;
			}

			return $values->avg();
		};

		$falseNegative = function ($version) {
			$metrics = $version->evaluation_metrics ?: ($version->metrics ?: []);
			if (isset($metrics['false_negative'])) {
				return $metrics['false_negative'];
			}

			$matrix = $metrics['confusion_matrix'] ?? null;
			if (is_array($matrix) && isset($matrix[1][0])) {
				return $matrix[1][0];
			}

			return '-';
		};
	@endphp

	<div class="admin-page-stack">
		<div class="admin-panel">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
				<div class="d-flex gap-3 align-items-start">
					<span class="stat-icon"><i class="fa-solid fa-folder-tree"></i></span>
					<div>
						<p class="eyebrow mb-2">Retrain Model</p>
						<h1 class="h4 fw-bold mb-1">Daftar versi retraining</h1>
						<p class="section-subtitle mb-0">Pilih hasil retraining yang ingin digunakan, lalu cek metrik model di detailnya.</p>
					</div>
				</div>
				<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-dark">
					<i class="fa-solid fa-arrow-left me-2"></i>Dashboard
				</a>
			</div>

			@if(session('success'))
				<div class="alert alert-success">{{ session('success') }}</div>
			@endif

			@if($errors->any())
				<div class="alert alert-danger">
					<ul class="mb-0">
						@foreach($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
			@endif

			<div class="d-flex flex-wrap gap-2 mb-3">
				<a href="{{ route('admin.retraining') }}#dataset-selection" class="btn btn-dark">
					<i class="fa-solid fa-rotate me-2"></i>Retrain Manual
				</a>
				<form action="{{ route('admin.models.runs.archive-inactive') }}" method="POST" onsubmit="return confirm('Hapus semua retrain model yang tidak sedang digunakan dari daftar aktif?')">
					@csrf
					<button class="btn btn-outline-danger" type="submit">
						<i class="fa-solid fa-trash-can me-2"></i>Hapus Semua Retrain Model
					</button>
				</form>
				<a href="{{ route('admin.retraining') }}" class="btn btn-outline-dark">
					<i class="fa-solid fa-list-check me-2"></i>Lihat Job Retrain
				</a>
			</div>

			<div class="filter-card d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<span class="text-muted small fw-bold">Model aktif saat ini:</span>
					<strong class="text-success">{{ $activeRun ? $versionLabel($activeRun) : 'Belum ada model aktif' }}</strong>
				</div>
				<span class="status-chip status-success">
					<i class="fa-solid fa-circle-check"></i>{{ $activeRun ? 'Sedang digunakan' : 'Fallback sistem' }}
				</span>
			</div>

			<div class="table-responsive">
				<table class="table admin-table responsive-table align-middle">
					<thead>
						<tr>
							<th>Versi Model</th>
							<th>Rata-rata Akurasi</th>
							<th>Status</th>
							<th class="text-end">Aksi</th>
						</tr>
					</thead>
					<tbody>
						@forelse($runs as $run)
							@php
								$label = $versionLabel($run);
								$avg = $averageAccuracy($run);
								$acceptedVersions = $run->modelVersions->where('status', '!=', 'rejected');
								$missingArtifact = $acceptedVersions->contains(fn ($version) => ! $version->artifact_model_path || ! is_file($version->artifact_model_path));
								$canUse = ! $run->is_active && $acceptedVersions->isNotEmpty() && ! $missingArtifact;
							@endphp
							<tr>
								<td data-label="Versi Model">
									<div class="entity-cell">
										<span class="entity-avatar">
											<i class="fa-solid fa-folder"></i>
										</span>
										<div>
											<div class="table-title">{{ $label }}</div>
											<div class="muted-line">
												{{ $run->stage === 'legacy_baseline' ? 'Baseline model awal' : 'Retraining #' . $run->id }}
												@if($run->is_active)
													<span class="status-chip status-success ms-2">Aktif</span>
												@endif
											</div>
										</div>
									</div>
								</td>
								<td data-label="Rata-rata Akurasi">
									<span class="metric-pill">{{ $formatMetric($avg) }}</span>
								</td>
								<td data-label="Status">
									@if($run->is_active)
										<span class="status-chip status-success">Sedang digunakan</span>
									@elseif($acceptedVersions->isEmpty())
										<span class="status-chip status-danger">Ditolak evaluasi</span>
									@elseif($missingArtifact)
										<span class="status-chip status-warning">Artefak hilang</span>
									@else
										<span class="text-muted">-</span>
									@endif
								</td>
								<td data-label="Aksi" class="text-end">
									<div class="action-stack">
										<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#runDetail{{ $run->id }}" aria-expanded="false">
											<i class="fa-solid fa-chart-simple me-1"></i>Detail
										</button>
										<form action="{{ route('admin.models.runs.activate', $run) }}" method="POST" onsubmit="return confirm('Gunakan retrain model ini untuk prediksi berikutnya?')">
											@csrf
											<button class="btn btn-sm btn-outline-dark" type="submit" @disabled(! $canUse)>
												Gunakan Retrain Ini
											</button>
										</form>
										<form action="{{ route('admin.models.runs.archive', $run) }}" method="POST" onsubmit="return confirm('Hapus retrain model ini dari daftar aktif?')">
											@csrf
											<button class="btn btn-sm btn-outline-danger" type="submit" @disabled($run->is_active || $run->stage === 'legacy_baseline')>
												Hapus
											</button>
										</form>
									</div>
								</td>
							</tr>
							<tr class="collapse detail-row" id="runDetail{{ $run->id }}">
								<td colspan="4">
									<div class="row g-3">
										<div class="col-lg-3">
											<div class="pool-mini-stat">
												<span>Dataset Dipakai</span>
												<strong>{{ count($run->selected_dataset_ids ?? []) }}</strong>
												<small>{{ optional($run->finished_at ?? $run->created_at)->format('d M Y H:i') }}</small>
											</div>
										</div>
										<div class="col-lg-9">
											<div class="table-responsive">
												<table class="table table-sm admin-table responsive-table align-middle mb-0">
													<thead>
														<tr>
															<th>Model</th>
															<th>Accuracy</th>
															<th>Recall</th>
															<th>F1-score</th>
															<th>Precision</th>
															<th>False Negative</th>
															<th>Status</th>
														</tr>
													</thead>
													<tbody>
														@foreach($run->modelVersions as $version)
															<tr>
																<td data-label="Model" class="fw-bold">{{ $version->model_name }}</td>
																<td data-label="Accuracy">{{ $formatMetric($metricValue($version, 'accuracy')) }}</td>
																<td data-label="Recall">{{ $formatMetric($metricValue($version, ['recall_stroke', 'recall'])) }}</td>
																<td data-label="F1-score">{{ $formatMetric($metricValue($version, ['f1_stroke', 'f1-score', 'f1_score'])) }}</td>
																<td data-label="Precision">{{ $formatMetric($metricValue($version, ['precision_stroke', 'precision'])) }}</td>
																<td data-label="False Negative">{{ $falseNegative($version) }}</td>
																<td data-label="Status">
																	<span class="status-chip status-{{ $statusTone($version->status) }}">{{ ucfirst($version->status) }}</span>
																</td>
															</tr>
														@endforeach
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="4" class="text-center text-muted py-5">Belum ada versi retrain model.</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
