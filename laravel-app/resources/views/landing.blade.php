@extends('layouts.app')

@section('content')
    @php
        $modelOptions = $models ?? [];
        $readyModels = array_filter($modelOptions, fn ($model) => $model['available'] ?? false);
        $readyCount = count($readyModels);
        $totalModelCount = count($modelOptions);
        $allModelNames = $modelOptions ? implode(', ', array_map(fn ($model) => $model['label'] ?? $model['name'], $modelOptions)) : 'Decision Tree, KNN, SVM';
        $modelStatusSummary = $readyCount . ' Model Siap';
        if ($totalModelCount > $readyCount) {
            $modelStatusSummary .= ', ' . ($totalModelCount - $readyCount) . ' Belum Aktif';
        }
        $modelPlainGuides = [
            'decision_tree' => [
                'headline' => 'Seperti daftar pertanyaan berurutan.',
                'body' => 'Sistem membaca data pasien langkah demi langkah, seperti petugas yang menanyakan beberapa hal penting sebelum memberi gambaran risiko.',
                'points' => [
                    'Mencermati faktor seperti usia, kondisi kesehatan, kadar gula, BMI, dan kebiasaan merokok.',
                    'Setiap jawaban membawa data ke langkah berikutnya sampai pola risikonya terbaca.',
                    'Hasil akhirnya berupa tanda risiko rendah atau tinggi sebagai screening awal.',
                ],
            ],
            'knn' => [
                'headline' => 'Seperti mencari pasien yang mirip.',
                'body' => 'Sistem membandingkan data pasien baru dengan data pasien lain yang sudah pernah dipelajari, lalu melihat kelompok mana yang paling mirip.',
                'points' => [
                    'Mencari data lama yang kondisinya paling mendekati data pasien baru.',
                    'Melihat apakah data yang mirip tersebut lebih banyak berada di kelompok risiko rendah atau tinggi.',
                    'Memberi hasil berdasarkan pola terbanyak dari data yang paling mirip.',
                ],
            ],
            'svm' => [
                'headline' => 'Seperti membuat batas aman dan waspada.',
                'body' => 'Sistem mempelajari pola data untuk membedakan kelompok risiko rendah dan tinggi, lalu menempatkan data pasien baru pada sisi yang paling sesuai.',
                'points' => [
                    'Mengenali perbedaan pola antara data yang cenderung aman dan data yang perlu diwaspadai.',
                    'Mencari batas pemisah yang paling jelas di antara pola-pola tersebut.',
                    'Menentukan apakah data pasien lebih dekat ke sisi risiko rendah atau risiko tinggi.',
                ],
            ],
        ];
    @endphp

    <style>
        .landing-shell {
            display: grid;
            gap: 2rem;
        }

        .hero-section {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            padding: 2.5rem;
            background:
                linear-gradient(135deg, rgba(15, 32, 47, 0.92), rgba(15, 118, 110, 0.88)),
                url("https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1400&q=80");
            background-size: cover;
            background-position: center;
            color: #fff;
            min-height: 460px;
            display: flex;
            align-items: center;
            box-shadow: 0 24px 50px rgba(15, 32, 50, 0.2);
        }

        .hero-content {
            max-width: 760px;
            position: relative;
            z-index: 1;
        }

        .hero-card {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 22px;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 16px 34px rgba(15, 32, 50, 0.2);
        }

        .hero-list {
            display: grid;
            gap: 0.75rem;
        }

        .hero-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            font-weight: 600;
            color: #e2f6f3;
        }

        .hero-list-item span:last-child {
            text-align: right;
        }


        .quick-stat {
            border: 1px solid rgba(15, 118, 110, 0.16);
            border-radius: 18px;
            padding: 1.25rem;
            background: #ffffff;
            box-shadow: 0 14px 28px rgba(15, 32, 50, 0.08);
        }

        .quick-stat .icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 118, 110, 0.12);
            color: #0f766e;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }

        .action-card {
            border-radius: 20px;
            border: 1px solid rgba(15, 118, 110, 0.16);
            background: linear-gradient(135deg, rgba(223, 247, 242, 0.9), rgba(255, 255, 255, 0.95));
            padding: 1.5rem;
            box-shadow: 0 14px 28px rgba(15, 32, 50, 0.08);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: rgba(255, 255, 255, 0.9);
            font-weight: 700;
            font-size: 0.86rem;
        }

        .section-kicker {
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 800;
            color: #0f766e;
            margin-bottom: 0.55rem;
        }

        .info-card,
        .model-card,
        .team-card,
        .step-card {
            height: 100%;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
            padding: 1.5rem;
        }

        .model-status {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
            font-size: 0.76rem;
            font-weight: 800;
        }

        .model-status.active {
            background: rgba(15, 118, 110, 0.12);
            color: #0f5e57;
        }

        .model-status.pending {
            background: rgba(245, 158, 11, 0.14);
            color: #92400e;
        }

        .model-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .model-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .model-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: rgba(15, 118, 110, 0.1);
            color: #0f766e;
            font-size: 1.1rem;
        }

        .model-work-panel {
            border-radius: 16px;
            border: 1px solid rgba(15, 118, 110, 0.15);
            background: linear-gradient(135deg, rgba(240, 253, 250, 0.9), #ffffff);
            padding: 1rem;
        }

        .model-work-label {
            color: #0f172a;
            font-weight: 800;
            margin-bottom: 0.35rem;
        }

        .model-work-list {
            display: grid;
            gap: 0.7rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .model-work-list li {
            display: flex;
            gap: 0.65rem;
            color: #475569;
            line-height: 1.6;
        }

        .model-work-list i {
            color: #0f766e;
            margin-top: 0.25rem;
            flex: 0 0 auto;
        }

        .stroke-visual {
            min-height: 340px;
            border-radius: 24px;
            background:
                linear-gradient(135deg, rgba(223, 247, 242, 0.94), rgba(236, 253, 245, 0.98));
            border: 1px solid rgba(15, 118, 110, 0.18);
            display: grid;
            place-items: center;
            padding: 2rem;
        }

        .brain-illustration {
            width: min(320px, 100%);
            aspect-ratio: 1;
            border-radius: 50%;
            display: grid;
            place-items: center;
            position: relative;
            background: radial-gradient(circle, #ffffff 0 42%, rgba(15, 118, 110, 0.12) 43% 100%);
            box-shadow: inset 0 0 0 18px rgba(255, 255, 255, 0.55), 0 24px 45px rgba(15, 118, 110, 0.16);
        }

        .brain-illustration::before,
        .brain-illustration::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.18);
        }

        .brain-illustration::before {
            width: 64px;
            height: 64px;
            top: 52px;
            right: 54px;
        }

        .brain-illustration::after {
            width: 92px;
            height: 8px;
            left: 58px;
            bottom: 85px;
            transform: rotate(-18deg);
        }

        .brain-illustration i {
            font-size: 8rem;
            color: #0e7490;
            position: relative;
            z-index: 1;
        }

        .dataset-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 800;
            color: #0e7490;
            text-decoration: none;
        }

        .dataset-link:hover {
            color: #155e75;
        }

        .dt-simple-note {
            border-radius: 14px;
            background: rgba(15, 118, 110, 0.08);
            border: 1px solid rgba(15, 118, 110, 0.14);
            padding: 0.85rem;
            color: #334155;
            line-height: 1.6;
        }

        .team-card {
            display: flex;
            flex-direction: column;
        }

        .team-photo-frame {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            aspect-ratio: 4 / 3;
            margin-bottom: 1rem;
            border: 1px solid rgba(15, 118, 110, 0.14);
            background: linear-gradient(135deg, rgba(224, 242, 254, 0.9), rgba(240, 253, 250, 0.95));
            box-shadow: inset 0 0 0 8px rgba(255, 255, 255, 0.55);
        }

        .team-photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .team-photo-number {
            position: absolute;
            left: 0.85rem;
            bottom: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 34px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.72);
            color: #ffffff;
            font-weight: 900;
            font-size: 0.82rem;
            backdrop-filter: blur(10px);
        }

        .step-number {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0e7490;
            color: #fff;
            font-weight: 800;
        }

        .retraining-info {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            border: 1px solid rgba(185, 28, 28, 0.14);
            background:
                radial-gradient(circle at top right, rgba(185, 28, 28, 0.12), transparent 34%),
                linear-gradient(135deg, #fff7ed, #ffffff 62%);
            padding: 1.5rem;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .retraining-info-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #991b1b;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 16px 30px rgba(153, 27, 27, 0.22);
        }

        .retraining-mini-list {
            display: grid;
            gap: 0.7rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .retraining-mini-list li {
            display: flex;
            gap: 0.7rem;
            color: #475569;
            line-height: 1.55;
        }

        .retraining-mini-list i {
            color: #991b1b;
            margin-top: 0.25rem;
            flex: 0 0 auto;
        }

        .retraining-step {
            border-radius: 18px;
            border: 1px solid rgba(185, 28, 28, 0.12);
            background: rgba(255, 255, 255, 0.78);
            padding: 1rem;
            height: 100%;
        }

        .retraining-step-number {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(185, 28, 28, 0.1);
            color: #991b1b;
            font-weight: 900;
            margin-bottom: 0.75rem;
        }

        .soft-copy {
            color: #64748b;
            line-height: 1.7;
        }

        @media (max-width: 767.98px) {
            .hero-section {
                min-height: 420px;
                padding: 1.5rem;
            }

            .brain-illustration i {
                font-size: 6rem;
            }
        }
    </style>

    <div class="landing-shell">
        <section class="hero-section">
            <div class="row g-4 align-items-center w-100">
                <div class="col-lg-7">
                    <div class="hero-content">
                        <span class="hero-badge mb-3">
                            <i class="fa-solid fa-heart-pulse"></i>
                            Stroke Risk Prediction
                        </span>
                        <h1 class="display-5 fw-bold mb-3">
                            <i class="fa-solid fa-shield-heart me-2"></i>
                            Sistem Cerdas Prediksi Risiko Stroke Berbasis Machine Learning Berdasarkan Faktor Gaya Hidup dan Riwayat Kesehatan
                        </h1>
                        <p class="lead text-white-50 mb-4">
                            Web ini membantu melakukan screening awal risiko stroke berdasarkan data kesehatan,
                            gaya hidup, dan riwayat medis pengguna. Sistem disiapkan untuk beberapa model machine
                            learning seperti Decision Tree, KNN, dan SVM.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('form') }}" class="btn btn-light btn-lg fw-bold px-4">Mulai Prediksi</a>
                            <a href="#dataset" class="btn btn-outline-light btn-lg fw-bold px-4">Lihat Dataset</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-card">
                        <p class="text-uppercase small fw-bold text-white-50 mb-3">Ringkasan Sistem</p>
                        <div class="hero-list">
                            <div class="hero-list-item">
                                <span>Dataset</span>
                                <span>Healthcare Stroke</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Model</span>
                                <span>{{ $allModelNames }}</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Mode Prediksi</span>
                                <span>Input Form & Upload File</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Status</span>
                                <span>{{ $modelStatusSummary }}</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Output</span>
                                <span>Risiko Rendah/Tinggi</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('form') }}" class="btn btn-light w-100 fw-bold">Coba Prediksi Sekarang</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-md-4">
                <div class="quick-stat">
                    <div class="icon"><i class="fa-solid fa-heart-circle-check"></i></div>
                    <h3 class="h6 fw-bold mb-2">Screening Awal</h3>
                    <p class="soft-copy mb-0">Hasil membantu mengenali potensi risiko, bukan menggantikan diagnosis dokter.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="quick-stat">
                    <div class="icon"><i class="fa-solid fa-stethoscope"></i></div>
                    <h3 class="h6 fw-bold mb-2">Model yang Dipakai</h3>
                    <p class="soft-copy mb-0">Sistem disiapkan untuk {{ $allModelNames }}. Model yang siap akan aktif otomatis untuk prediksi.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="quick-stat">
                    <div class="icon"><i class="fa-solid fa-comments"></i></div>
                    <h3 class="h6 fw-bold mb-2">Penjelasan Mudah</h3>
                    <p class="soft-copy mb-0">Cara kerja tiap model dijelaskan dengan bahasa sederhana agar mudah dipahami pengguna umum.</p>
                </div>
            </div>
        </section>

        <section class="row g-4 align-items-center">
            <div class="col-lg-6">
                <div class="stroke-visual">
                    <div class="brain-illustration" aria-label="Ilustrasi otak dan risiko stroke">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <p class="section-kicker">Informasi Umum</p>
                <h2 class="fw-bold mb-3">Stroke terjadi saat aliran darah ke otak terganggu.</h2>
                <p class="soft-copy mb-3">
                    Stroke dapat disebabkan oleh penyumbatan atau pecahnya pembuluh darah di otak. Risiko bisa dipengaruhi
                    oleh usia, hipertensi, penyakit jantung, kadar glukosa, BMI, kebiasaan merokok, dan faktor gaya hidup lainnya.
                </p>
                <p class="soft-copy mb-0">
                    Hasil prediksi bersifat edukasi dan bukan pengganti diagnosis dokter.
                </p>
            </div>
        </section>

        <section id="models">
            <div class="row align-items-end g-3 mb-4">
                <div class="col-lg-8">
                    <p class="section-kicker">Model Prediksi</p>
                    <h2 class="fw-bold mb-2">Tiga cara sistem membaca data kesehatan.</h2>
                    <p class="soft-copy mb-0">
                        Setiap model punya cara pandang berbeda. Penjelasan di bawah dibuat tanpa angka teknis,
                        supaya fokusnya pada gambaran sederhana tentang bagaimana sistem mengambil keputusan awal.
                    </p>
                </div>
            </div>
            <div class="row g-4">
            @foreach($modelOptions as $key => $model)
                @php
                    $isAvailable = $model['available'] ?? false;
                    $modelLabel = $model['name'] ?? $model['label'];
                    $modelGuide = $modelPlainGuides[$key] ?? [
                        'headline' => 'Seperti membaca pola dari data lama.',
                        'body' => 'Sistem mempelajari contoh data sebelumnya, lalu memakai pola tersebut untuk memberi gambaran risiko pada data baru.',
                        'points' => [
                            'Membaca data kesehatan yang dimasukkan pengguna.',
                            'Membandingkannya dengan pola dari data yang sudah dipelajari.',
                            'Memberi hasil risiko rendah atau tinggi sebagai panduan awal.',
                        ],
                    ];
                @endphp
                <div class="col-md-4">
                    <div class="model-card">
                        <div class="model-card-header">
                            <span class="model-icon">
                                <i class="fa-solid {{ $model['icon'] ?? 'fa-brain' }}"></i>
                            </span>
                            <div>
                                <p class="section-kicker mb-1">{{ $isAvailable ? 'Model Siap' : 'Model Tambahan' }}</p>
                                <h3 class="h5 fw-bold mb-0">{{ $modelLabel }}</h3>
                            </div>
                        </div>
                        <span class="model-status {{ $isAvailable ? 'active' : 'pending' }} mb-3">
                            <i class="fa-solid {{ $isAvailable ? 'fa-circle-check' : 'fa-clock' }}"></i>{{ $model['status_label'] ?? '-' }}
                        </span>
                        @if($isAvailable)
                            <p class="soft-copy mb-0">
                                Model ini membantu membaca data pasien lalu memberi gambaran awal apakah kondisinya lebih dekat ke risiko rendah atau risiko tinggi.
                            </p>
                            <div class="model-work-panel">
                                <p class="model-work-label">{{ $modelGuide['headline'] }}</p>
                                <p class="soft-copy small mb-0">{{ $modelGuide['body'] }}</p>
                            </div>
                            <ul class="model-work-list small">
                                @foreach($modelGuide['points'] as $point)
                                    <li>
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>{{ $point }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="soft-copy mb-0">Model ini belum aktif, jadi belum dipakai untuk membaca data pasien pada halaman prediksi.</p>
                        @endif
                    </div>
                </div>
            @endforeach
            </div>
        </section>

        <section class="action-card">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <p class="section-kicker">Siap Mulai</p>
                    <h2 class="fw-bold mb-2">Mulai prediksi sekarang dengan model aktif.</h2>
                    <p class="soft-copy mb-0">Gunakan hasil sebagai panduan awal dan konsultasikan kondisi kesehatan dengan tenaga medis bila diperlukan.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('form') }}" class="btn btn-dark btn-lg w-100">Mulai Prediksi</a>
                </div>
            </div>
        </section>

        <section class="retraining-info">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="retraining-info-icon">
                            <i class="fa-solid fa-rotate"></i>
                        </span>
                        <div>
                            <p class="section-kicker mb-1">Fitur Retraining</p>
                            <h2 class="fw-bold mb-2">Model bisa diperbarui dari history prediksi web.</h2>
                        </div>
                    </div>
                    <p class="soft-copy mb-3">
                        Retraining adalah proses melatih ulang model machine learning menggunakan history prediksi user.
                        Input user disimpan bersama hasil prediksi, lalu hasil prediksi tersebut dipakai sebagai label <code>stroke</code>.
                    </p>
                    <ul class="retraining-mini-list mb-3">
                        <li>
                            <i class="fa-solid fa-shield-heart"></i>
                            <span>Data retraining diambil dari history prediksi yang tersimpan saat user memakai form atau upload prediksi.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>Label <code>stroke</code> pada dataset retraining diisi dari hasil prediksi sistem sesuai ketentuan fitur.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-lock"></i>
                            <span>Retraining baru aktif jika jumlah data valid cukup dan semua model utama sudah tersedia.</span>
                        </li>
                    </ul>
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('admin.retraining') }}" class="btn btn-dark px-4">
                            <i class="fa-solid fa-database me-2"></i>Buka Panel Retraining
                        </a>
                    @else
                        <span class="status-badge">
                            <i class="fa-solid fa-lock me-1"></i>Retraining hanya untuk admin
                        </span>
                    @endif
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="retraining-step">
                                <span class="retraining-step-number">1</span>
                                <h3 class="h6 fw-bold">Kumpulkan Data</h3>
                                <p class="soft-copy small mb-0">Input user dan hasil prediksi tersimpan ke history.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="retraining-step">
                                <span class="retraining-step-number">2</span>
                                <h3 class="h6 fw-bold">Validasi Sistem</h3>
                                <p class="soft-copy small mb-0">Admin mengambil history valid menjadi pool dataset retraining.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="retraining-step">
                                <span class="retraining-step-number">3</span>
                                <h3 class="h6 fw-bold">Latih Ulang Model</h3>
                                <p class="soft-copy small mb-0">Jika syarat lengkap, model dilatih ulang dari dataset awal + history valid.</p>
                            </div>
                        </div>
                    </div>

                    <div class="dt-simple-note small mt-3">
                        <strong>Catatan:</strong> Retraining penuh dijalankan saat data pool sudah memenuhi syarat.
                        Decision Tree, KNN, dan SVM akan dilatih dari basis data yang sama agar perbandingannya lebih adil.
                    </div>
                </div>
            </div>
        </section>

        <section>
            <p class="section-kicker">Cara Web Bekerja</p>
            <h2 class="fw-bold mb-4">Alur singkat dari input hingga hasil tersimpan.</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">1</span>
                        <h3 class="h6 fw-bold">Register / Login</h3>
                        <p class="soft-copy mb-0">Masuk agar hasil prediksi tersimpan dan bisa dilihat kembali kapan saja.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">2</span>
                        <h3 class="h6 fw-bold">Isi Data Pasien</h3>
                        <p class="soft-copy mb-0">Isi data usia, kondisi medis, BMI, kadar glukosa, pekerjaan, dan kebiasaan merokok.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">3</span>
                        <h3 class="h6 fw-bold">Prediksi ML API</h3>
                        <p class="soft-copy mb-0">Sistem memproses data dengan model aktif untuk menghasilkan risiko rendah/tinggi.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">4</span>
                        <h3 class="h6 fw-bold">Simpan History</h3>
                        <p class="soft-copy mb-0">Hasil tersimpan otomatis dan dapat diakses kembali di halaman riwayat.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-4" id="dataset">
            <div class="col-lg-7">
                <div class="info-card">
                    <p class="section-kicker">Dataset</p>
                    <h2 class="fw-bold mb-3">Healthcare Stroke Prediction Dataset</h2>
                    <p class="soft-copy">
                        Dataset publik dari Kaggle berisi data kesehatan pasien, seperti usia, tekanan darah,
                        kadar glukosa, BMI, kebiasaan merokok, serta label stroke.
                    </p>
                    <a class="dataset-link" href="https://www.kaggle.com/datasets/fedesoriano/stroke-prediction-dataset" target="_blank" rel="noopener noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        Link Kaggle Dataset
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="info-card">
                    <p class="section-kicker">Database</p>
                    <h2 class="fw-bold mb-3">MySQL</h2>
                    <p class="soft-copy mb-0">
                        Data user, session, dan riwayat prediksi disimpan menggunakan database MySQL
                        agar aplikasi siap digunakan secara multi-user.
                    </p>
                </div>
            </div>
        </section>

        <section>
            <p class="section-kicker">Tim Pengembang</p>
            <h2 class="fw-bold mb-4">Tim Pengembang</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-photo-frame">
                            <img src="{{ asset('assets/images/Ali.jpg') }}" alt="Foto Ali Nur Hakim" loading="lazy">
                            <span class="team-photo-number">01</span>
                        </div>
                        <h3 class="h5 fw-bold mb-1">Ali Nur Hakim</h3>
                        <p class="soft-copy mb-0">Laravel & Integrasi Sistem</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-photo-frame">
                            <img src="{{ asset('assets/images/viona.jpg') }}" alt="Foto Viona Deva Qaulika" loading="lazy">
                            <span class="team-photo-number">02</span>
                        </div>
                        <h3 class="h5 fw-bold mb-1">Viona Deva Qaulika</h3>
                        <p class="soft-copy mb-0">Machine Learning & Dataset</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-photo-frame">
                            <img src="{{ asset('assets/images/Sabda.jpg') }}" alt="Foto Sabda Putra Aribawa" loading="lazy">
                            <span class="team-photo-number">03</span>
                        </div>
                        <h3 class="h5 fw-bold mb-1">Sabda Putra Aribawa</h3>
                        <p class="soft-copy mb-0">UI Design & Dokumentasi</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
