@extends('layouts.admin')

@section('content')
	@php
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

		$versionLabel = function ($version) {
			$run = $version->retrainingRun;
			if (($run?->stage === 'legacy_baseline') || str_starts_with($version->version_uid, 'legacy-')) {
				return 'MODEL_BASELINE';
			}

			$timestamp = optional($version->retrained_at ?? $run?->finished_at ?? $version->created_at)->format('Ymd_His');

			return 'MODEL_' . ($timestamp ?: $version->id);
		};

		$runLabel = function ($version) {
			$run = $version->retrainingRun;
			if (! $run) {
				return 'Tanpa run';
			}

			return $run->stage === 'legacy_baseline' ? 'Baseline awal' : 'Retraining #' . $run->id;
		};

		$artifactMissing = function ($version) {
			return ! $version->artifact_model_path
				|| ! is_file($version->artifact_model_path)
				|| ! $version->artifact_features_path
				|| ! is_file($version->artifact_features_path);
		};

		$isAllModels = $selectedModelKey === 'all';
		$selectedModelName = $isAllModels ? 'Semua Model' : ($selectedModel['name'] ?? 'Model');
		$selectedModelIcon = $isAllModels ? 'fa-layer-group' : ($selectedModel['icon'] ?? 'fa-brain');
		$selectedActiveVersion = $isAllModels ? null : ($activeVersions[$selectedModelKey] ?? null);
		$activeModelCount = $activeVersions->count();
		$activeFilterQuery = array_filter($filters, fn ($value) => filled($value));
		$hasModelFilters = count($activeFilterQuery) > 0;
	@endphp

	<style>
		.model-switcher {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 0.75rem;
		}

		.model-switch {
			display: grid;
			grid-template-columns: 42px minmax(0, 1fr) auto;
			align-items: center;
			gap: 0.75rem;
			min-height: 72px;
			min-width: 0;
			border: 1px solid var(--admin-line);
			border-radius: 8px;
			background: #fff;
			color: var(--admin-text);
			text-decoration: none;
			padding: 0.85rem;
			overflow: hidden;
			transition: 0.16s ease;
		}

		.model-switch:hover,
		.model-switch.is-active {
			border-color: rgba(15, 143, 114, 0.38);
			background: #f0fbf7;
			color: var(--admin-brand-deep);
		}

		.model-switch-icon {
			width: 42px;
			height: 42px;
			border-radius: 8px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: var(--admin-brand-soft);
			color: var(--admin-brand-deep);
			flex: 0 0 auto;
		}

		.model-switch > .flex-grow-1 {
			min-width: 0;
			overflow: hidden;
		}

		.model-switch .table-title,
		.model-switch .muted-line {
			display: block;
			max-width: 100%;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.model-switch .status-chip {
			justify-self: end;
			max-width: 100%;
		}

		.model-list-card {
			border: 1px solid var(--admin-line);
			border-radius: 8px;
			background: #fff;
			box-shadow: var(--shadow-sm);
			overflow: hidden;
		}

		.model-list-head {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 1rem;
			padding: 1rem;
			background: linear-gradient(135deg, #fbfefd, #f0fbf7);
			border-bottom: 1px solid var(--admin-line);
		}

		.model-list-title {
			display: flex;
			align-items: center;
			gap: 0.8rem;
		}

		.model-list-body {
			padding: 0.4rem 1rem 1rem;
		}

		.activate-check {
			width: 1.25rem;
			height: 1.25rem;
			cursor: pointer;
		}

		.activate-check:disabled {
			cursor: not-allowed;
		}

		.model-active-cell {
			width: 58px;
			text-align: center;
		}

		.version-note {
			max-width: 380px;
		}

		.score-cell {
			min-width: 96px;
			font-weight: 800;
			color: var(--admin-text);
			white-space: nowrap;
		}

		.model-pagination {
			margin-top: 1rem;
		}

		@media (max-width: 991.98px) {
			.model-switcher {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 767.98px) {
			.model-list-head {
				display: grid;
			}

			.score-cell {
				min-width: 0;
			}
		}
	</style>

	<div class="admin-page-stack">
		<div class="admin-panel">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
				<div class="d-flex gap-3 align-items-start">
					<span class="stat-icon"><i class="fa-solid fa-brain"></i></span>
					<div>
						<p class="eyebrow mb-2">Model Aktif</p>
						<h1 class="h4 fw-bold mb-1">Manajemen versi model</h1>
						<p class="section-subtitle mb-0">Pilih satu model untuk melihat dan mengaktifkan versi yang dibutuhkan.</p>
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

			<div class="d-flex flex-wrap gap-2">
				<a href="{{ route('admin.retraining') }}#dataset-selection" class="btn btn-dark">
					<i class="fa-solid fa-rotate me-2"></i>Retrain Manual
				</a>
				<form action="{{ route('admin.models.runs.archive-inactive') }}" method="POST" onsubmit="return confirm('Hapus semua retrain model yang tidak sedang digunakan dari daftar aktif?')">
					@csrf
					<button class="btn btn-outline-danger" type="submit">
						<i class="fa-solid fa-trash-can me-2"></i>Hapus Retrain Tidak Aktif
					</button>
				</form>
				<a href="{{ route('admin.retraining') }}" class="btn btn-outline-dark">
					<i class="fa-solid fa-list-check me-2"></i>Lihat Job Retrain
				</a>
			</div>
		</div>

		<div class="model-switcher" aria-label="Pilih model">
			<a class="model-switch {{ $isAllModels ? 'is-active' : '' }}" href="{{ route('admin.models', array_merge(['model' => 'all'], $activeFilterQuery)) }}">
				<span class="model-switch-icon"><i class="fa-solid fa-layer-group"></i></span>
				<span class="flex-grow-1">
					<span class="table-title d-block">Semua Model</span>
					<span class="muted-line">{{ $activeModelCount }} model aktif</span>
				</span>
				<span class="status-chip status-primary">Gabungan</span>
			</a>
			@foreach($modelOptions as $modelKey => $model)
				@php
					$activeVersion = $activeVersions[$modelKey] ?? null;
					$isSelected = $selectedModelKey === $modelKey;
				@endphp
				<a class="model-switch {{ $isSelected ? 'is-active' : '' }}" href="{{ route('admin.models', array_merge(['model' => $modelKey], $activeFilterQuery)) }}">
					<span class="model-switch-icon"><i class="fa-solid {{ $model['icon'] }}"></i></span>
					<span class="flex-grow-1">
						<span class="table-title d-block">{{ $model['name'] }}</span>
						<span class="muted-line">{{ $activeVersion ? $versionLabel($activeVersion) : 'Belum aktif' }}</span>
					</span>
					@if($activeVersion)
						<span class="status-chip status-success">Aktif</span>
					@else
						<span class="status-chip status-warning">Kosong</span>
					@endif
				</a>
			@endforeach
		</div>

		<form class="filter-card row g-3 align-items-end" method="GET" action="{{ route('admin.models') }}">
			<input type="hidden" name="model" value="{{ $selectedModelKey }}">
			<div class="col-lg-3 col-md-6">
				<label class="form-label fw-bold" for="model-usage">Penggunaan</label>
				<select id="model-usage" class="form-select" name="usage">
					<option value="">Semua</option>
					@foreach($usageFilters as $usageValue => $usageLabel)
						<option value="{{ $usageValue }}" @selected($filters['usage'] === $usageValue)>{{ $usageLabel }}</option>
					@endforeach
				</select>
			</div>
			<div class="col-lg-3 col-md-6">
				<label class="form-label fw-bold" for="model-readiness">Kelayakan</label>
				<select id="model-readiness" class="form-select" name="readiness">
					<option value="">Semua</option>
					@foreach($readinessFilters as $readinessValue => $readinessLabel)
						<option value="{{ $readinessValue }}" @selected($filters['readiness'] === $readinessValue)>{{ $readinessLabel }}</option>
					@endforeach
				</select>
			</div>
			<div class="col-lg-3 col-md-6">
				<label class="form-label fw-bold" for="model-time">Periode</label>
				<select id="model-time" class="form-select" name="time">
					<option value="">Semua waktu</option>
					@foreach($timeFilters as $timeValue => $timeLabel)
						<option value="{{ $timeValue }}" @selected($filters['time'] === $timeValue)>{{ $timeLabel }}</option>
					@endforeach
				</select>
			</div>
			<div class="col-lg-3 col-md-6 d-grid gap-2">
				<button class="btn btn-dark" type="submit">
					<i class="fa-solid fa-filter me-2"></i>Filter
				</button>
				@if($hasModelFilters)
					<a class="btn btn-outline-dark" href="{{ route('admin.models', ['model' => $selectedModelKey]) }}">Reset</a>
				@endif
			</div>
		</form>

		<div class="model-list-card" id="model-list">
			<div class="model-list-head">
				<div class="model-list-title">
					<span class="model-switch-icon"><i class="fa-solid {{ $selectedModelIcon }}"></i></span>
					<div>
						<p class="eyebrow mb-1">{{ $isAllModels ? 'semua_model' : $selectedModelKey }}</p>
						<h2 class="h5 fw-bold mb-1">{{ $selectedModelName }}</h2>
						<p class="section-subtitle mb-0">{{ $versions->total() }} {{ $hasModelFilters ? 'versi sesuai filter' : 'versi tersedia' }}</p>
					</div>
				</div>
				@if($isAllModels)
					<span class="status-chip status-primary">
						<i class="fa-solid fa-layer-group"></i>{{ $activeModelCount }} model aktif
					</span>
				@elseif($selectedActiveVersion)
					<span class="status-chip status-success">
						<i class="fa-solid fa-circle-check"></i>Aktif: {{ $versionLabel($selectedActiveVersion) }}
					</span>
				@else
					<span class="status-chip status-warning">
						<i class="fa-solid fa-circle-exclamation"></i>Belum aktif
					</span>
				@endif
			</div>

			<div class="model-list-body">
				<div class="table-responsive">
					<table class="table admin-table responsive-table align-middle mb-0">
						<thead>
							<tr>
								<th class="text-center">Aktif</th>
								<th>Model</th>
								<th>Versi</th>
								<th>Accuracy</th>
								<th>Precision</th>
								<th>Recall</th>
								<th>F1-score</th>
								<th>False Negative</th>
								<th>Status</th>
								<th>Run</th>
							</tr>
						</thead>
						<tbody>
							@forelse($versions as $version)
								@php
									$missingArtifact = $artifactMissing($version);
									$canActivate = ! $version->is_active && ! $missingArtifact;
									$status = $missingArtifact && ! $version->is_active ? 'artifact_missing' : $version->status;
									$statusClass = $status === 'artifact_missing' ? 'warning' : $statusTone($version->status);
									$statusLabel = match ($status) {
										'active' => 'Active',
										'available' => 'Available',
										'rejected' => 'Rejected',
										'artifact_missing' => 'Artefak hilang',
										default => ucfirst((string) $status),
									};
									$reasons = $version->eligibility['reasons'] ?? [];
									$rowModel = $modelOptions->get($version->model_key, [
										'name' => $version->model_name,
										'icon' => 'fa-brain',
									]);
								@endphp
								<tr>
									<td data-label="Aktif" class="model-active-cell">
										@if($version->is_active)
											<input class="form-check-input activate-check" type="checkbox" checked disabled aria-label="{{ $versionLabel($version) }} aktif">
										@elseif($canActivate)
											<form action="{{ route('admin.models.versions.activate', $version) }}" method="POST" onsubmit="return confirm('Aktifkan {{ $rowModel['name'] }} versi {{ $versionLabel($version) }}?')">
												@csrf
												<input class="form-check-input activate-check" type="checkbox" aria-label="Aktifkan {{ $versionLabel($version) }}" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
											</form>
										@else
											<input class="form-check-input activate-check" type="checkbox" disabled aria-label="{{ $versionLabel($version) }} tidak bisa diaktifkan">
										@endif
									</td>
									<td data-label="Model">
										<div class="entity-cell">
											<span class="entity-avatar">
												<i class="fa-solid {{ $rowModel['icon'] }}"></i>
											</span>
											<div>
												<div class="table-title">{{ $rowModel['name'] }}</div>
												<div class="muted-line">{{ $version->model_key }}</div>
											</div>
										</div>
									</td>
									<td data-label="Versi">
										<div class="table-title">{{ $versionLabel($version) }}</div>
										<div class="muted-line version-note">
											{{ $version->version_uid }}
											@if($version->is_default)
												<span class="status-chip status-primary ms-1">Default pilihan</span>
											@endif
											@if(! empty($reasons))
												<div>{{ implode(' ', array_slice($reasons, 0, 2)) }}</div>
											@endif
										</div>
									</td>
									<td data-label="Accuracy" class="score-cell">
										{{ $formatMetric($metricValue($version, 'accuracy')) }}
									</td>
									<td data-label="Precision" class="score-cell">
										{{ $formatMetric($metricValue($version, ['precision_stroke', 'precision'])) }}
									</td>
									<td data-label="Recall" class="score-cell">
										{{ $formatMetric($metricValue($version, ['recall_stroke', 'recall'])) }}
									</td>
									<td data-label="F1-score" class="score-cell">
										{{ $formatMetric($metricValue($version, ['f1_stroke', 'f1-score', 'f1_score'])) }}
									</td>
									<td data-label="False Negative" class="score-cell">
										{{ $falseNegative($version) }}
									</td>
									<td data-label="Status">
										<span class="status-chip status-{{ $statusClass }}">{{ $statusLabel }}</span>
									</td>
									<td data-label="Run">
										<div class="table-title">{{ $runLabel($version) }}</div>
										<div class="muted-line">
											{{ optional($version->retrained_at ?? $version->created_at)->format('d M Y H:i') }}
										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="10" class="text-center text-muted py-4">
										{{ $hasModelFilters ? 'Tidak ada versi yang cocok dengan filter.' : 'Belum ada versi model.' }}
									</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>

				@if($versions->hasPages())
					<div class="model-pagination">
						{{ $versions->links('vendor.pagination.bootstrap-5', ['itemName' => 'versi', 'ariaLabel' => 'Navigasi versi model']) }}
					</div>
				@elseif($versions->total() > 0)
					<div class="model-pagination">
						<div class="app-pagination-summary">
							{{ $versions->total() }} versi ditampilkan
						</div>
					</div>
				@endif
			</div>
		</div>
	</div>
@endsection
