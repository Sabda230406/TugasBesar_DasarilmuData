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
	@endphp

	<style>
		.admin-page-head {
			border-radius: 20px;
			border: 1px solid rgba(22, 163, 74, 0.18);
			background:
				radial-gradient(circle at top right, rgba(34, 197, 94, 0.14), transparent 30%),
				linear-gradient(135deg, rgba(236, 253, 245, 0.96), rgba(255, 255, 255, 0.98));
			padding: 1.4rem;
			margin-bottom: 1.5rem;
		}

		.admin-panel {
			border: 1px solid var(--admin-line);
			border-radius: 18px;
			background: #fff;
			padding: 1.25rem;
			box-shadow: var(--shadow-sm);
		}

		.pool-mini-stat {
			border: 1px solid rgba(187, 247, 208, 0.86);
			border-radius: 16px;
			background: #f7fef9;
			padding: 1rem;
			height: 100%;
		}

		.pool-mini-stat span {
			display: block;
			color: var(--admin-brand-deep);
			font-size: 0.76rem;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.08em;
		}

		.pool-mini-stat strong {
			display: block;
			font-size: 1.7rem;
			font-weight: 800;
			color: var(--admin-text);
			margin-top: 0.25rem;
		}

		.pool-mini-stat strong.text-danger {
			color: var(--admin-brand-deep) !important;
		}

		.model-chip {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
			border-radius: 999px;
			padding: 0.5rem 0.8rem;
			font-weight: 800;
			font-size: 0.82rem;
			background: #eef7f1;
			color: #475569;
		}

		.model-chip.ready {
			background: var(--admin-brand-soft);
			color: var(--admin-brand-deep);
		}

		.table td,
		.table th {
			vertical-align: middle;
		}

		.preview-box,
		.error-box {
			max-width: 360px;
			white-space: nowrap;
			overflow: auto;
		}
	</style>

	<div class="admin-page-head">
		<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
			<div>
				<p class="eyebrow mb-2">Admin Retraining</p>
				<h1 class="fw-bold mb-2">Retraining dari history prediksi user.</h1>
				<p class="section-subtitle mb-0">History prediksi dapat diexport atau langsung dimasukkan ke pool retraining dengan label <code>stroke</code> dari hasil prediksi sistem.</p>
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
						<div class="text-muted small fw-bold">History</div>
						<div class="h4 fw-bold mb-0">{{ $historySummary['total_histories'] ?? 0 }}</div>
					</div>
					<div class="col-4">
						<div class="text-muted small fw-bold">Valid</div>
						<div class="h4 fw-bold mb-0">{{ $historySummary['valid_rows'] ?? 0 }}</div>
					</div>
					<div class="col-4">
						<div class="text-muted small fw-bold">Stroke 1</div>
						<div class="h4 fw-bold text-success mb-0">{{ $historySummary['stroke_1'] ?? 0 }}</div>
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
					Data history yang valid memakai fitur pasien dari input user dan label <code>stroke</code> dari hasil prediksi sistem.
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
				<form action="{{ route('retraining.start') }}" method="POST" class="mb-3">
					@csrf
					<button class="btn btn-dark w-100 py-2" type="submit" @disabled(! $pool['can_retrain'])>
						<i class="fa-solid fa-rotate me-2"></i>Mulai Retraining Semua Model
					</button>
				</form>
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
					<table class="table table-sm align-middle mb-0">
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
									<td class="fw-bold">{{ $modelResult['model_name'] ?? str_replace('_', ' ', $modelKey) }}</td>
									<td>{{ $formatMetric($metrics['accuracy'] ?? null) }}</td>
									<td>{{ $formatMetric($metrics['recall_stroke'] ?? null) }}</td>
									<td>{{ $formatMetric($metrics['f1_stroke'] ?? null) }}</td>
									<td>{{ $metrics['false_negative'] ?? '-' }}</td>
									<td>
										<span class="badge text-bg-{{ ($eligibility['eligible'] ?? true) ? 'success' : 'warning' }}">
											{{ ($eligibility['eligible'] ?? true) ? 'Layak' : 'Ditolak' }}
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

	<div class="admin-panel">
		<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
			<div>
				<h2 class="h5 fw-bold mb-1">Daftar Dataset/Input Retraining</h2>
				<p class="section-subtitle mb-0">Data valid dihitung ke pool, archived tidak dihitung, invalid disimpan sebagai catatan error.</p>
			</div>
		</div>

		<form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.retraining') }}">
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
				<button class="btn btn-dark" type="submit">Filter</button>
			</div>
		</form>

		<div class="table-responsive">
			<table class="table align-middle">
				<thead>
					<tr>
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
						<tr>
							<td class="fw-bold">#{{ $dataset->id }}</td>
							<td>
								<div class="fw-bold">{{ $dataset->source_name }}</div>
								<div class="text-muted small">
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
							</td>
							<td>{{ $dataset->user?->name ?? 'User dihapus' }}</td>
							<td>{{ optional($dataset->created_at)->format('d M Y H:i') }}</td>
							<td>{{ $dataset->valid_rows }}</td>
							<td>{{ $dataset->stroke_0 }}</td>
							<td>{{ $dataset->stroke_1 }}</td>
							<td><span class="badge text-bg-{{ $statusTone($dataset->status) }}">{{ $dataset->status }}</span></td>
							<td>
								<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#datasetDetail{{ $dataset->id }}" aria-expanded="false">
									Lihat
								</button>
							</td>
							<td class="text-end">
								@if(in_array($dataset->status, ['Valid', 'Invalid'], true))
									<form action="{{ route('admin.retraining.archive', $dataset) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive dataset ini? Data tidak dihapus, hanya tidak dihitung ke pool.')">
										@csrf
										<button class="btn btn-sm btn-outline-secondary" type="submit">
											<i class="fa-solid fa-box-archive me-1"></i>Archive
										</button>
									</form>
								@elseif($dataset->status === 'Archived')
									<form action="{{ route('admin.retraining.restore', $dataset) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore dataset ini ke pool valid?')">
										@csrf
										<button class="btn btn-sm btn-outline-success" type="submit">
											<i class="fa-solid fa-rotate-left me-1"></i>Restore
										</button>
									</form>
								@else
									<span class="text-muted small">-</span>
								@endif
							</td>
						</tr>
						<tr class="collapse" id="datasetDetail{{ $dataset->id }}">
							<td colspan="10">
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
							<td colspan="10" class="text-center text-muted py-4">Belum ada dataset retraining.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		<div class="mt-3">
			{{ $datasets->links() }}
		</div>
	</div>
@endsection
