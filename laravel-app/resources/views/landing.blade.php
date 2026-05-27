@extends('layouts.app')

@section('content')
    @php
        $modelName = $modelMetrics['model_name'] ?? 'Decision Tree';
        $formatPercent = function ($value) {
            if (! is_numeric($value)) {
                return null;
            }

            $value = (float) $value;
            if ($value <= 1) {
                $value *= 100;
            }

            return number_format($value, 2) . '%';
        };
        $strokeMetrics = $modelMetrics['classification_report']['1'] ?? [];
        $confusionMatrix = $modelMetrics['confusion_matrix'] ?? [];
        $accuracyDisplay = $modelMetrics['accuracy_display'] ?? 'Belum tersedia';
        $recallDisplay = $formatPercent($strokeMetrics['recall'] ?? null) ?? 'Belum tersedia';
        $f1Display = $formatPercent($strokeMetrics['f1-score'] ?? null) ?? 'Belum tersedia';
        $falseNegative = $confusionMatrix[1][0] ?? null;
        $truePositive = $confusionMatrix[1][1] ?? null;
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

        .model-detail-toggle {
            border-radius: 999px;
            font-weight: 800;
            padding: 0.65rem 0.95rem;
        }

        .model-detail-modal .modal-content {
            border-radius: 22px;
            border: 1px solid rgba(15, 118, 110, 0.18);
            box-shadow: 0 24px 55px rgba(15, 32, 50, 0.22);
        }

        .model-detail-modal .modal-header {
            border-bottom: 1px solid rgba(15, 118, 110, 0.12);
            background: linear-gradient(135deg, rgba(240, 253, 250, 0.95), #ffffff);
            border-top-left-radius: 22px;
            border-top-right-radius: 22px;
        }

        .model-detail-modal .modal-title {
            font-weight: 800;
            color: #0f172a;
        }

        .model-detail-modal .modal-body {
            padding: 1.5rem;
        }

        .model-detail-modal .modal-footer {
            border-top: 1px solid rgba(15, 118, 110, 0.12);
            padding: 1rem 1.5rem 1.25rem;
        }

        .dt-detail-panel {
            border-radius: 16px;
            border: 1px solid rgba(15, 118, 110, 0.16);
            background: linear-gradient(135deg, rgba(240, 253, 250, 0.9), #ffffff);
            padding: 1rem;
        }

        .dt-metric-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .dt-simple-note {
            border-radius: 14px;
            background: rgba(15, 118, 110, 0.08);
            border: 1px solid rgba(15, 118, 110, 0.14);
            padding: 0.85rem;
            color: #334155;
            line-height: 1.6;
        }

        .dt-metric-box {
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 0.8rem;
        }

        .dt-metric-label {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .dt-metric-value {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 900;
            line-height: 1;
        }

        .dt-metric-value.primary {
            color: #0f766e;
        }

        .modal-backdrop.show {
            opacity: 0.5;
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

        .metric-value {
            font-size: 2.6rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }

        .metric-value.primary {
            color: #0f766e;
        }

        .metric-grid {
            display: grid;
            gap: 1rem;
        }

        .metric-explain {
            border: 1px solid rgba(15, 118, 110, 0.16);
            border-radius: 20px;
            padding: 1.4rem;
            height: 100%;
            background: linear-gradient(135deg, rgba(240, 253, 250, 0.9), #ffffff);
            box-shadow: 0 14px 28px rgba(15, 32, 50, 0.06);
        }

        .metric-label {
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .metric-caption {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 0;
        }

        .explain-list {
            display: grid;
            gap: 0.85rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .explain-list li {
            display: flex;
            gap: 0.75rem;
            color: #475569;
            line-height: 1.6;
        }

        .explain-list i {
            color: #0f766e;
            margin-top: 0.25rem;
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

        .team-avatar {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e0f2fe;
            color: #155e75;
            font-weight: 800;
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

            .dt-metric-grid {
                grid-template-columns: 1fr;
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
                            learning, dengan satu model aktif yang tersambung ke API prediksi.
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
                                <span>Model Aktif</span>
                                <span>{{ $modelName }}</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Mode Prediksi</span>
                                <span>Input Form & Upload File</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Status</span>
                                <span>ML API Terhubung</span>
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
                    <h3 class="h6 fw-bold mb-2">Siap Multi-Model</h3>
                    <p class="soft-copy mb-0">Tampilan disiapkan agar model lain seperti KNN dan SVM bisa diintegrasikan berikutnya.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="quick-stat">
                    <div class="icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h3 class="h6 fw-bold mb-2">Evaluasi Transparan</h3>
                    <p class="soft-copy mb-0">Setiap model perlu dibaca dengan metrik yang sesuai, bukan hanya accuracy.</p>
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
                    Stroke dapat disebabkan oleh penyumbatan pembuluh darah atau pecahnya pembuluh darah di otak.
                    Risiko stroke bisa dipengaruhi oleh usia, hipertensi, penyakit jantung, kadar glukosa,
                    BMI, kebiasaan merokok, dan beberapa faktor gaya hidup lain.
                </p>
                <p class="soft-copy mb-0">
                    Sistem ini bukan pengganti diagnosis dokter, tetapi bisa menjadi alat bantu edukasi
                    dan screening awal berbasis data.
                </p>
            </div>
        </section>

    <section class="row g-4" id="models">
            <div class="col-md-4">
                <div class="model-card">
                    <p class="section-kicker">Model Aktif</p>
                    <h3 class="h5 fw-bold mb-2">{{ $modelName }} Classifier</h3>
                    <span class="model-status active mb-3"><i class="fa-solid fa-circle-check"></i>Siap Prediksi</span>
                    <p class="soft-copy mb-3">Model ini tersambung ke Flask ML API dan file <code>model.pkl</code>.</p>
                    <button class="btn btn-outline-dark model-detail-toggle w-100" type="button" data-bs-toggle="modal" data-bs-target="#modelDetailModal">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Cara membaca hasil model
                    </button>
                    <div class="modal fade model-detail-modal" id="modelDetailModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fa-solid fa-circle-info me-2"></i>Cara membaca hasil model</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="dt-detail-panel">
                                        <p class="fw-bold mb-2">Hasil ini adalah screening awal.</p>
                                        <p class="soft-copy small mb-3">
                                            Prediksi membantu memberi gambaran risiko dari data yang diinput. Hasil risiko tinggi
                                            bukan berarti pasti stroke, dan hasil risiko rendah bukan berarti bebas risiko selamanya.
                                        </p>

                                        <div class="dt-simple-note small mb-3">
                                            Model dibuat lebih berhati-hati terhadap kemungkinan risiko tinggi. Jadi, sebagian hasil
                                            bisa muncul sebagai peringatan agar pengguna mempertimbangkan pemeriksaan lanjutan.
                                        </div>

                                        <p class="fw-bold small mb-2">Detail evaluasi model aktif</p>
                                        <div class="dt-metric-grid mb-3">
                                            <div class="dt-metric-box">
                                                <p class="dt-metric-label">Deteksi Risiko Tinggi</p>
                                                <div class="dt-metric-value primary">{{ $recallDisplay }}</div>
                                            </div>
                                            <div class="dt-metric-box">
                                                <p class="dt-metric-label">Keseimbangan Model</p>
                                                <div class="dt-metric-value">{{ $f1Display }}</div>
                                            </div>
                                            <div class="dt-metric-box">
                                                <p class="dt-metric-label">Accuracy Evaluasi</p>
                                                <div class="dt-metric-value">{{ $accuracyDisplay }}</div>
                                            </div>
                                        </div>

                                        <ul class="explain-list small">
                                            <li>
                                                <i class="fa-solid fa-shield-heart"></i>
                                                <span>Jika hasilnya risiko tinggi, sebaiknya lakukan pengecekan lanjutan atau konsultasi tenaga medis.</span>
                                            </li>
                                            @if(is_numeric($falseNegative) && is_numeric($truePositive))
                                                <li>
                                                    <i class="fa-solid fa-chart-simple"></i>
                                                    <span>Pada data uji, model aktif mendeteksi {{ $truePositive }} data risiko tinggi dan melewatkan {{ $falseNegative }} data risiko tinggi.</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="model-card">
                    <p class="section-kicker">Model Tambahan</p>
                    <h3 class="h5 fw-bold mb-2">KNN Classifier</h3>
                    <span class="model-status pending mb-3"><i class="fa-solid fa-clock"></i>UI Ready</span>
                    <p class="soft-copy mb-0">Sudah ditampilkan sebagai opsi, tetapi belum bisa dipakai sampai artefak model dan API-nya tersedia.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="model-card">
                    <p class="section-kicker">Model Tambahan</p>
                    <h3 class="h5 fw-bold mb-2">SVM Classifier</h3>
                    <span class="model-status pending mb-3"><i class="fa-solid fa-clock"></i>UI Ready</span>
                    <p class="soft-copy mb-0">Sudah masuk ke tampilan multi-model, tetapi fungsi prediksinya masih menunggu integrasi backend.</p>
                </div>
            </div>
        </section>

        <section class="action-card">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <p class="section-kicker">Siap Mulai</p>
                    <h2 class="fw-bold mb-2">Mulai prediksi sekarang dengan model aktif.</h2>
                    <p class="soft-copy mb-0">Gunakan hasil prediksi sebagai peringatan awal dan tetap konsultasikan kondisi kesehatan dengan tenaga medis.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('form') }}" class="btn btn-dark btn-lg w-100">Mulai Prediksi</a>
                </div>
            </div>
        </section>

        <section>
            <p class="section-kicker">Cara Web Bekerja</p>
            <h2 class="fw-bold mb-4">Alur prediksi dari input sampai riwayat.</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">1</span>
                        <h3 class="h6 fw-bold">Register / Login</h3>
                        <p class="soft-copy mb-0">User masuk ke sistem agar hasil prediksi tersimpan di akun masing-masing.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">2</span>
                        <h3 class="h6 fw-bold">Isi Data Pasien</h3>
                        <p class="soft-copy mb-0">Form menerima data usia, kondisi medis, BMI, glukosa, pekerjaan, dan kebiasaan merokok.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">3</span>
                        <h3 class="h6 fw-bold">Prediksi ML API</h3>
                        <p class="soft-copy mb-0">Laravel mengirim input ke Flask API, lalu API memprosesnya memakai model aktif yang tersedia.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <span class="step-number mb-3">4</span>
                        <h3 class="h6 fw-bold">Simpan History</h3>
                        <p class="soft-copy mb-0">Hasil prediksi disimpan ke database MySQL dan bisa dilihat kembali.</p>
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
                        Dataset berisi informasi pasien seperti gender, age, hypertension, heart disease,
                        ever married, work type, residence type, average glucose level, BMI, smoking status,
                        dan label stroke.
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
            <p class="section-kicker">Our Team</p>
            <h2 class="fw-bold mb-4">Tim Pengembang</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-avatar mb-3">01</div>
                        <h3 class="h5 fw-bold mb-1">Ali Nur Hakim</h3>
                        <p class="soft-copy mb-0">Laravel & Integrasi Sistem</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-avatar mb-3">02</div>
                        <h3 class="h5 fw-bold mb-1">Viona Deva Qaulika</h3>
                        <p class="soft-copy mb-0">Machine Learning & Dataset</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-avatar mb-3">03</div>
                        <h3 class="h5 fw-bold mb-1">Sabda Putra Aribawa</h3>
                        <p class="soft-copy mb-0">UI Design & Dokumentasi</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
