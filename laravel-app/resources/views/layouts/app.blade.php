<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Stroke Risk App' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --app-bg: #f8fafc;
            --surface: #ffffff;
            --sidebar-bg: #0f172a;
            --line: #e2e8f0;
            --text-main: #1e293b;
            --text-soft: #64748b;
            --brand: #0284c7;
            --brand-deep: #0369a1;
            --brand-light: #e0f2fe;
            --accent: #f59e0b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --radius-lg: 20px;
            --radius-md: 12px;
        }

        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--text-main);
            background-color: var(--app-bg);
            -webkit-font-smoothing: antialiased;
        }

        /* Sidebar Modern - Dark Theme for Professional Look */
        .app-sidebar {
            width: 240px;
            background: var(--sidebar-bg);
            color: #fff;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 10px 0 30px rgba(0,0,0,0.05);
        }

        .brand-section {
            padding: 1.75rem 1.25rem 1.25rem;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #fff;
            font-weight: 800;
            font-size: 1.2rem;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.3);
        }

        /* Navigation Style */
        .app-nav {
            padding: 0 1rem;
        }

        .nav-section-title {
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            padding: 0 0.9rem;
            margin-bottom: 0.75rem;
        }

        .nav-icon {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            transition: all 0.2s ease;
        }

        .app-nav .nav-link {
            border-radius: var(--radius-md);
            color: #94a3b8;
            padding: 0.65rem 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .app-nav .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .app-nav .nav-link:hover .nav-icon {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .app-nav .nav-link.active {
            color: #fff;
            background: var(--brand);
            box-shadow: 0 10px 20px -5px rgba(2, 132, 199, 0.4);
        }

        .app-nav .nav-link.active .nav-icon {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        /* Main Content Area */
        .app-main {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto;
            max-height: 100vh;
        }

        /* Refined Topbar */
        .topbar {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.25rem 2rem;
            border: 1px solid var(--line);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        /* Page Container */
        .page-frame {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            min-height: calc(100vh - 200px);
        }

        /* Typography & Components */
        .eyebrow {
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--brand);
            display: block;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            background: var(--brand-light);
            color: var(--brand-deep);
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 100px;
            border: 1px solid rgba(2, 132, 199, 0.1);
        }

        .system-info-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .sidebar-meta {
            font-size: 0.75rem;
            color: rgba(226, 232, 240, 0.65);
        }

        /* Mobile Responsiveness */
        @media (max-width: 991.98px) {
            .app-shell { flex-direction: column; }
            .app-sidebar { width: 100%; min-height: auto; }
            .app-main { padding: 1rem; }
            .page-frame { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="app-shell d-lg-flex">
        <aside class="app-sidebar min-vh-100">
            <div class="brand-section">
                <div class="d-flex align-items-center gap-3">
                    <div class="brand-mark">SR</div>
                    <div>
                        <h6 class="mb-0 fw-bold text-white">StrokeRisk</h6>
                        <p class="mb-0 sidebar-meta">Analytics</p>
                    </div>
                </div>
            </div>

            <div class="app-nav">
                <p class="nav-section-title">Menu</p>
                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="/">
                        <span class="nav-icon"><i class="fa-solid fa-house-chimney"></i></span>
                        <span class="fw-semibold">Landing</span>
                    </a>
                    <a class="nav-link {{ request()->is('form') ? 'active' : '' }}" href="/form">
                        <span class="nav-icon"><i class="fa-solid fa-stethoscope"></i></span>
                        <span class="fw-semibold">Prediksi</span>
                    </a>
                    <a class="nav-link {{ request()->is('history') ? 'active' : '' }}" href="/history">
                        <span class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <span class="fw-semibold">Riwayat</span>
                    </a>
                </nav>
            </div>

            <div class="px-3 mt-auto">
                <div class="system-info-card">
                    <p class="eyebrow" style="color: #38bdf8;">Kapasitas Sistem</p>
                    <p class="small mb-0" style="color: #94a3b8; line-height: 1.6;">
                        Menggunakan algoritma <strong>Random Forest</strong> untuk akurasi diagnosa medis yang optimal.
                    </p>
                </div>
            </div>
        </aside>

        <main class="app-main">
            <div class="container-fluid px-0">
                <div class="topbar">
                    <div>
                        <p class="eyebrow">Dashboard Analytics</p>
                        <h1 class="h4 mb-0 fw-bold text-dark">Sistem Klasifikasi Risiko Stroke</h1>
                    </div>
                    
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end d-none d-md-block border-end pe-4">
                            <div class="small fw-bold">Model Stat</div>
                            <div class="small text-muted">RF Classifier v1.2</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="status-badge">
                                <i class="fa-solid fa-circle-check me-1"></i> Akurasi 95%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="page-frame">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</body>
</html>