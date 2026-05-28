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
            --app-bg: #f5f9fb;
            --surface: #ffffff;
            --surface-soft: #eef6f8;
            --line: #d6e2ea;
            --text-main: #0f1f2f;
            --text-soft: #607086;
            --brand: #0f766e;
            --brand-deep: #0f5e57;
            --brand-light: #dff7f2;
            --accent: #f59e0b;
            --success: #16a34a;
            --shadow-sm: 0 2px 12px rgba(15, 32, 50, 0.06);
            --shadow-md: 0 20px 50px rgba(15, 32, 50, 0.1);
            --radius-lg: 20px;
            --radius-md: 12px;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--text-main);
            background:
                linear-gradient(180deg, rgba(223, 247, 242, 0.55), rgba(246, 248, 251, 0) 360px),
                var(--app-bg);
            -webkit-font-smoothing: antialiased;
        }

        .app-navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid rgba(219, 229, 234, 0.9);
            backdrop-filter: blur(16px);
            box-shadow: var(--shadow-sm);
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #5eead4 0%, #0f766e 100%);
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);
        }

        .navbar-brand {
            color: var(--text-main);
        }

        .navbar-nav .nav-link {
            border-radius: 999px;
            color: var(--text-soft);
            font-weight: 700;
            padding: 0.55rem 0.9rem;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--brand-deep);
            background: var(--brand-light);
        }

        .nav-auth-btn {
            border-radius: 999px;
            font-weight: 700;
            padding: 0.55rem 1rem;
        }

        .app-main {
            padding: 2rem 0 3rem;
        }

        .page-frame {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            padding: 2.25rem;
            box-shadow: var(--shadow-md);
            min-height: calc(100vh - 190px);
        }

        .card-surface {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
        }

        .section-title {
            font-weight: 800;
            color: var(--text-main);
        }

        .section-subtitle {
            color: var(--text-soft);
        }

        .eyebrow {
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--brand);
            display: block;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: var(--brand-light);
            color: var(--brand-deep);
            font-weight: 800;
            font-size: 0.78rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(14, 116, 144, 0.13);
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid var(--line);
            color: var(--text-main);
            font-weight: 700;
            font-size: 0.88rem;
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding: 1rem 0 0.4rem;
            }

            .navbar-nav .nav-link {
                border-radius: var(--radius-md);
            }
        }

        @media (max-width: 767.98px) {
            .app-main {
                padding: 1rem 0 2rem;
            }

            .page-frame {
                padding: 1.25rem;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg app-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3 fw-bold" href="{{ url('/') }}">
                <span class="brand-mark">SR</span>
                <span>
                    <span class="d-block lh-sm">StrokeRisk</span>
                    <small class="text-muted fw-semibold">Prediction System</small>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-lg-auto gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                            <i class="fa-solid fa-house-chimney me-1"></i> Landing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('form') ? 'active' : '' }}" href="{{ route('form') }}">
                            <i class="fa-solid fa-stethoscope me-1"></i> Prediksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('upload') || request()->routeIs('upload.predict') ? 'active' : '' }}" href="{{ route('upload') }}">
                            <i class="fa-solid fa-file-arrow-up me-1"></i> Upload File
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('retraining') || request()->routeIs('retraining.*') ? 'active' : '' }}" href="{{ route('retraining') }}">
                            <i class="fa-solid fa-rotate me-1"></i> Retraining
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('history') ? 'active' : '' }}" href="{{ route('history') }}">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat
                        </a>
                    </li>
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mt-lg-0">
                    @auth
                        <span class="user-pill">
                            <i class="fa-solid fa-user"></i>
                            {{ auth()->user()->name }}
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button class="btn btn-outline-dark nav-auth-btn" type="submit">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-dark nav-auth-btn">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-dark nav-auth-btn">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="app-main">
        <div class="container">
            <div class="page-frame">
                @yield('content')
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
