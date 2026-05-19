@extends('layouts.app')

@section('content')
    <style>
        :root {
            --brand-dark: #0f172a;
            --brand-primary: #0f766e;
            --brand-accent: #f59e0b;
            --soft-border: rgba(15, 23, 42, 0.08);
            --soft-shadow: 0 24px 45px rgba(15, 23, 42, 0.08);
        }

        .landing-shell {
            background: radial-gradient(circle at top left, rgba(15, 118, 110, 0.08), transparent 42%),
                radial-gradient(circle at 60% 20%, rgba(245, 158, 11, 0.08), transparent 45%);
            border-radius: 32px;
            padding: 2.5rem;
        }

        .hero-panel {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 2.75rem;
            background: linear-gradient(135deg, #0f172a 0%, #134e4a 55%, #0f766e 100%);
            color: #fff;
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.22);
        }

        .hero-panel::after,
        .hero-panel::before {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .hero-panel::after {
            right: -80px;
            bottom: -90px;
            width: 260px;
            height: 260px;
        }

        .hero-panel::before {
            left: -60px;
            top: -60px;
            width: 160px;
            height: 160px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.55rem 0.95rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .section-title {
            font-size: 0.8rem;
            letter-spacing: 0.16em;
            font-weight: 700;
            color: rgba(15, 23, 42, 0.55);
            text-transform: uppercase;
        }

        .metric-card,
        .feature-card,
        .insight-card,
        .trust-card {
            height: 100%;
            border-radius: 22px;
            background: #fff;
            border: 1px solid var(--soft-border);
            box-shadow: var(--soft-shadow);
        }

        .metric-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--brand-dark);
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.18), rgba(245, 158, 11, 0.18));
            color: var(--brand-primary);
            font-weight: 800;
        }

        .timeline-item {
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid var(--soft-border);
        }

        .cta-panel {
            background: linear-gradient(135deg, rgba(204, 251, 241, 0.9) 0%, rgba(254, 243, 199, 0.95) 100%);
            border-radius: 22px;
            padding: 1.75rem;
        }

        .btn-accent {
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .muted-copy {
            color: rgba(15, 23, 42, 0.65);
        }

        @media (max-width: 767.98px) {
            .landing-shell {
                padding: 1.5rem;
            }

            .hero-panel {
                padding: 1.75rem;
            }
        }
    </style>

    <section class="landing-shell mb-4">
        <div class="hero-panel mb-4">
            <div class="row align-items-center g-4 position-relative" style="z-index: 1;">
                <div class="col-lg-7">
                    <span class="hero-badge mb-3">Stroke Risk Intelligence Platform</span>
                    <h1 class="display-6 fw-bold mb-3">Sistem Cerdas Prediksi Risiko Stroke Berbasis Machine Learning Berdasarkan Faktor Gaya Hidup dan Riwayat Kesehatan.</h1>
                    <p class="mb-4 text-white-50">Sistem ini menggabungkan faktor klinis, gaya hidup, dan riwayat pasien untuk memberikan klasifikasi risiko secara cepat dengan pendekatan machine learning yang transparan.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="/form" class="btn btn-light btn-lg px-4 fw-semibold">Mulai Prediksi</a>
                        <a href="/history" class="btn btn-outline-light btn-lg px-4">Riwayat Prediksi</a>
                        <a href="#insights" class="btn btn-accent btn-lg px-4">Lihat Insight</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="rounded-4 p-4" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.16); backdrop-filter: blur(14px);">
                        <p class="text-uppercase small fw-semibold text-white-50 mb-3">Project Snapshot</p>
                        <div class="d-grid gap-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-white-50">Dataset</span>
                                <span class="fw-semibold">Healthcare Stroke Dataset</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-white-50">Model</span>
                                <span class="fw-semibold">Random Forest</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-white-50">Akurasi</span>
                                <span class="fw-semibold">95%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-white-50">Output</span>
                                <span class="fw-semibold">Risk Classification</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="metric-card p-4">
                    <p class="section-title mb-2">Model Performance</p>
                    <div class="metric-value mb-2">0.95</div>
                    <p class="mb-0 muted-copy">Model memiliki performa stabil untuk kebutuhan klasifikasi awal berbasis data kesehatan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card p-4">
                    <p class="section-title mb-2">Focus Area</p>
                    <div class="h4 fw-bold mb-2">Risk Detection</div>
                    <p class="mb-0 muted-copy">Membantu mengenali potensi stroke dari kombinasi faktor klinis dan gaya hidup.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card p-4">
                    <p class="section-title mb-2">Workflow</p>
                    <div class="h4 fw-bold mb-2">Fast & Traceable</div>
                    <p class="mb-0 muted-copy">Input data pasien, jalankan prediksi, simpan hasil untuk evaluasi berikutnya.</p>
                </div>
            </div>
        </section>

        <section class="row g-4 mb-4" id="insights">
            <div class="col-lg-7">
                <div class="feature-card p-4 p-lg-5">
                    <p class="section-title mb-2">About the System</p>
                    <h3 class="fw-bold mb-3">Pipeline yang rapi untuk mengubah data kesehatan menjadi insight risiko.</h3>
                    <p class="muted-copy mb-4">Halaman ini dirancang untuk tampil profesional dan siap dipakai sebagai demo, presentasi kampus, maupun portfolio data science.</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="timeline-item">
                                <div class="feature-icon mb-3">01</div>
                                <h6 class="fw-bold">Input Data</h6>
                                <p class="mb-0 muted-copy small">Masukkan profil kesehatan dan kebiasaan pasien lewat form prediksi.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="timeline-item">
                                <div class="feature-icon mb-3">02</div>
                                <h6 class="fw-bold">Analisis Model</h6>
                                <p class="mb-0 muted-copy small">Model Random Forest mengolah kombinasi faktor risiko secara otomatis.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="timeline-item">
                                <div class="feature-icon mb-3">03</div>
                                <h6 class="fw-bold">Hasil Prediksi</h6>
                                <p class="mb-0 muted-copy small">Sistem menghasilkan klasifikasi risiko untuk membantu interpretasi awal.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="timeline-item">
                                <div class="feature-icon mb-3">04</div>
                                <h6 class="fw-bold">Riwayat Tersimpan</h6>
                                <p class="mb-0 muted-copy small">Setiap proses dapat dilacak kembali melalui halaman history.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="insight-card p-4 p-lg-5">
                    <p class="section-title mb-2">Key Value</p>
                    <h3 class="fw-bold mb-3">Kenapa tampilan ini lebih siap dipakai?</h3>
                    <div class="d-grid gap-3">
                        <div class="rounded-4 p-3" style="background: #f8fafc;">
                            <h6 class="fw-bold mb-1">Visual premium</h6>
                            <p class="mb-0 small muted-copy">Warna, spacing, dan struktur konten lebih elegan dan meyakinkan.</p>
                        </div>
                        <div class="rounded-4 p-3" style="background: #f8fafc;">
                            <h6 class="fw-bold mb-1">Narasi jelas</h6>
                            <p class="mb-0 small muted-copy">Pengunjung langsung paham tujuan, metode, dan manfaat project.</p>
                        </div>
                        <div class="rounded-4 p-3" style="background: #f8fafc;">
                            <h6 class="fw-bold mb-1">CTA rapi</h6>
                            <p class="mb-0 small muted-copy">Arah pengguna ke form prediksi dan riwayat jadi lebih natural.</p>
                        </div>
                    </div>

                    <div class="cta-panel mt-4">
                        <p class="section-title mb-2">Next Step</p>
                        <h5 class="fw-bold mb-2">Siap lanjut ke proses prediksi?</h5>
                        <p class="mb-3 muted-copy">Coba langsung form prediksi untuk melihat alur sistem end-to-end.</p>
                        <a href="/form" class="btn btn-dark w-100">Buka Form Prediksi</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-lg-8">
                <div class="trust-card p-4 p-lg-5">
                    <p class="section-title mb-2">Project Highlights</p>
                    <h3 class="fw-bold mb-3">Ringkas, meyakinkan, dan siap dibawa presentasi.</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="feature-icon">AI</div>
                                <div>
                                    <h6 class="fw-bold mb-1">ML Ready</h6>
                                    <p class="mb-0 small muted-copy">Pipeline sudah disesuaikan untuk demo cepat dan testing ulang.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="feature-icon">UX</div>
                                <div>
                                    <h6 class="fw-bold mb-1">UX Focused</h6>
                                    <p class="mb-0 small muted-copy">Tampilan menonjolkan ringkasan dan call-to-action utama.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="feature-icon">DB</div>
                                <div>
                                    <h6 class="fw-bold mb-1">Traceable</h6>
                                    <p class="mb-0 small muted-copy">Hasil prediksi tersimpan dan mudah ditinjau ulang.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="feature-icon">API</div>
                                <div>
                                    <h6 class="fw-bold mb-1">API Connected</h6>
                                    <p class="mb-0 small muted-copy">Terhubung dengan layanan ML untuk prediksi real-time.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="trust-card p-4 p-lg-5 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <p class="section-title mb-2">Quick Actions</p>
                        <h4 class="fw-bold mb-3">Lanjutkan workflow Anda.</h4>
                        <p class="muted-copy">Mulai dari input data, cek riwayat, atau siapkan presentasi.</p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="/form" class="btn btn-dark">Prediksi Sekarang</a>
                        <a href="/history" class="btn btn-outline-dark">Buka History</a>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection
