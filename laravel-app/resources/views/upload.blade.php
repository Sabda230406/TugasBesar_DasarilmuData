@extends('layouts.app')

@section('content')
	@php
		$modelName = $modelMetrics['model_name'] ?? 'Decision Tree';
		$accuracyDisplay = $modelMetrics['accuracy_display'] ?? null;
	@endphp

	<style>
		.upload-hero {
			display: grid;
			grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
			gap: 1.5rem;
			align-items: stretch;
			margin-bottom: 1.5rem;
		}

		.upload-panel,
		.format-panel,
		.help-panel {
			border: 1px solid rgba(214, 226, 234, 0.95);
			border-radius: 20px;
			background: #fff;
			box-shadow: 0 18px 40px rgba(15, 32, 50, 0.08);
		}

		.upload-panel {
			padding: 1.75rem;
		}

		.model-mini-card {
			padding: 1.25rem;
			background: linear-gradient(135deg, rgba(223, 247, 242, 0.9), rgba(255, 255, 255, 0.95));
		}

		.dropzone {
			position: relative;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			min-height: 260px;
			padding: 2rem;
			border: 2px dashed rgba(15, 118, 110, 0.32);
			border-radius: 18px;
			background:
				linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(245, 158, 11, 0.06)),
				#fbfefd;
			text-align: center;
			transition: border-color 0.2s ease, transform 0.2s ease, background 0.2s ease;
		}

		.dropzone.is-dragover {
			border-color: #0f766e;
			transform: translateY(-2px);
			background: rgba(223, 247, 242, 0.9);
		}

		.dropzone input {
			position: absolute;
			inset: 0;
			opacity: 0;
			cursor: pointer;
		}

		.upload-icon {
			width: 72px;
			height: 72px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border-radius: 18px;
			background: #0f766e;
			color: #fff;
			font-size: 1.75rem;
			box-shadow: 0 18px 32px rgba(15, 118, 110, 0.24);
			margin-bottom: 1rem;
		}

		.file-name-pill {
			display: none;
			margin-top: 1rem;
			padding: 0.55rem 0.8rem;
			border-radius: 999px;
			background: #eff6ff;
			color: #1d4ed8;
			font-weight: 700;
			font-size: 0.88rem;
		}

		.file-name-pill.is-visible {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
		}

		.format-panel {
			padding: 1.25rem;
		}

		.column-grid {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 0.65rem;
		}

		.column-chip {
			border: 1px solid rgba(148, 163, 184, 0.25);
			border-radius: 12px;
			background: #f8fafc;
			padding: 0.55rem 0.7rem;
			font-size: 0.82rem;
			font-weight: 700;
			color: #334155;
			overflow-wrap: anywhere;
		}

		.example-box {
			border-radius: 14px;
			background: #0f172a;
			color: #dbeafe;
			padding: 1rem;
			font-size: 0.8rem;
			overflow-x: auto;
			margin: 0;
		}

		@media (max-width: 991.98px) {
			.upload-hero {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 575.98px) {
			.column-grid {
				grid-template-columns: 1fr;
			}

			.dropzone {
				min-height: 220px;
				padding: 1.25rem;
			}
		}
	</style>

	<div class="upload-hero">
		<div class="upload-panel">
			<p class="eyebrow">Prediksi Batch</p>
			<h1 class="h3 fw-bold mb-2"><i class="fa-solid fa-file-medical me-2"></i>Upload CSV atau Excel pasien</h1>
			<p class="text-muted mb-4">Unggah data banyak pasien sekaligus. Baris yang valid akan diprediksi dan tersimpan ke riwayat akun Anda.</p>

			@if ($errors->any())
				<div class="alert alert-danger">
					<ul class="mb-0">
						@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
			@endif

			<form action="{{ route('upload.predict') }}" method="POST" enctype="multipart/form-data">
				@csrf
				<label class="dropzone" id="dropzone">
					<input id="prediction_file" type="file" name="prediction_file" accept=".csv,.txt,.xlsx,.xls" required>
					<span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
					<span class="h5 fw-bold mb-2">Pilih file atau tarik ke sini</span>
					<span class="text-muted">Format: CSV, XLSX, atau XLS. Maksimal 5 MB dan 500 baris.</span>
					<span class="file-name-pill" id="fileName">
						<i class="fa-solid fa-file-lines"></i>
						<span></span>
					</span>
				</label>

				<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
					<div class="text-muted small">
						<i class="fa-solid fa-shield-heart me-1"></i>
						Pastikan header file sesuai kolom model.
					</div>
					<button type="submit" class="btn btn-dark btn-lg px-4">
						<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Proses Prediksi
					</button>
				</div>
			</form>
		</div>

		<div class="model-mini-card upload-panel">
			<p class="eyebrow">Model Aktif</p>
			<h2 class="h5 fw-bold mb-3">{{ $modelName }}</h2>
			<div class="d-flex flex-column gap-3">
				<div>
					<div class="text-muted small">Akurasi Evaluasi</div>
					<div class="h3 fw-bold mb-0">{{ $accuracyDisplay ?? '-' }}</div>
				</div>
				<div>
					<div class="text-muted small">Output</div>
					<div class="fw-bold">Risiko Rendah / Risiko Tinggi</div>
				</div>
				<div>
					<div class="text-muted small">Penyimpanan</div>
					<div class="fw-bold">Setiap prediksi valid masuk ke riwayat</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-4">
		<div class="col-lg-7">
			<div class="format-panel h-100">
				<p class="eyebrow">Kolom Wajib</p>
				<h2 class="h5 fw-bold mb-3">Header file yang diterima</h2>
				<div class="column-grid">
					@foreach($requiredColumns as $column)
						<div class="column-chip">{{ $column }}</div>
					@endforeach
				</div>
			</div>
		</div>
		<div class="col-lg-5">
			<div class="format-panel h-100">
				<p class="eyebrow">Contoh CSV</p>
				<h2 class="h5 fw-bold mb-3">Satu baris contoh input</h2>
				<pre class="example-box">gender,age,hypertension,heart_disease,ever_married,work_type,Residence_type,avg_glucose_level,bmi,smoking_status
Female,25,0,0,No,Private,Urban,85,20.2,never smoked
Male,80,1,1,Yes,Private,Urban,250,40,smokes</pre>
			</div>
		</div>
	</div>

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
	</script>
@endsection
