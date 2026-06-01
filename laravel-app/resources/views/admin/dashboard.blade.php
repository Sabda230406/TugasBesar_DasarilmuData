@extends('layouts.admin')

@section('content')
	<style>
		.admin-shell {
			display: grid;
			gap: 1.5rem;
		}

		.admin-hero {
			border-radius: 22px;
			border: 1px solid rgba(22, 163, 74, 0.18);
			background:
				radial-gradient(circle at top right, rgba(34, 197, 94, 0.18), transparent 34%),
				linear-gradient(135deg, rgba(236, 253, 245, 0.96), rgba(255, 255, 255, 0.98));
			padding: 1.5rem;
		}

		.admin-stat {
			border: 1px solid var(--admin-line);
			border-radius: 18px;
			background: #fff;
			padding: 1.15rem;
			box-shadow: var(--shadow-sm);
			height: 100%;
		}

		.admin-stat span {
			display: block;
			color: var(--admin-muted);
			font-size: 0.78rem;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.08em;
		}

		.admin-stat strong {
			display: block;
			color: var(--admin-text);
			font-size: 1.8rem;
			font-weight: 800;
			margin-top: 0.35rem;
		}

		.admin-card {
			border: 1px solid var(--admin-line);
			border-radius: 18px;
			background: #fff;
			padding: 1.25rem;
			box-shadow: var(--shadow-sm);
			height: 100%;
		}

		.quick-action {
			display: flex;
			align-items: center;
			gap: 0.85rem;
			border: 1px solid var(--admin-line);
			border-radius: 16px;
			padding: 1rem;
			text-decoration: none;
			color: var(--admin-text);
			background: var(--admin-soft);
			font-weight: 800;
			transition: 0.2s ease;
		}

		.quick-action:hover {
			transform: translateY(-2px);
			border-color: rgba(22, 163, 74, 0.32);
			background: #ecfdf5;
			color: var(--admin-brand-deep);
		}

		.quick-action i {
			width: 42px;
			height: 42px;
			border-radius: 14px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: var(--admin-brand-soft);
			color: var(--admin-brand-deep);
		}

		.model-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			border-radius: 999px;
			padding: 0.45rem 0.7rem;
			font-weight: 800;
			font-size: 0.8rem;
			background: #eef7f1;
			color: #475569;
		}

		.model-pill.ready {
			background: var(--admin-brand-soft);
			color: var(--admin-brand-deep);
		}

		.table td,
		.table th {
			vertical-align: middle;
		}
	</style>

	<div class="admin-shell">
		<section class="admin-hero">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div>
					<p class="eyebrow mb-2">Admin Dashboard</p>
					<h1 class="fw-bold mb-2">Kontrol sistem StrokeRisk.</h1>
					<p class="section-subtitle mb-0">Kelola user, pantau retraining, dan ambil history prediksi user sebagai dataset retraining.</p>
				</div>
				<a href="{{ route('admin.history.export') }}" class="btn btn-dark">
					<i class="fa-solid fa-file-csv me-2"></i>Download CSV Retraining
				</a>
			</div>
		</section>

		<div class="row g-3">
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<span>Total User</span>
					<strong>{{ $stats['users'] }}</strong>
				</div>
			</div>
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<span>Total History</span>
					<strong>{{ $stats['histories'] }}</strong>
				</div>
			</div>
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<span>Data Retraining Valid</span>
					<strong>{{ $stats['validRetrainingRows'] }}</strong>
				</div>
			</div>
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<span>Model Tersedia</span>
					<strong>{{ $stats['readyModels'] }}/{{ $stats['totalModels'] }}</strong>
				</div>
			</div>
		</div>

		<div class="row g-4">
			<div class="col-lg-4">
				<div class="admin-card">
					<h2 class="h5 fw-bold mb-3">Menu Cepat</h2>
					<div class="d-grid gap-3">
						<a href="{{ route('admin.users') }}" class="quick-action">
							<i class="fa-solid fa-users-gear"></i>
							<span>Kelola User</span>
						</a>
						<a href="{{ route('admin.retraining') }}" class="quick-action">
							<i class="fa-solid fa-database"></i>
							<span>Kelola Retraining</span>
						</a>
						<a href="{{ route('admin.history.export') }}" class="quick-action">
							<i class="fa-solid fa-download"></i>
							<span>Download History untuk Retraining</span>
						</a>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="admin-card">
					<h2 class="h5 fw-bold mb-3">Status Retraining</h2>
					<div class="mb-3">
						<div class="d-flex justify-content-between fw-bold mb-2">
							<span>Progress Pool</span>
							<span>{{ $pool['progress'] }}%</span>
						</div>
						<div class="progress" style="height: 10px;">
							<div class="progress-bar bg-success" style="width: {{ $pool['progress'] }}%"></div>
						</div>
					</div>
					<p class="mb-2"><strong>Total valid:</strong> {{ $pool['total_rows'] }} / {{ $pool['min_total_rows'] }}</p>
					<p class="mb-2"><strong>Pasien tidak stroke:</strong> {{ $pool['stroke_0'] }} / {{ $pool['min_class_rows'] }}</p>
					<p class="mb-3"><strong>Pasien stroke:</strong> {{ $pool['stroke_1'] }} / {{ $pool['min_class_rows'] }}</p>
					<span class="status-badge">
						<i class="fa-solid {{ $pool['can_retrain'] ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
						{{ $pool['status_label'] }}
					</span>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="admin-card">
					<h2 class="h5 fw-bold mb-3">Model ML</h2>
					<div class="d-flex flex-wrap gap-2">
						@foreach($models as $model)
							<span class="model-pill {{ $model['available'] ? 'ready' : '' }}">
								<i class="fa-solid {{ $model['icon'] }}"></i>
								{{ $model['name'] }} {{ $model['available'] ? 'Siap' : 'Belum Ada' }}
							</span>
						@endforeach
					</div>
					<hr>
					<p class="section-subtitle mb-0">Model aktif tetap disimpan di folder <code>ml-api</code>. Backup otomatis dibuat saat retraining mengganti model aktif.</p>
				</div>
			</div>
		</div>

		<div class="admin-card">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
				<div>
					<h2 class="h5 fw-bold mb-1">History menjadi dataset retraining</h2>
					<p class="section-subtitle mb-0">
						Export CSV memakai format retraining langsung. Kolom <code>stroke</code> diisi dari hasil prediksi sistem yang tersimpan di history.
					</p>
				</div>
				<a href="{{ route('admin.retraining') }}" class="btn btn-outline-dark">
					<i class="fa-solid fa-database me-2"></i>Kelola Pool History
				</a>
			</div>
		</div>

		<div class="row g-4">
			<div class="col-lg-6">
				<div class="admin-card">
					<h2 class="h5 fw-bold mb-3">Dataset Retraining Terbaru</h2>
					<div class="table-responsive">
						<table class="table table-sm align-middle mb-0">
							<thead>
								<tr>
									<th>Sumber</th>
									<th>Valid</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								@forelse($latestDatasets as $dataset)
									<tr>
										<td>
											<div class="fw-bold">{{ $dataset->source_name }}</div>
											<div class="text-muted small">{{ $dataset->user?->name ?? 'User dihapus' }}</div>
										</td>
										<td>{{ $dataset->valid_rows }}</td>
										<td><span class="badge text-bg-light">{{ $dataset->status }}</span></td>
									</tr>
								@empty
									<tr>
										<td colspan="3" class="text-muted">Belum ada dataset retraining.</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="admin-card">
					<h2 class="h5 fw-bold mb-3">History Prediksi Terbaru</h2>
					<div class="table-responsive">
						<table class="table table-sm align-middle mb-0">
							<thead>
								<tr>
									<th>User</th>
									<th>Model</th>
									<th>Hasil</th>
								</tr>
							</thead>
							<tbody>
								@forelse($latestHistories as $history)
									<tr>
										<td>{{ $history->user?->name ?? 'User dihapus' }}</td>
										<td>{{ $history->model_name ?? '-' }}</td>
										<td>{{ (string) $history->prediction === '1' ? 'Risiko Tinggi' : 'Risiko Rendah' }}</td>
									</tr>
								@empty
									<tr>
										<td colspan="3" class="text-muted">Belum ada history prediksi.</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
