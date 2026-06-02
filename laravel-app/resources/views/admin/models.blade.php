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

		$runTitle = function ($run) {
			if ($run->stage === 'legacy_baseline') {
				return 'Baseline model awal';
			}

			return 'Retraining #' . $run->id;
		};
	@endphp

	<div class="admin-page-stack">
		<div class="admin-page-head">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div class="d-flex gap-3 align-items-start">
					<span class="admin-page-icon"><i class="fa-solid fa-layer-group"></i></span>
					<div>
						<p class="eyebrow mb-2">Registry Retraining</p>
						<h1 class="fw-bold mb-2">Pilih hasil retraining aktif</h1>
						<p class="section-subtitle mb-0">Admin memilih paket retraining yang dipakai sistem. Di dalam tiap paket ada metrik model yang dilatih.</p>
					</div>
				</div>
				<div class="d-flex flex-wrap gap-2">
					<a href="{{ route('admin.retraining') }}" class="btn btn-dark">
						<i class="fa-solid fa-rotate me-2"></i>Retraining
					</a>
					<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-dark">
						<i class="fa-solid fa-arrow-left me-2"></i>Dashboard
					</a>
				</div>
			</div>
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

		<div class="row g-3">
			<div class="col-lg-4">
				<div class="pool-mini-stat h-100">
					<span>Retraining Aktif</span>
					<strong>{{ $activeRun ? $runTitle($activeRun) : 'Belum diset' }}</strong>
					<small>{{ $activeRun?->activated_at?->format('d M Y H:i') ?? 'Sistem memakai fallback model yang tersedia.' }}</small>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="pool-mini-stat h-100">
					<span>Total Paket</span>
					<strong>{{ $runs->count() }}</strong>
					<small>Berisi baseline dan hasil retraining yang sudah completed.</small>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="pool-mini-stat h-100">
					<span>Model Dalam Paket Aktif</span>
					<strong>{{ $activeRun?->modelVersions?->where('status', '!=', 'rejected')->count() ?? 0 }}</strong>
					<small>Model rejected tidak ikut diaktifkan.</small>
				</div>
			</div>
		</div>

		@if($runs->isEmpty())
			<div class="admin-panel text-center py-5">
				<h2 class="h5 fw-bold">Belum ada hasil retraining.</h2>
				<p class="section-subtitle mb-0">Jalankan retraining dulu, nanti paket hasilnya muncul di sini lengkap dengan metrik modelnya.</p>
			</div>
		@endif

		@foreach($runs as $run)
			@php
				$acceptedVersions = $run->modelVersions->where('status', '!=', 'rejected');
				$hasMissingArtifact = $acceptedVersions->contains(fn ($version) => ! $version->artifact_model_path || ! is_file($version->artifact_model_path));
				$canActivate = ! $run->is_active && $acceptedVersions->isNotEmpty() && ! $hasMissingArtifact;
			@endphp
			<div class="admin-panel">
				<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
					<div>
						<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
							<p class="eyebrow mb-0">{{ $run->stage === 'legacy_baseline' ? 'Baseline' : 'Hasil Retraining' }}</p>
							@if($run->is_active)
								<span class="status-chip status-success"><i class="fa-solid fa-circle-check"></i>Aktif dipakai</span>
							@elseif($acceptedVersions->isEmpty())
								<span class="status-chip status-danger">Tidak lolos evaluasi</span>
							@else
								<span class="status-chip status-primary">Bisa dipilih</span>
							@endif
						</div>
						<h2 class="h5 fw-bold mb-1">{{ $runTitle($run) }}</h2>
						<p class="section-subtitle mb-0">
							{{ $run->message ?? 'Paket retraining selesai.' }}
							@if($run->user)
								<span class="d-inline-block ms-1">Oleh {{ $run->user->name }}.</span>
							@endif
						</p>
					</div>
					<form action="{{ route('admin.models.runs.activate', $run) }}" method="POST" onsubmit="return confirm('Jadikan paket retraining ini sebagai model aktif sistem?')">
						@csrf
						<button class="btn btn-dark" type="submit" @disabled(! $canActivate)>
							<i class="fa-solid fa-power-off me-2"></i>Aktifkan Paket
						</button>
					</form>
				</div>

				<div class="row g-3 mb-3">
					<div class="col-md-3">
						<div class="pool-mini-stat">
							<span>Dataset</span>
							<strong>{{ count($run->selected_dataset_ids ?? []) }}</strong>
							<small>ID dataset yang dipakai retraining.</small>
						</div>
					</div>
					<div class="col-md-3">
						<div class="pool-mini-stat">
							<span>Model Lolos</span>
							<strong>{{ $acceptedVersions->count() }}</strong>
							<small>Dari {{ $run->modelVersions->count() }} model tersimpan.</small>
						</div>
					</div>
					<div class="col-md-3">
						<div class="pool-mini-stat">
							<span>Selesai</span>
							<strong>{{ optional($run->finished_at ?? $run->created_at)->format('d M') }}</strong>
							<small>{{ optional($run->finished_at ?? $run->created_at)->format('Y H:i') }}</small>
						</div>
					</div>
					<div class="col-md-3">
						<div class="pool-mini-stat">
							<span>Status Paket</span>
							<strong>{{ $run->is_active ? 'Aktif' : 'Idle' }}</strong>
							<small>{{ $run->activated_at?->format('d M Y H:i') ?? 'Belum dijadikan aktif.' }}</small>
						</div>
					</div>
				</div>

				<div class="table-responsive">
					<table class="table admin-table responsive-table align-middle mb-0">
						<thead>
							<tr>
								<th>Model</th>
								<th>Accuracy</th>
								<th>Recall</th>
								<th>F1-score</th>
								<th>Precision</th>
								<th>False Negative</th>
								<th>Status</th>
								<th>Versi Artefak</th>
							</tr>
						</thead>
						<tbody>
							@foreach($run->modelVersions as $version)
								@php
									$missingArtifact = ! $version->artifact_model_path || ! is_file($version->artifact_model_path);
								@endphp
								<tr>
									<td data-label="Model">
										<div class="table-title">{{ $version->model_name }}</div>
										@if($version->is_default)
											<div class="muted-line text-success fw-bold">Default pilihan model</div>
										@endif
									</td>
									<td data-label="Accuracy">{{ $formatMetric($metricValue($version, 'accuracy')) }}</td>
									<td data-label="Recall">{{ $formatMetric($metricValue($version, ['recall_stroke', 'recall'])) }}</td>
									<td data-label="F1-score">{{ $formatMetric($metricValue($version, ['f1_stroke', 'f1-score', 'f1_score'])) }}</td>
									<td data-label="Precision">{{ $formatMetric($metricValue($version, ['precision_stroke', 'precision'])) }}</td>
									<td data-label="False Negative">{{ $falseNegative($version) }}</td>
									<td data-label="Status">
										<span class="status-chip status-{{ $statusTone($version->status) }}">{{ ucfirst($version->status) }}</span>
										@if($missingArtifact)
											<span class="status-chip status-warning mt-1">Artefak hilang</span>
										@endif
									</td>
									<td data-label="Versi Artefak">
										<div class="muted-line">{{ $version->version_uid }}</div>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>

				@if($hasMissingArtifact)
					<div class="alert alert-warning mt-3 mb-0">Ada artefak model yang hilang, jadi paket ini belum bisa diaktifkan.</div>
				@endif
			</div>
		@endforeach
	</div>
@endsection
