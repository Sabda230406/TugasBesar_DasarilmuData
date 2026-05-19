@extends('layouts.app')

@section('content')
	<style>
		.result-shell {
			background: linear-gradient(135deg, rgba(14, 116, 144, 0.12) 0%, rgba(14, 116, 144, 0.02) 100%);
			border: 1px solid rgba(15, 23, 42, 0.08);
			border-radius: 20px;
			padding: 2rem;
		}

		.result-card {
			border-radius: 20px;
			border: 1px solid rgba(15, 23, 42, 0.08);
			background: #fff;
			box-shadow: 0 18px 35px rgba(15, 23, 42, 0.06);
			padding: 2rem;
		}

		.risk-badge {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.6rem 1rem;
			border-radius: 999px;
			font-weight: 700;
			font-size: 1rem;
		}

		.risk-badge.low {
			background: rgba(16, 185, 129, 0.12);
			color: #047857;
		}

		.risk-badge.high {
			background: rgba(239, 68, 68, 0.12);
			color: #b91c1c;
		}

		.countdown-card {
			border-radius: 14px;
			background: #f8fafc;
			border: 1px dashed rgba(148, 163, 184, 0.4);
			padding: 0.75rem 1rem;
			font-size: 0.9rem;
			color: #475569;
		}
	</style>

	<div class="result-shell">
		<div class="result-card">
			<p class="eyebrow mb-2">Hasil Prediksi</p>
			<h2 class="h4 fw-bold mb-3">Status risiko berdasarkan input Anda</h2>
			<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
				<span class="risk-badge {{ $riskTone }}">
					<i class="fa-solid {{ $riskTone === 'high' ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
					{{ $riskLabel }}
				</span>
				@if($accuracy)
					<span class="text-muted">Akurasi model: {{ $accuracy }}</span>
				@endif
			</div>
			<p class="text-muted mb-4">{{ $riskMessage }}</p>

			<div class="d-flex flex-wrap gap-2">
				<a href="/history" class="btn btn-dark">Lihat History</a>
				<a href="/form" class="btn btn-outline-secondary">Input Ulang</a>
			</div>
		</div>
	</div>
@endsection
