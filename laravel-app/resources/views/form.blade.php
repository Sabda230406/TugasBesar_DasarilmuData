@extends('layouts.app')

@section('content')
	@php
		$modelOptions = $models ?? [];
		$selectedModelKey = old('model', $selectedModelKey ?? array_key_first($modelOptions) ?? 'decision_tree');
		$selectedModel = $modelOptions[$selectedModelKey] ?? ($modelOptions ? reset($modelOptions) : null);
		$readyCount = count(array_filter($modelOptions, fn ($model) => $model['available'] ?? false));
	@endphp

	<style>
		.form-hero {
			background: linear-gradient(135deg, rgba(15, 118, 110, 0.14) 0%, rgba(15, 118, 110, 0.02) 100%);
			border: 1px solid rgba(15, 118, 110, 0.18);
			border-radius: 18px;
			padding: 1.5rem 1.75rem;
		}

		.form-card {
			border-radius: 18px;
			border: 1px solid rgba(214, 226, 234, 0.9);
			background: #fff;
			box-shadow: 0 16px 32px rgba(15, 32, 50, 0.08);
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

		.model-picker {
			border: 1px solid rgba(15, 118, 110, 0.18);
			border-radius: 16px;
			background: #ffffff;
			padding: 1rem;
			box-shadow: 0 14px 28px rgba(15, 32, 50, 0.06);
		}

		.model-options {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 0.75rem;
			margin-top: 0.85rem;
		}

		.model-option {
			position: relative;
			display: grid;
			grid-template-columns: auto minmax(0, 1fr);
			gap: 0.75rem;
			align-items: center;
			min-height: 92px;
			border: 1px solid rgba(148, 163, 184, 0.32);
			border-radius: 14px;
			background: #f8fafc;
			padding: 0.95rem;
			transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
		}

		.model-option input {
			position: absolute;
			opacity: 0;
			pointer-events: none;
		}

		.model-option.is-active {
			border-color: rgba(15, 118, 110, 0.48);
			background: linear-gradient(135deg, rgba(223, 247, 242, 0.9), #ffffff);
			box-shadow: 0 12px 24px rgba(15, 118, 110, 0.12);
		}

		.model-option.is-disabled {
			cursor: not-allowed;
			opacity: 0.74;
		}

		.model-option-icon {
			width: 42px;
			height: 42px;
			border-radius: 12px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: rgba(15, 118, 110, 0.12);
			color: #0f5e57;
		}

		.model-option.is-disabled .model-option-icon {
			background: rgba(148, 163, 184, 0.16);
			color: #64748b;
		}

		.model-option-title,
		.model-option-meta {
			display: block;
		}

		.model-option-title {
			font-weight: 800;
			color: #0f172a;
		}

		.model-option-meta {
			color: #64748b;
			font-size: 0.78rem;
			font-weight: 700;
		}

		.model-option-badge {
			grid-column: 1 / -1;
			width: fit-content;
			border-radius: 999px;
			background: rgba(15, 118, 110, 0.12);
			color: #0f5e57;
			font-size: 0.72rem;
			font-weight: 800;
			padding: 0.3rem 0.55rem;
		}

		.model-option.is-disabled .model-option-badge {
			background: rgba(245, 158, 11, 0.14);
			color: #92400e;
		}

		.model-note {
			display: inline-flex;
			align-items: center;
			gap: 0.4rem;
			border-radius: 999px;
			background: rgba(245, 158, 11, 0.14);
			color: #92400e;
			font-size: 0.78rem;
			font-weight: 800;
			padding: 0.45rem 0.7rem;
		}

		.form-control,
		.form-select {
			border-radius: 12px;
			border-color: rgba(148, 163, 184, 0.35);
			padding: 0.7rem 0.9rem;
		}

		.form-control:focus,
		.form-select:focus {
			box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.16);
			border-color: rgba(15, 118, 110, 0.65);
		}

		.form-footer {
			border-top: 1px solid rgba(148, 163, 184, 0.2);
			padding-top: 1.25rem;
		}

		@media (max-width: 767.98px) {
			.model-options {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<div class="form-hero mb-4">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<div>
				<p class="eyebrow mb-2">Form Prediksi</p>
				<h1 class="h4 fw-bold mb-2"><i class="fa-solid fa-file-medical me-2"></i>Masukkan data pasien untuk prediksi risiko stroke.</h1>
				<p class="mb-0 text-muted">Lengkapi seluruh field agar hasil prediksi lebih akurat.</p>
			</div>
			<div class="text-end">
				<span class="status-badge" id="activeModelBadge">
					<i id="activeModelIcon" class="fa-solid {{ $selectedModel['icon'] ?? 'fa-brain' }} me-1"></i>
					<span id="activeModelName">{{ $selectedModel['name'] ?? 'Model' }}</span> Aktif
				</span>
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

		<div class="col-12">
			<div class="model-picker">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
					<div>
						<div class="form-label fw-bold mb-1">Pilih Model Prediksi</div>
						<div class="form-helper">Pilih model yang ingin dipakai. Model tanpa artefak otomatis dikunci.</div>
					</div>
					<span class="model-note">
						<i class="fa-solid fa-circle-info"></i>
						{{ $readyCount }} model siap dipakai
					</span>
				</div>
				<div class="model-options" role="radiogroup" aria-label="Pilih Model Prediksi">
					@foreach($modelOptions as $key => $model)
						@php
							$isAvailable = $model['available'] ?? false;
							$isSelected = $selectedModelKey === $key && $isAvailable;
						@endphp
						<label class="model-option {{ $isSelected ? 'is-active' : '' }} {{ ! $isAvailable ? 'is-disabled' : '' }}" for="form_model_{{ $key }}">
							<input id="form_model_{{ $key }}" type="radio" name="model" value="{{ $key }}"
								data-model-name="{{ $model['name'] ?? $model['label'] }}"
								data-model-icon="{{ $model['icon'] ?? 'fa-brain' }}"
								@checked($isSelected) @disabled(! $isAvailable)>
							<span class="model-option-icon"><i class="fa-solid {{ $model['icon'] ?? 'fa-brain' }}"></i></span>
							<span>
								<span class="model-option-title">{{ $model['name'] ?? $model['label'] }}</span>
								<span class="model-option-meta">{{ $model['meta'] ?? '-' }}</span>
							</span>
							<span class="model-option-badge">{{ $model['status_label'] ?? ($isAvailable ? 'Siap' : 'Belum aktif') }}</span>
						</label>
					@endforeach
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="form-card h-100">
				<h6 class="form-section-title"><i class="fa-solid fa-user-nurse me-2"></i>Profil Pasien</h6>
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
				<h6 class="form-section-title"><i class="fa-solid fa-notes-medical me-2"></i>Riwayat Kesehatan</h6>
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
						<label class="form-label" for="weight">Berat Badan (kg)</label>
						<input class="form-control" id="weight" type="number" step="0.1" min="0"
							name="weight" value="{{ old('weight') }}" placeholder="Contoh: 65" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="height">Tinggi Badan (cm)</label>
						<input class="form-control" id="height" type="number" step="0.1" min="0"
							name="height" value="{{ old('height') }}" placeholder="Contoh: 170" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="bmi">BMI (otomatis)</label>
						<input class="form-control" id="bmi" type="number" step="0.01" name="bmi"
							value="{{ old('bmi') }}" placeholder="Otomatis" readonly required>
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

	<script>
		const weightInput = document.getElementById('weight');
		const heightInput = document.getElementById('height');
		const bmiInput = document.getElementById('bmi');

		const updateBmi = () => {
			const weight = parseFloat(weightInput?.value || '0');
			const heightCm = parseFloat(heightInput?.value || '0');
			if (!weight || !heightCm) {
				if (bmiInput) bmiInput.value = '';
				return;
			}
			const heightM = heightCm / 100;
			const bmi = weight / (heightM * heightM);
			if (bmiInput && Number.isFinite(bmi)) {
				bmiInput.value = bmi.toFixed(2);
			}
		};

		weightInput?.addEventListener('input', updateBmi);
		heightInput?.addEventListener('input', updateBmi);
		window.addEventListener('DOMContentLoaded', updateBmi);

		document.querySelectorAll('input[name="model"]').forEach((input) => {
			input.addEventListener('change', () => {
				document.querySelectorAll('.model-option').forEach((option) => option.classList.remove('is-active'));
				input.closest('.model-option')?.classList.add('is-active');

				const activeModelName = document.getElementById('activeModelName');
				const activeModelIcon = document.getElementById('activeModelIcon');
				if (activeModelName) {
					activeModelName.textContent = input.dataset.modelName || 'Model';
				}
				if (activeModelIcon) {
					activeModelIcon.className = `fa-solid ${input.dataset.modelIcon || 'fa-brain'} me-1`;
				}
			});
		});
	</script>
@endsection
