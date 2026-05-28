@extends('layouts.app')

@section('content')
	@php
		$modelOptions = $models ?? [];
		$selectedModelKey = old('model', $selectedModelKey ?? array_key_first($modelOptions) ?? 'decision_tree');
		$selectedModel = $modelOptions[$selectedModelKey] ?? ($modelOptions ? reset($modelOptions) : null);
		$modelName = $selectedModel['name'] ?? $modelMetrics['model_name'] ?? 'Decision Tree';
		$readyCount = count(array_filter($modelOptions, fn ($model) => $model['available'] ?? false));
	@endphp

	<style>
		.upload-hero {
			background: linear-gradient(135deg, rgba(15, 118, 110, 0.14) 0%, rgba(15, 118, 110, 0.02) 100%);
			border: 1px solid rgba(15, 118, 110, 0.18);
			border-radius: 18px;
			padding: 1.5rem 1.75rem;
		}

		.upload-card {
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

		.upload-card-head {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 1rem;
			margin-bottom: 1.25rem;
		}

		.dropzone {
			position: relative;
			display: grid;
			place-items: center;
			min-height: 250px;
			padding: 2rem;
			border: 2px dashed rgba(15, 118, 110, 0.34);
			border-radius: 16px;
			background:
				linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(245, 158, 11, 0.05)),
				#fbfefd;
			text-align: center;
			transition: border-color 0.2s ease, background 0.2s ease;
		}

		.dropzone.is-dragover {
			border-color: #0f766e;
			background: rgba(223, 247, 242, 0.82);
		}

		.dropzone input {
			position: absolute;
			inset: 0;
			opacity: 0;
			cursor: pointer;
		}

		.upload-icon {
			width: 64px;
			height: 64px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border-radius: 16px;
			background: #0f766e;
			color: #fff;
			font-size: 1.55rem;
			box-shadow: 0 14px 26px rgba(15, 118, 110, 0.22);
			margin-bottom: 1rem;
		}

		.file-name-pill {
			display: none;
			margin-top: 1rem;
			padding: 0.55rem 0.8rem;
			border-radius: 999px;
			background: #eff6ff;
			color: #1d4ed8;
			font-weight: 800;
			font-size: 0.86rem;
		}

		.file-name-pill.is-visible {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
		}

		.upload-rules {
			display: flex;
			flex-wrap: wrap;
			gap: 0.55rem;
			margin-top: 1rem;
		}

		.rule-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
			border-radius: 999px;
			background: #f8fafc;
			border: 1px solid rgba(148, 163, 184, 0.22);
			color: #0f766e;
			font-size: 0.82rem;
			font-weight: 800;
			padding: 0.45rem 0.7rem;
		}

		.rule-pill span {
			color: #475569;
		}

		.column-list {
			display: flex;
			flex-wrap: wrap;
			gap: 0.45rem;
		}

		.required-block {
			margin-top: 1.25rem;
			padding-top: 1.25rem;
			border-top: 1px solid rgba(148, 163, 184, 0.2);
		}

		.column-chip {
			border: 1px solid rgba(148, 163, 184, 0.22);
			border-radius: 999px;
			background: #f8fafc;
			padding: 0.35rem 0.6rem;
			font-size: 0.78rem;
			font-weight: 800;
			color: #334155;
			overflow-wrap: anywhere;
		}

		.upload-footer {
			border-top: 1px solid rgba(148, 163, 184, 0.2);
			padding-top: 1.25rem;
		}

		@media (max-width: 767.98px) {
			.upload-card-head {
				flex-direction: column;
			}

			.model-options {
				grid-template-columns: 1fr;
			}

			.dropzone {
				min-height: 240px;
				padding: 1.25rem;
			}
		}
	</style>

	<div class="upload-hero mb-4">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<div>
				<p class="eyebrow mb-2">Prediksi Batch</p>
				<h1 class="h4 fw-bold mb-2"><i class="fa-solid fa-file-arrow-up me-2"></i>Upload data pasien untuk prediksi risiko stroke.</h1>
				<p class="mb-0 text-muted">Gunakan file CSV, XLSX, atau XLS untuk memproses banyak pasien sekaligus.</p>
			</div>
			<div class="text-end">
				<span class="status-badge" id="activeModelBadge">
					<i id="activeModelIcon" class="fa-solid {{ $selectedModel['icon'] ?? 'fa-brain' }} me-1"></i>
					<span id="activeModelName">{{ $modelName }}</span> Aktif
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

	<form action="{{ route('upload.predict') }}" method="POST" enctype="multipart/form-data" class="row g-4">
		@csrf

		<div class="col-12">
			<div class="model-picker">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
					<div>
						<div class="form-label fw-bold mb-1">Pilih Model Prediksi</div>
						<div class="form-helper">Model yang dipilih akan dipakai untuk seluruh baris valid dalam file.</div>
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
						<label class="model-option {{ $isSelected ? 'is-active' : '' }} {{ ! $isAvailable ? 'is-disabled' : '' }}" for="upload_model_{{ $key }}">
							<input id="upload_model_{{ $key }}" type="radio" name="model" value="{{ $key }}"
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

		<div class="col-12">
			<div class="upload-card">
				<div class="upload-card-head">
					<div>
						<h6 class="form-section-title"><i class="fa-solid fa-file-arrow-up me-2"></i>File Data Pasien</h6>
						<p class="form-helper mb-0">Upload file dengan header yang sesuai supaya data bisa diproses tanpa input manual satu per satu.</p>
					</div>
					<a href="{{ asset('templates/stroke-input-template.csv') }}" class="btn btn-outline-secondary" download>
						<i class="fa-solid fa-file-csv me-2"></i>Download Template
					</a>
				</div>

				<label class="dropzone" id="dropzone">
					<input id="prediction_file" type="file" name="prediction_file" accept=".csv,.txt,.xlsx,.xls" required>
					<span>
						<span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
						<span class="h5 fw-bold d-block mb-2">Pilih file atau tarik ke sini</span>
						<span class="text-muted d-block">File akan divalidasi sebelum diproses.</span>
						<span class="file-name-pill" id="fileName">
							<i class="fa-solid fa-file-lines"></i>
							<span></span>
						</span>
					</span>
				</label>

				<div class="upload-rules">
					<span class="rule-pill"><i class="fa-solid fa-file-lines"></i><span>CSV, XLSX, atau XLS</span></span>
					<span class="rule-pill"><i class="fa-solid fa-weight-scale"></i><span>Maksimal 5 MB</span></span>
					<span class="rule-pill"><i class="fa-solid fa-table-list"></i><span>Maksimal 500 baris</span></span>
				</div>

				<div class="required-block">
					<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
						<h6 class="form-section-title mb-0"><i class="fa-solid fa-table-columns me-2"></i>Header Wajib</h6>
						<span class="form-helper">Template CSV sudah berisi semua header ini.</span>
					</div>
					<div class="column-list">
						@foreach($requiredColumns as $column)
							<span class="column-chip">{{ $column }}</span>
						@endforeach
					</div>
				</div>

				<div class="upload-footer d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
					<p class="mb-0 text-muted">Baris valid akan diprediksi dan tersimpan ke riwayat akun.</p>
					<div class="d-flex flex-wrap gap-2">
						<button type="submit" class="btn btn-dark px-4">
							<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Proses Prediksi
						</button>
					</div>
				</div>
			</div>
		</div>
	</form>

	<script>
		const dropzone = document.getElementById('dropzone');
		const input = document.getElementById('prediction_file');
		const fileName = document.getElementById('fileName');
		const fileNameText = fileName?.querySelector('span');

		const showFileName = () => {
			const file = input?.files?.[0];
			if (!file || !fileName || !fileNameText) return;
			fileNameText.textContent = file.name;
			fileName.classList.add('is-visible');
		};

		input?.addEventListener('change', showFileName);

		['dragenter', 'dragover'].forEach((eventName) => {
			dropzone?.addEventListener(eventName, (event) => {
				event.preventDefault();
				dropzone.classList.add('is-dragover');
			});
		});

		['dragleave', 'drop'].forEach((eventName) => {
			dropzone?.addEventListener(eventName, () => {
				dropzone.classList.remove('is-dragover');
			});
		});

		document.querySelectorAll('input[name="model"]').forEach((modelInput) => {
			modelInput.addEventListener('change', () => {
				document.querySelectorAll('.model-option').forEach((option) => option.classList.remove('is-active'));
				modelInput.closest('.model-option')?.classList.add('is-active');

				const activeModelName = document.getElementById('activeModelName');
				const activeModelIcon = document.getElementById('activeModelIcon');
				if (activeModelName) {
					activeModelName.textContent = modelInput.dataset.modelName || 'Model';
				}
				if (activeModelIcon) {
					activeModelIcon.className = `fa-solid ${modelInput.dataset.modelIcon || 'fa-brain'} me-1`;
				}
			});
		});
	</script>
@endsection
