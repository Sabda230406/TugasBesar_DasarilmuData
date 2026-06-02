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
	@endphp

	<div class="admin-shell">
		<section class="admin-hero">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div class="d-flex gap-3 align-items-start">
					<span class="admin-page-icon"><i class="fa-solid fa-shield-heart"></i></span>
					<div>
						<p class="eyebrow mb-2">Admin Dashboard</p>
						<h1 class="fw-bold mb-2">Kontrol sistem StrokeRisk</h1>
						<p class="section-subtitle mb-0">Kelola user, pantau kesiapan retraining, dan ubah history prediksi menjadi dataset model.</p>
					</div>
				</div>
				<a href="{{ route('admin.history.export') }}" class="btn btn-dark">
					<i class="fa-solid fa-file-csv me-2"></i>Download CSV Retraining
				</a>
			</div>
		</section>

		<div class="row g-3">
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>Total User</span>
							<strong>{{ $stats['users'] }}</strong>
						</div>
						<i class="fa-solid fa-users stat-icon"></i>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>Total History</span>
							<strong>{{ $stats['histories'] }}</strong>
						</div>
						<i class="fa-solid fa-clock-rotate-left stat-icon"></i>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>Data Valid</span>
							<strong>{{ $stats['validRetrainingRows'] }}</strong>
						</div>
						<i class="fa-solid fa-database stat-icon"></i>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-3">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>Model Tersedia</span>
							<strong>{{ $stats['readyModels'] }}/{{ $stats['totalModels'] }}</strong>
						</div>
						<i class="fa-solid fa-brain stat-icon"></i>
					</div>
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
						<a href="{{ route('admin.models') }}" class="quick-action">
							<i class="fa-solid fa-brain"></i>
							<span>Paket Retraining Aktif</span>
						</a>
						<a href="{{ route('admin.history.export') }}" class="quick-action">
							<i class="fa-solid fa-download"></i>
							<span>Download History Baru</span>
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
					<p class="section-subtitle mb-3">Admin bisa memilih paket hasil retraining yang dipakai sistem, lalu melihat metrik model di dalamnya.</p>
					<a href="{{ route('admin.models') }}" class="btn btn-outline-dark w-100">
						<i class="fa-solid fa-chart-simple me-2"></i>Lihat Paket Retraining
					</a>
				</div>
			</div>
		</div>

		<div class="admin-card">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
				<div class="d-flex gap-3 align-items-start">
					<span class="stat-icon"><i class="fa-solid fa-file-arrow-down"></i></span>
					<div>
						<h2 class="h5 fw-bold mb-1">History menjadi dataset retraining</h2>
						<p class="section-subtitle mb-0">
							Export CSV memakai format retraining langsung. Kolom <code>stroke</code> diisi dari hasil prediksi sistem yang tersimpan di history.
						</p>
					</div>
				</div>
				<a href="{{ route('admin.retraining') }}" class="btn btn-outline-dark">
					<i class="fa-solid fa-database me-2"></i>Kelola Pool History
				</a>
			</div>
		</div>

		<div class="row g-4">
			<div class="col-lg-6">
				<div class="admin-card">
					<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
						<div>
							<h2 class="h5 fw-bold mb-1">Dataset Retraining Terbaru</h2>
							<p class="section-subtitle mb-0">Input paling baru yang masuk ke pool data.</p>
						</div>
						<span class="stat-icon"><i class="fa-solid fa-layer-group"></i></span>
					</div>
					<div class="table-responsive">
						<table class="table table-sm admin-table responsive-table align-middle mb-0">
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
										<td data-label="Sumber">
											<div class="table-title">{{ $dataset->source_name }}</div>
											<div class="muted-line">{{ $dataset->user?->name ?? 'User dihapus' }}</div>
										</td>
										<td data-label="Valid"><span class="metric-pill">{{ $dataset->valid_rows }} baris</span></td>
										<td data-label="Status">
											<span class="status-chip status-{{ $statusTone($dataset->status) }}">{{ $dataset->status }}</span>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="3" class="text-muted py-4">Belum ada dataset retraining.</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="admin-card">
					<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
						<div>
							<h2 class="h5 fw-bold mb-1">History Prediksi Terbaru</h2>
							<p class="section-subtitle mb-0">Aktivitas prediksi paling baru dari user.</p>
						</div>
						<span class="stat-icon"><i class="fa-solid fa-chart-line"></i></span>
					</div>
					<div class="table-responsive">
						<table class="table table-sm admin-table responsive-table align-middle mb-0">
							<thead>
								<tr>
									<th>User</th>
									<th>Model</th>
									<th>Hasil</th>
								</tr>
							</thead>
							<tbody>
								@forelse($latestHistories as $history)
									@php
										$isHighRisk = (string) $history->prediction === '1';
									@endphp
									<tr>
										<td data-label="User">
											<div class="table-title">{{ $history->user?->name ?? 'User dihapus' }}</div>
										</td>
										<td data-label="Model"><span class="metric-pill">{{ $history->model_name ?? '-' }}</span></td>
										<td data-label="Hasil">
											<span class="status-chip risk-chip {{ $isHighRisk ? 'high' : 'low' }}">
												<i class="fa-solid {{ $isHighRisk ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
												{{ $isHighRisk ? 'Risiko Tinggi' : 'Risiko Rendah' }}
											</span>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="3" class="text-muted py-4">Belum ada history prediksi.</td>
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
