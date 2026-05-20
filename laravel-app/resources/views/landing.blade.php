@extends('layouts.app')

@section('content')
    @php
        $modelName = $modelMetrics['model_name'] ?? 'Decision Tree';
        $accuracyDisplay = $modelMetrics['accuracy_display'] ?? '91.89%';
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
            font-weight: 600;
            color: #e2f6f3;
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
                            Web ini membantu mengklasifikasikan potensi risiko stroke berdasarkan data kesehatan,
                            gaya hidup, dan riwayat medis pengguna.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('form') }}" class="btn btn-light btn-lg fw-bold px-4">Mulai Prediksi</a>
                            <a href="#dataset" class="btn btn-outline-light btn-lg fw-bold px-4">Lihat Dataset</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-card">
                        <p class="text-uppercase small fw-bold text-white-50 mb-3">Project Snapshot</p>
                        <div class="hero-list">
                            <div class="hero-list-item">
                                <span>Dataset</span>
                                <span>Healthcare Stroke</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Model</span>
                                <span>{{ $modelName }}</span>
                            </div>
                            <div class="hero-list-item">
                                <span>Akurasi</span>
                                <span>{{ $accuracyDisplay }}</span>
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
                    <h3 class="h6 fw-bold mb-2">Screening Cepat</h3>
                    <p class="soft-copy mb-0">Input data pasien dan dapatkan hasil risiko secara cepat dan jelas.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="quick-stat">
                    <div class="icon"><i class="fa-solid fa-stethoscope"></i></div>
                    <h3 class="h6 fw-bold mb-2">Bantu Keputusan</h3>
                    <p class="soft-copy mb-0">Ringkasan risiko membantu edukasi dan langkah pencegahan awal.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="quick-stat">
                    <div class="icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h3 class="h6 fw-bold mb-2">Riwayat Terpantau</h3>
                    <p class="soft-copy mb-0">Semua hasil tersimpan dan bisa dipantau di halaman history.</p>
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

    <section class="row g-4" id="dataset">
            <div class="col-md-4">
                <div class="model-card">
                    <p class="section-kicker">Model</p>
                    <h3 class="h5 fw-bold mb-2">{{ $modelName }} Classifier</h3>
                    <p class="soft-copy mb-0">Model machine learning yang digunakan untuk klasifikasi risiko stroke dari fitur input pasien.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="model-card">
                    <p class="section-kicker">Accuracy</p>
                    <div class="metric-value mb-2">{{ $accuracyDisplay }}</div>
                    <p class="soft-copy mb-0">Nilai akurasi model yang dikirim dari layanan Flask ML API saat prediksi dilakukan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="model-card">
                    <p class="section-kicker">Output</p>
                    <h3 class="h5 fw-bold mb-2">Risiko Rendah / Tinggi</h3>
                    <p class="soft-copy mb-0">Hasil prediksi ditampilkan sebagai label risiko dan tersimpan di halaman riwayat.</p>
                </div>
            </div>
        </section>

        <section class="action-card">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <p class="section-kicker">Siap Mulai</p>
                    <h2 class="fw-bold mb-2">Mulai prediksi sekarang dan lihat hasilnya secara instan.</h2>
                    <p class="soft-copy mb-0">Data Anda aman dan hanya digunakan untuk estimasi risiko.</p>
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
                        <p class="soft-copy mb-0">Laravel mengirim input ke Flask API, lalu model memberikan klasifikasi risiko.</p>
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
