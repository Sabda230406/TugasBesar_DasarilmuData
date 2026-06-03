@extends('layouts.admin')

@section('content')
	@php
		$statusTone = function ($status) {
			return match ($status) {
				'Valid' => 'success',
				'Invalid' => 'danger',
				'Used for Retraining' => 'primary',
				'Archived' => 'secondary',
				default => 'light',
			};
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

		$hasAvailableModel = collect($models)->contains(fn ($model) => $model['available']);
		$canStartRetraining = ($pool['data_ready'] ?? false)
			&& ! ($pool['training_in_progress'] ?? false)
			&& $hasAvailableModel;
	@endphp

	<div class="admin-page-stack">
	<div class="admin-page-head">
		<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
			<div class="d-flex gap-3 align-items-start">
				<span class="admin-page-icon"><i class="fa-solid fa-database"></i></span>
				<div>
					<p class="eyebrow mb-2">Admin Retraining</p>
					<h1 class="fw-bold mb-2">Retraining dari history prediksi user</h1>
					<p class="section-subtitle mb-0">Kelola pool data, validasi input, dan jalankan retraining dari history prediksi yang sudah tersimpan.</p>
				</div>
			</div>
			<div class="d-flex flex-wrap gap-2">
				<a href="{{ route('admin.history.export') }}" class="btn btn-dark">
					<i class="fa-solid fa-file-csv me-2"></i>Download CSV Retraining
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

	@if(session('warning'))
		<div class="alert alert-warning">{{ session('warning') }}</div>
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

	@if($latestRun)
		<div class="admin-panel" id="retrainingProgressPanel" data-status-url="{{ route('admin.retraining.runs.show', $latestRun) }}">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div class="d-flex gap-3 align-items-start">
					<span class="training-loader {{ $latestRun->status === 'completed' ? 'done' : ($latestRun->status === 'failed' ? 'failed' : '') }}" id="trainingLoader">
						@if($latestRun->status === 'completed')
							<i class="fa-solid fa-check"></i>
						@elseif($latestRun->status === 'failed')
							<i class="fa-solid fa-xmark"></i>
						@endif
					</span>
					<div>
						<p class="eyebrow mb-2">Progress Retraining #{{ $latestRun->id }}</p>
						<h2 class="h5 fw-bold mb-1" id="runStageLabel">{{ $latestRun->message ?? 'Menunggu status retraining.' }}</h2>
						<p class="section-subtitle mb-0">Proses tetap berjalan meskipun admin pindah halaman.</p>
					</div>
				</div>
				<span class="status-chip status-{{ $latestRun->status === 'failed' ? 'danger' : ($latestRun->status === 'completed' ? 'success' : 'primary') }}" id="runStatusLabel">{{ ucfirst($latestRun->status) }}</span>
			</div>
			<div class="progress mt-3" style="height: 10px;">
				<div class="progress-bar bg-success" id="runProgressBar" style="width: {{ $latestRun->progress }}%"></div>
			</div>
			<div class="d-flex justify-content-between mt-2 small fw-bold text-muted">
				<span id="runStageName">{{ $latestRun->stage ?? '-' }}</span>
				<span id="runProgressText">{{ $latestRun->progress }}%</span>
			</div>
			<div class="progress-step-list" id="progressStepList">
				<span class="progress-step" data-stage="queued">Queued</span>
				<span class="progress-step" data-stage="preparing_dataset">Dataset</span>
				<span class="progress-step" data-stage="training_decision_tree">Decision Tree</span>
				<span class="progress-step" data-stage="training_knn">KNN</span>
				<span class="progress-step" data-stage="training_svm">SVM</span>
				<span class="progress-step" data-stage="evaluating">Evaluasi</span>
				<span class="progress-step" data-stage="activating_model">Aktivasi</span>
				<span class="progress-step" data-stage="completed">Selesai</span>
			</div>
		</div>
	@endif

	<div class="row g-4 mb-4">
		<div class="col-lg-8">
			<div class="admin-panel h-100">
				<div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
					<div>
						<h2 class="h5 fw-bold mb-1">Ringkasan Pool Data</h2>
						<p class="section-subtitle mb-0">Syarat: minimal 50 data valid, 10 pasien tidak stroke, dan 10 pasien stroke.</p>
					</div>
					<span class="status-badge">
						<i class="fa-solid {{ $pool['can_retrain'] ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
						{{ $pool['status_label'] }}
					</span>
				</div>

				<div class="row g-3 mb-3">
					<div class="col-md-4">
						<div class="pool-mini-stat">
							<span>Total Valid</span>
							<strong>{{ $pool['total_rows'] }}</strong>
							<small class="text-muted">Target {{ $pool['min_total_rows'] }} data</small>
						</div>
					</div>
					<div class="col-md-4">
						<div class="pool-mini-stat">
							<span>Pasien Tidak Stroke</span>
							<strong>{{ $pool['stroke_0'] }}</strong>
							<small class="text-muted">Minimal {{ $pool['min_class_rows'] }} data</small>
						</div>
					</div>
					<div class="col-md-4">
						<div class="pool-mini-stat">
							<span>Pasien Stroke</span>
							<strong class="text-danger">{{ $pool['stroke_1'] }}</strong>
							<small class="text-muted">Minimal {{ $pool['min_class_rows'] }} data</small>
						</div>
					</div>
				</div>

				<div class="d-flex justify-content-between fw-bold mb-2">
					<span>Progress pool</span>
					<span>{{ $pool['progress'] }}%</span>
				</div>
				<div class="progress mb-3" style="height: 10px;">
					<div class="progress-bar bg-success" style="width: {{ $pool['progress'] }}%"></div>
				</div>

				@if(! $pool['can_retrain'])
					<div class="alert alert-danger mb-0">
						<strong>Belum siap.</strong>
						{{ implode(' ', $pool['missing_messages']) ?: 'Lengkapi data dan model terlebih dahulu.' }}
					</div>
				@else
					<div class="alert alert-success mb-0">Data dan model sudah siap untuk retraining.</div>
				@endif
			</div>
		</div>

		<div class="col-lg-4">
			<div class="admin-panel h-100">
				<h2 class="h5 fw-bold mb-3">Ambil dari History</h2>
				<div class="row g-2 mb-3">
					<div class="col-4">
						<div class="text-muted small fw-bold">Baru</div>
						<div class="h4 fw-bold mb-0">{{ $historySummary['valid_rows'] ?? 0 }}</div>
					</div>
					<div class="col-4">
						<div class="text-muted small fw-bold">Masuk Pool</div>
						<div class="h4 fw-bold mb-0">{{ $historySummary['imported_rows'] ?? 0 }}</div>
					</div>
					<div class="col-4">
						<div class="text-muted small fw-bold">Terpakai</div>
						<div class="h4 fw-bold text-success mb-0">{{ $historySummary['used_rows'] ?? 0 }}</div>
					</div>
				</div>
				<form action="{{ route('admin.retraining.history.import') }}" method="POST" class="mb-3" onsubmit="return confirm('Masukkan seluruh history prediksi valid ke pool retraining?')">
					@csrf
					<button class="btn btn-dark w-100 py-2" type="submit" @disabled(($historySummary['valid_rows'] ?? 0) <= 0)>
						<i class="fa-solid fa-database me-2"></i>Masukkan History ke Pool
					</button>
				</form>
				<a href="{{ route('admin.history.export') }}" class="btn btn-outline-dark w-100 mb-3">
					<i class="fa-solid fa-download me-2"></i>Download CSV History
				</a>
				<div class="small text-muted mb-4">
					Hanya history baru yang belum pernah masuk pool yang bisa di-import atau di-download sebagai CSV retraining.
				</div>

				<hr>

				<h2 class="h5 fw-bold mb-3">Kontrol Retraining</h2>
				<div class="d-flex flex-wrap gap-2 mb-3">
					@foreach($models as $model)
						<span class="model-chip {{ $model['available'] ? 'ready' : '' }}">
							<i class="fa-solid {{ $model['icon'] }}"></i>
							{{ $model['name'] }} {{ $model['available'] ? 'Siap' : 'Belum Ada' }}
						</span>
					@endforeach
				</div>
				<a href="#dataset-selection" class="btn btn-dark w-100 py-2 mb-3 {{ ($pool['training_in_progress'] ?? false) ? 'disabled' : '' }}">
					<i class="fa-solid fa-list-check me-2"></i>Pilih Dataset Retraining
				</a>
				@if($pool['training_in_progress'] ?? false)
					<form action="{{ route('admin.retraining.reset-lock') }}" method="POST" class="mb-3" onsubmit="return confirm('Reset status training? Pakai ini hanya kalau proses sebelumnya error/timeout dan Flask sudah tidak memproses retraining.')">
						@csrf
						<button class="btn btn-outline-danger w-100 py-2" type="submit">
							<i class="fa-solid fa-unlock-keyhole me-2"></i>Reset Status Training
						</button>
					</form>
				@endif
				<div class="small text-muted">
					Jika status tetap <strong>Sedang training</strong> setelah fatal error atau timeout, gunakan reset hanya setelah yakin Flask sudah berhenti memproses.
					Backup model lama tersimpan di <code>ml-api/backup_models/</code>.
				</div>
			</div>
		</div>
	</div>

	@if($result)
		<div class="admin-panel mb-4">
			<h2 class="h5 fw-bold mb-3">Hasil Retraining Terakhir</h2>
			<div class="alert {{ ($result['status'] ?? '') === 'error' ? 'alert-danger' : (($result['activated'] ?? false) ? 'alert-success' : 'alert-warning') }}">
				{{ $result['message'] ?? 'Retraining selesai.' }}
			</div>
			@if(! empty($result['models']) && is_array($result['models']))
				<div class="table-responsive">
					<table class="table table-sm admin-table responsive-table align-middle mb-0">
						<thead>
							<tr>
								<th>Model</th>
								<th>Accuracy</th>
								<th>Recall Stroke</th>
								<th>F1 Stroke</th>
								<th>False Negative</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							@foreach($result['models'] as $modelKey => $modelResult)
								@php
									$metrics = $modelResult['metrics'] ?? [];
									$eligibility = $modelResult['eligibility'] ?? [];
								@endphp
								<tr>
									<td data-label="Model" class="fw-bold">{{ $modelResult['model_name'] ?? str_replace('_', ' ', $modelKey) }}</td>
									<td data-label="Accuracy">{{ $formatMetric($metrics['accuracy'] ?? null) }}</td>
									<td data-label="Recall Stroke">{{ $formatMetric($metrics['recall_stroke'] ?? null) }}</td>
									<td data-label="F1 Stroke">{{ $formatMetric($metrics['f1_stroke'] ?? null) }}</td>
									<td data-label="False Negative">{{ $metrics['false_negative'] ?? '-' }}</td>
									<td data-label="Status">
										<span class="status-chip status-{{ ($eligibility['accepted'] ?? true) ? 'success' : 'warning' }}">
											{{ ($eligibility['accepted'] ?? true) ? 'Layak' : 'Ditolak' }}
										</span>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			@endif
		</div>
	@endif

	<div class="admin-panel" id="dataset-selection">
		<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
			<div class="d-flex gap-3 align-items-start">
				<span class="stat-icon"><i class="fa-solid fa-table-list"></i></span>
				<div>
					<h2 class="h5 fw-bold mb-1">Daftar Dataset/Input Retraining</h2>
					<p class="section-subtitle mb-0">Pilih dataset Valid yang mau digabung untuk retraining. Dataset Used, Invalid, dan Archived tidak bisa dipilih.</p>
				</div>
			</div>
			<div class="d-flex flex-wrap gap-2">
				<a href="{{ route('admin.models') }}" class="btn btn-outline-dark">
					<i class="fa-solid fa-brain me-2"></i>Lihat Model
				</a>
				<button class="btn btn-dark" type="submit" form="retrainingStartForm" @disabled(! $canStartRetraining)>
					<i class="fa-solid fa-play me-2"></i>Mulai Retraining
				</button>
			</div>
		</div>

		<form class="filter-card row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.retraining') }}">
			<div class="col-md-4">
				<label class="form-label fw-bold" for="search">Cari sumber</label>
				<input id="search" class="form-control" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama file atau input">
			</div>
			<div class="col-md-3">
				<label class="form-label fw-bold" for="status">Status</label>
				<select id="status" class="form-select" name="status">
					<option value="">Semua status</option>
					@foreach($statuses as $status)
						<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
					@endforeach
				</select>
			</div>
			<div class="col-md-3">
				<label class="form-label fw-bold" for="source">Sumber</label>
				<select id="source" class="form-select" name="source">
					<option value="">Semua sumber</option>
					<option value="upload" @selected($filters['source'] === 'upload')>Upload File</option>
					<option value="manual" @selected($filters['source'] === 'manual')>Input Manual</option>
					<option value="history" @selected($filters['source'] === 'history')>History Prediksi</option>
				</select>
			</div>
			<div class="col-md-2 d-grid">
				<button class="btn btn-dark" type="submit">
					<i class="fa-solid fa-filter me-2"></i>Filter
				</button>
			</div>
		</form>

		<form id="retrainingStartForm" action="{{ route('admin.retraining.start') }}" method="POST">
			@csrf

			<div class="filter-card mb-4">
				<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
					<div>
						<h3 class="h6 fw-bold mb-1">Model yang dilatih</h3>
						<p class="section-subtitle mb-0">Model yang tersedia otomatis dipilih. Kamu bisa lepas centang kalau hanya ingin retraining model tertentu.</p>
					</div>
					<span class="status-chip status-primary"><span id="selectedDatasetCount">0</span> dataset dipilih</span>
				</div>
				<div class="d-flex flex-wrap gap-2 mt-3">
					@foreach($models as $modelKey => $model)
						<label class="model-chip {{ $model['available'] ? 'ready' : '' }}">
							<input class="form-check-input m-0" type="checkbox" name="models[]" value="{{ $modelKey }}" @checked($model['available']) @disabled(! $model['available'] || ($pool['training_in_progress'] ?? false))>
							<i class="fa-solid {{ $model['icon'] }}"></i>
							{{ $model['name'] }}
						</label>
					@endforeach
				</div>
			</div>

			<div class="table-responsive">
				<table class="table admin-table responsive-table align-middle">
					<thead>
						<tr>
							<th>Pilih</th>
							<th>ID</th>
							<th>Sumber</th>
							<th>Uploader</th>
							<th>Tanggal</th>
							<th>Valid</th>
							<th>Tidak Stroke</th>
							<th>Stroke</th>
							<th>Status</th>
							<th>Detail</th>
							<th class="text-end">Aksi</th>
						</tr>
					</thead>
					<tbody>
						@forelse($datasets as $dataset)
							@php
								$sourceIcon = match ($dataset->source_type) {
									'manual' => 'fa-keyboard',
									'history' => 'fa-clock-rotate-left',
									default => 'fa-file-arrow-up',
								};
								$canSelect = $dataset->status === 'Valid' && ! ($pool['training_in_progress'] ?? false);
							@endphp
							<tr>
								<td data-label="Pilih">
									<input class="form-check-input dataset-checkbox" type="checkbox" name="dataset_ids[]" value="{{ $dataset->id }}" @disabled(! $canSelect)>
								</td>
								<td data-label="ID" class="fw-bold">#{{ $dataset->id }}</td>
								<td data-label="Sumber">
									<div class="entity-cell">
										<span class="entity-avatar">
											<i class="fa-solid {{ $sourceIcon }}"></i>
										</span>
										<div>
											<div class="table-title">{{ $dataset->source_name }}</div>
											<div class="muted-line">
												@switch($dataset->source_type)
													@case('manual')
														Input manual
														@break
													@case('history')
														History prediksi
														@break
													@default
														Upload file
												@endswitch
											</div>
										</div>
									</div>
								</td>
								<td data-label="Uploader">{{ $dataset->user?->name ?? 'User dihapus' }}</td>
								<td data-label="Tanggal">{{ optional($dataset->created_at)->format('d M Y H:i') }}</td>
								<td data-label="Valid"><span class="metric-pill">{{ $dataset->valid_rows }}</span></td>
								<td data-label="Tidak Stroke"><span class="metric-pill">{{ $dataset->stroke_0 }}</span></td>
								<td data-label="Stroke"><span class="metric-pill">{{ $dataset->stroke_1 }}</span></td>
								<td data-label="Status">
									<span class="status-chip status-{{ $statusTone($dataset->status) }}">{{ $dataset->status }}</span>
								</td>
								<td data-label="Detail">
									<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#datasetDetail{{ $dataset->id }}" aria-expanded="false">
										<i class="fa-solid fa-eye me-1"></i>Lihat
									</button>
								</td>
								<td data-label="Aksi" class="text-end">
									@if(in_array($dataset->status, ['Valid', 'Invalid'], true))
										<button class="btn btn-sm btn-outline-secondary" type="submit" formaction="{{ route('admin.retraining.archive', $dataset) }}" formmethod="POST" onclick="return confirm('Archive dataset ini? Data tidak dihapus, hanya tidak dihitung ke pool.')">
											<i class="fa-solid fa-box-archive me-1"></i>Archive
										</button>
									@elseif($dataset->status === 'Archived')
										<button class="btn btn-sm btn-outline-success" type="submit" formaction="{{ route('admin.retraining.restore', $dataset) }}" formmethod="POST" onclick="return confirm('Restore dataset ini ke pool valid?')">
											<i class="fa-solid fa-rotate-left me-1"></i>Restore
										</button>
									@else
										<span class="text-muted small">-</span>
									@endif
								</td>
							</tr>
							<tr class="collapse detail-row" id="datasetDetail{{ $dataset->id }}">
								<td colspan="11">
									<div class="row g-3">
										<div class="col-lg-6">
											<h3 class="h6 fw-bold">Preview Dataset</h3>
											<pre class="preview-box bg-light border rounded p-3 mb-0">{{ json_encode($dataset->preview ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
										</div>
										<div class="col-lg-6">
											<h3 class="h6 fw-bold">Error Validasi</h3>
											<pre class="error-box bg-light border rounded p-3 mb-0">{{ json_encode($dataset->errors ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
										</div>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="11" class="text-center text-muted py-4">Belum ada dataset retraining.</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</form>

		<div class="mt-3">
			{{ $datasets->links() }}
		</div>
	</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const checkboxes = document.querySelectorAll('.dataset-checkbox');
			const selectedCount = document.getElementById('selectedDatasetCount');
			const updateSelectedCount = () => {
				if (! selectedCount) {
					return;
				}

				selectedCount.textContent = Array.from(checkboxes).filter((checkbox) => checkbox.checked).length;
			};
			checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelectedCount));
			updateSelectedCount();

			const panel = document.getElementById('retrainingProgressPanel');
			if (! panel) {
				return;
			}

			const statusUrl = panel.dataset.statusUrl;
			const stageLabel = document.getElementById('runStageLabel');
			const statusLabel = document.getElementById('runStatusLabel');
			const stageName = document.getElementById('runStageName');
			const progressText = document.getElementById('runProgressText');
			const progressBar = document.getElementById('runProgressBar');
			const loader = document.getElementById('trainingLoader');
			const steps = Array.from(document.querySelectorAll('.progress-step'));
			const stageOrder = [
				'queued',
				'preparing_dataset',
				'training_models',
				'training_decision_tree',
				'training_knn',
				'training_svm',
				'evaluating',
				'activating_model',
				'completed',
				'completed_not_activated',
				'failed',
			];

			const applyRun = (run) => {
				const status = run.status || 'queued';
				const stage = run.stage || status;
				const progress = Number(run.progress || 0);
				const currentIndex = stageOrder.indexOf(stage);

				stageLabel.textContent = run.message || 'Retraining sedang diproses.';
				statusLabel.textContent = status.charAt(0).toUpperCase() + status.slice(1);
				statusLabel.className = `status-chip status-${status === 'failed' ? 'danger' : (status === 'completed' ? 'success' : 'primary')}`;
				stageName.textContent = stage.replaceAll('_', ' ');
				progressText.textContent = `${progress}%`;
				progressBar.style.width = `${progress}%`;

				loader.className = `training-loader ${status === 'completed' ? 'done' : (status === 'failed' ? 'failed' : '')}`;
				loader.innerHTML = status === 'completed'
					? '<i class="fa-solid fa-check"></i>'
					: (status === 'failed' ? '<i class="fa-solid fa-xmark"></i>' : '');

				steps.forEach((step) => {
					const stepIndex = stageOrder.indexOf(step.dataset.stage);
					step.classList.toggle('active', step.dataset.stage === stage);
					step.classList.toggle('done', currentIndex > -1 && stepIndex > -1 && stepIndex < currentIndex);
				});
			};

			const pollRun = async () => {
				try {
					const response = await fetch(statusUrl);
					const payload = await response.json();
					if (payload.status !== 'success' || ! payload.run) {
						return;
					}

					applyRun(payload.run);
					if (['completed', 'failed'].includes(payload.run.status)) {
						clearInterval(interval);
					}
				} catch (error) {
					console.error(error);
				}
			};

			const interval = setInterval(pollRun, 2500);
			pollRun();
		});
	</script>
@endsection
