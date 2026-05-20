@extends('layouts.app')

@section('content')
	<style>
		.result-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 1rem;
			margin-bottom: 1.5rem;
		}

		.summary-grid {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 1rem;
			margin-bottom: 1.5rem;
		}

		.summary-card {
			border: 1px solid rgba(214, 226, 234, 0.95);
			border-radius: 18px;
			background: #fff;
			padding: 1.15rem;
			box-shadow: 0 12px 26px rgba(15, 32, 50, 0.07);
		}

		.summary-card .label {
			color: #64748b;
			font-size: 0.78rem;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}

		.summary-card .value {
			font-size: 2rem;
			line-height: 1.1;
			font-weight: 800;
			color: #0f172a;
			margin-top: 0.35rem;
		}

		.result-table-card {
			border: 1px solid rgba(214, 226, 234, 0.95);
			border-radius: 20px;
			background: #fff;
			box-shadow: 0 18px 40px rgba(15, 32, 50, 0.08);
			overflow: hidden;
		}

		.result-table-card .table {
			margin-bottom: 0;
		}

		.result-table-card thead th {
			background: #f8fafc;
			color: #475569;
			font-size: 0.75rem;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			border-bottom: 1px solid #e2e8f0;
			white-space: nowrap;
		}

		.risk-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			padding: 0.45rem 0.7rem;
			border-radius: 999px;
			font-weight: 800;
			font-size: 0.78rem;
			white-space: nowrap;
		}

		.risk-pill.low {
			background: rgba(16, 185, 129, 0.12);
			color: #047857;
		}

		.risk-pill.high {
			background: rgba(239, 68, 68, 0.12);
			color: #b91c1c;
		}

		.error-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			padding: 0.45rem 0.7rem;
			border-radius: 999px;
			font-weight: 800;
			font-size: 0.78rem;
			background: rgba(245, 158, 11, 0.14);
			color: #92400e;
		}

		.input-list {
			display: flex;
			flex-wrap: wrap;
			gap: 0.35rem;
			max-width: 560px;
		}

		.input-chip {
			border-radius: 999px;
			background: #f1f5f9;
			color: #475569;
			font-size: 0.74rem;
			font-weight: 700;
			padding: 0.25rem 0.55rem;
		}

		@media (max-width: 991.98px) {
			.summary-grid {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}

		@media (max-width: 575.98px) {
			.result-header {
				flex-direction: column;
			}

			.summary-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<div class="result-header">
		<div>
			<p class="eyebrow">Hasil Upload</p>
			<h1 class="h3 fw-bold mb-2"><i class="fa-solid fa-table-list me-2"></i>Prediksi dari file selesai</h1>
			<p class="text-muted mb-0">{{ $fileName }} diproses dengan model {{ $modelName }} @if($accuracyDisplay) (akurasi {{ $accuracyDisplay }}) @endif.</p>
		</div>
		<div class="d-flex flex-wrap gap-2">
			<a href="{{ route('upload') }}" class="btn btn-outline-secondary">
				<i class="fa-solid fa-arrow-up-from-bracket me-2"></i>Upload Lagi
			</a>
			<a href="{{ route('history') }}" class="btn btn-dark">
				<i class="fa-solid fa-clock-rotate-left me-2"></i>Lihat Riwayat
			</a>
		</div>
	</div>

	<div class="summary-grid">
		<div class="summary-card">
			<div class="label">Total Baris</div>
			<div class="value">{{ $summary['total'] }}</div>
		</div>
		<div class="summary-card">
			<div class="label">Berhasil</div>
			<div class="value text-success">{{ $summary['success'] }}</div>
		</div>
		<div class="summary-card">
			<div class="label">Risiko Tinggi</div>
			<div class="value text-danger">{{ $summary['high'] }}</div>
		</div>
		<div class="summary-card">
			<div class="label">Perlu Dicek</div>
			<div class="value text-warning">{{ $summary['errors'] }}</div>
		</div>
	</div>

	<div class="result-table-card">
		<div class="table-responsive">
			<table class="table align-middle">
				<thead>
					<tr>
						<th>Baris</th>
						<th>Status</th>
						<th>Prob. Risiko</th>
						<th>Input</th>
						<th>Catatan</th>
					</tr>
				</thead>
				<tbody>
					@foreach($results as $item)
						<tr>
							<td class="fw-bold">#{{ $item['row'] }}</td>
							<td>
								@if($item['status'] === 'success')
									<span class="risk-pill {{ $item['riskTone'] }}">
										<i class="fa-solid {{ $item['riskTone'] === 'high' ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
										{{ $item['riskLabel'] }}
									</span>
								@else
									<span class="error-pill">
										<i class="fa-solid fa-circle-exclamation"></i>
										Error
									</span>
								@endif
							</td>
							<td>
								@if($item['status'] === 'success' && $item['probabilityDisplay'])
									<span class="fw-bold">{{ $item['probabilityDisplay'] }}</span>
								@else
									<span class="text-muted">-</span>
								@endif
							</td>
							<td>
								<div class="input-list">
									@foreach(($item['input'] ?? []) as $key => $value)
										<span class="input-chip">{{ $key }}: {{ $value ?? '-' }}</span>
									@endforeach
								</div>
							</td>
							<td class="text-muted">
								{{ $item['message'] ?? 'Tersimpan ke riwayat.' }}
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
@endsection
