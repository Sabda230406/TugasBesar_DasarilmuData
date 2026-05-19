@extends('layouts.app')

@section('content')
	<style>
		.form-hero {
			background: linear-gradient(135deg, rgba(14, 116, 144, 0.12) 0%, rgba(14, 116, 144, 0.02) 100%);
			border: 1px solid rgba(15, 23, 42, 0.08);
			border-radius: 18px;
			padding: 1.5rem 1.75rem;
		}

		.form-card {
			border-radius: 18px;
			border: 1px solid rgba(15, 23, 42, 0.08);
			background: #fff;
			box-shadow: 0 18px 35px rgba(15, 23, 42, 0.06);
			padding: 1.5rem;
		}

		.form-section-title {
			font-weight: 700;
			color: #0f172a;
			margin-bottom: 0.75rem;
		}

		.form-helper {
			color: #64748b;
			font-size: 0.85rem;
		}

		.form-control,
		.form-select {
			border-radius: 12px;
			border-color: rgba(148, 163, 184, 0.4);
			padding: 0.7rem 0.9rem;
		}

		.form-control:focus,
		.form-select:focus {
			box-shadow: 0 0 0 0.2rem rgba(14, 116, 144, 0.15);
			border-color: rgba(14, 116, 144, 0.6);
		}

		.form-footer {
			border-top: 1px solid rgba(148, 163, 184, 0.2);
			padding-top: 1.25rem;
		}
	</style>

	<div class="form-hero mb-4">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<div>
				<p class="eyebrow mb-2">Form Prediksi</p>
				<h1 class="h4 fw-bold mb-2">Masukkan data pasien untuk prediksi risiko stroke.</h1>
				<p class="mb-0 text-muted">Lengkapi seluruh field agar hasil prediksi lebih akurat.</p>
			</div>
			<div class="text-end">
				<span class="status-badge">Model Aktif</span>
			</div>
		</div>
	</div>

	@if ($errors->any())
		<div class="alert alert-danger">
			<ul class="mb-0">
				@foreach ($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<form action="/predict" method="POST" class="row g-4">
		@csrf

		<div class="col-lg-6">
			<div class="form-card h-100">
				<h6 class="form-section-title">Profil Pasien</h6>
				<p class="form-helper mb-4">Informasi dasar demografis pasien.</p>
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label" for="gender">Gender</label>
						<select class="form-select" id="gender" name="gender" required>
							@foreach(['Male', 'Female', 'Other'] as $option)
								<option value="{{ $option }}" @selected(old('gender') === $option)>{{ $option }}</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="age">Age</label>
						<input class="form-control" id="age" type="number" min="0" max="120" name="age" value="{{ old('age') }}" placeholder="Contoh: 45" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ever_married">Ever Married</label>
						<select class="form-select" id="ever_married" name="ever_married" required>
							@foreach(['Yes', 'No'] as $option)
								<option value="{{ $option }}" @selected(old('ever_married') === $option)>{{ $option }}</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="Residence_type">Residence Type</label>
						<select class="form-select" id="Residence_type" name="Residence_type" required>
							@foreach(['Urban', 'Rural'] as $option)
								<option value="{{ $option }}" @selected(old('Residence_type') === $option)>{{ $option }}</option>
							@endforeach
						</select>
					</div>
					<div class="col-12">
						<label class="form-label" for="work_type">Work Type</label>
						<select class="form-select" id="work_type" name="work_type" required>
							@foreach(['Private', 'Self-employed', 'Govt_job', 'children', 'Never_worked'] as $option)
								<option value="{{ $option }}" @selected(old('work_type') === $option)>{{ $option }}</option>
							@endforeach
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="form-card h-100">
				<h6 class="form-section-title">Kondisi Kesehatan</h6>
				<p class="form-helper mb-4">Detail medis dan gaya hidup yang memengaruhi risiko.</p>
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label" for="hypertension">Hypertension</label>
						<select class="form-select" id="hypertension" name="hypertension" required>
							<option value="0" @selected(old('hypertension') === '0')>Tidak</option>
							<option value="1" @selected(old('hypertension') === '1')>Ya</option>
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="heart_disease">Heart Disease</label>
						<select class="form-select" id="heart_disease" name="heart_disease" required>
							<option value="0" @selected(old('heart_disease') === '0')>Tidak</option>
							<option value="1" @selected(old('heart_disease') === '1')>Ya</option>
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="avg_glucose_level">Avg Glucose Level</label>
						<input class="form-control" id="avg_glucose_level" type="number" step="0.01"
							name="avg_glucose_level" value="{{ old('avg_glucose_level') }}" placeholder="Contoh: 110.5" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="bmi">BMI</label>
						<input class="form-control" id="bmi" type="number" step="0.1" name="bmi"
							value="{{ old('bmi') }}" placeholder="Contoh: 24.2" required>
					</div>
					<div class="col-12">
						<label class="form-label" for="smoking_status">Smoking Status</label>
						<select class="form-select" id="smoking_status" name="smoking_status" required>
							@foreach(['formerly smoked', 'never smoked', 'smokes', 'Unknown'] as $option)
								<option value="{{ $option }}" @selected(old('smoking_status') === $option)>{{ $option }}</option>
							@endforeach
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12">
			<div class="form-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
				<p class="mb-0 text-muted">Pastikan semua data terisi sebelum submit.</p>
				<div class="d-flex gap-2">
					<a href="/history" class="btn btn-outline-secondary">Lihat Riwayat</a>
					<button type="submit" class="btn btn-dark px-4">Prediksi Sekarang</button>
				</div>
			</div>
		</div>
	</form>
@endsection
