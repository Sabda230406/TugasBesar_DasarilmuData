@extends('layouts.app')

@section('content')
	<style>
		.result-shell {
			background: linear-gradient(135deg, rgba(15, 118, 110, 0.14) 0%, rgba(15, 118, 110, 0.02) 100%);
			border: 1px solid rgba(15, 118, 110, 0.18);
			border-radius: 20px;
			padding: 2rem;
		}

		.result-card {
			border-radius: 20px;
			border: 1px solid rgba(214, 226, 234, 0.9);
			background: #fff;
			box-shadow: 0 18px 35px rgba(15, 32, 50, 0.08);
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

		.tips-card {
			border-radius: 16px;
			border: 1px solid rgba(15, 118, 110, 0.2);
			background: linear-gradient(135deg, rgba(223, 247, 242, 0.9), rgba(255, 255, 255, 0.95));
			padding: 1.25rem 1.5rem;
			box-shadow: 0 14px 28px rgba(15, 32, 50, 0.08);
			margin-bottom: 1.5rem;
		}

		.tips-card h6 {
			font-weight: 800;
			color: #0f766e;
			margin-bottom: 0.75rem;
		}

		.tips-list {
			margin: 0;
			padding-left: 1.1rem;
			color: #607086;
		}

		.result-meta {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
			border-radius: 999px;
			background: #f8fafc;
			border: 1px solid rgba(148, 163, 184, 0.22);
			color: #475569;
			font-size: 0.86rem;
			font-weight: 700;
			padding: 0.5rem 0.75rem;
		}

		.medical-note {
			border-radius: 14px;
			background: rgba(245, 158, 11, 0.12);
			border: 1px solid rgba(245, 158, 11, 0.2);
			color: #7c2d12;
			padding: 0.85rem 1rem;
			font-size: 0.92rem;
			line-height: 1.6;
		}
	</style>

	<div class="result-shell">
		<div class="result-card">
			<p class="eyebrow mb-2">Hasil Prediksi</p>
			<h2 class="h4 fw-bold mb-3"><i class="fa-solid fa-heart-circle-check me-2"></i>Status risiko berdasarkan input Anda</h2>
			<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
				<span class="risk-badge {{ $riskTone }}">
					<i class="fa-solid {{ $riskTone === 'high' ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
					{{ $riskLabel }}
				</span>
				@if($probabilityDisplay)
					<span class="result-meta">
						<i class="fa-solid fa-chart-simple"></i>
						Probabilitas risiko tinggi: {{ $probabilityDisplay }}
					</span>
				@endif
				@if($accuracyDisplay)
					<span class="result-meta">
						<i class="fa-solid fa-brain"></i>
						Evaluasi {{ $modelName ?? 'model' }}: accuracy {{ $accuracyDisplay }}
					</span>
				@endif
			</div>
			<p class="text-muted mb-4">{{ $riskMessage }}</p>

			<div class="medical-note mb-4">
				<i class="fa-solid fa-circle-info me-1"></i>
				Hasil ini adalah screening awal berbasis data input, bukan diagnosis medis. Jika ada gejala atau hasil risiko tinggi, tetap konsultasikan ke dokter/tenaga medis.
			</div>

			<div class="tips-card">
				<h6><i class="fa-solid fa-notes-medical me-2"></i>Tips Kesehatan</h6>
				<ul class="tips-list">
					@foreach($riskTips as $tip)
						<li>{{ $tip }}</li>
					@endforeach
				</ul>
			</div>

			<div class="d-flex flex-wrap gap-2">
				<a href="/history" class="btn btn-dark">Lihat History</a>
				<a href="/form" class="btn btn-outline-secondary">Input Ulang</a>
			</div>
		</div>
	</div>
@endsection
