<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Stroke Risk Admin' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-bg: #052e24;
            --admin-bg-soft: #064e3b;
            --admin-surface: #ffffff;
            --admin-soft: #f4fbf7;
            --admin-line: #d7ebe1;
            --admin-text: #10231a;
            --admin-muted: #5f7469;
            --admin-brand: #16a34a;
            --admin-brand-deep: #047857;
            --admin-brand-soft: #dcfce7;
            --admin-accent: #0f9488;
            --shadow-sm: 0 8px 18px rgba(6, 78, 59, 0.08);
            --shadow-md: 0 24px 48px rgba(6, 78, 59, 0.13);
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--admin-text);
            background:
                radial-gradient(circle at 16% 0%, rgba(34, 197, 94, 0.16), transparent 32%),
                radial-gradient(circle at 88% 8%, rgba(20, 184, 166, 0.12), transparent 30%),
                #f5faf7;
        }

        .admin-navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(5, 46, 36, 0.94);
            border-bottom: 1px solid rgba(187, 247, 208, 0.2);
            box-shadow: 0 10px 28px rgba(5, 46, 36, 0.16);
            backdrop-filter: blur(16px);
        }

        .admin-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #22c55e, #0f9488);
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            box-shadow: 0 10px 24px rgba(34, 197, 94, 0.34);
        }

        .navbar-brand,
        .navbar-brand small {
            color: #ecfdf5;
        }

        .admin-nav .nav-link {
            border-radius: 999px;
            color: #ccebdc;
            font-weight: 700;
            padding: 0.55rem 0.9rem;
            transition: 0.18s ease;
        }

        .admin-nav .nav-link:hover,
        .admin-nav .nav-link.active {
            color: var(--admin-bg);
            background: var(--admin-brand-soft);
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            background: rgba(187, 247, 208, 0.16);
            border: 1px solid rgba(187, 247, 208, 0.24);
            color: #bbf7d0;
            font-weight: 800;
            font-size: 0.76rem;
        }

        .admin-frame {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            border: 1px solid var(--admin-line);
            padding: 2.25rem;
            box-shadow: var(--shadow-md);
        }

        .admin-toolbar {
            background: linear-gradient(135deg, var(--admin-bg), var(--admin-bg-soft));
            color: #ecfdf5;
            border: 1px solid rgba(187, 247, 208, 0.2);
            border-radius: 18px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.75rem;
            box-shadow: var(--shadow-sm);
        }

        .admin-toolbar strong {
            font-weight: 800;
        }

        .admin-main {
            padding: 2rem 0 3rem;
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(187, 247, 208, 0.22);
            color: #ecfdf5;
            font-weight: 700;
            font-size: 0.88rem;
        }

        .user-dropdown .dropdown-toggle {
            border: 1px solid rgba(187, 247, 208, 0.22);
            background: rgba(255, 255, 255, 0.12);
            color: #ecfdf5;
            font-weight: 700;
            padding: 0.45rem 0.85rem;
        }

        .user-dropdown .dropdown-menu {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: var(--shadow-sm);
            padding: 0.5rem;
            min-width: 220px;
        }

        .user-dropdown .dropdown-item {
            border-radius: 10px;
            font-weight: 600;
        }

        .admin-auth-btn {
            border-radius: 999px;
            font-weight: 700;
            padding: 0.55rem 1rem;
        }

        .navbar-toggler {
            border-color: rgba(187, 247, 208, 0.32);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.22);
        }

        .navbar-toggler-icon {
            filter: invert(1) brightness(1.8);
        }

        .eyebrow {
            color: var(--admin-brand-deep);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .section-subtitle {
            color: var(--admin-muted);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.5rem 0.75rem;
            background: var(--admin-brand-soft);
            color: var(--admin-brand-deep);
            font-size: 0.82rem;
            font-weight: 800;
        }

        .btn-dark {
            background: linear-gradient(135deg, var(--admin-brand-deep), var(--admin-bg-soft));
            border-color: transparent;
            box-shadow: 0 10px 20px rgba(4, 120, 87, 0.14);
        }

        .btn-dark:hover,
        .btn-dark:focus {
            background: linear-gradient(135deg, var(--admin-bg-soft), var(--admin-bg));
            border-color: transparent;
        }

        .btn-outline-dark {
            background: #fff;
            border-color: rgba(4, 120, 87, 0.35);
            color: var(--admin-brand-deep);
            font-weight: 700;
        }

        .btn-outline-dark:hover,
        .btn-outline-dark:focus {
            background: var(--admin-brand-deep);
            border-color: var(--admin-brand-deep);
            color: #fff;
        }

        .btn-outline-secondary {
            border-color: var(--admin-line);
            color: var(--admin-muted);
        }

        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background: var(--admin-soft);
            border-color: rgba(4, 120, 87, 0.35);
            color: var(--admin-brand-deep);
        }

        .form-control,
        .form-select {
            border-color: var(--admin-line);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(22, 163, 74, 0.62);
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.14);
        }

        .table thead th {
            color: var(--admin-muted);
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .pagination .page-link {
            color: var(--admin-brand-deep);
        }

        .pagination .active .page-link,
        .pagination .page-link:hover {
            background: var(--admin-brand-deep);
            border-color: var(--admin-brand-deep);
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .admin-frame {
                padding: 1.5rem;
                border-radius: 18px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3 fw-bold" href="{{ route('admin.dashboard') }}">
                <span class="admin-brand-mark">AD</span>
                <span>
                    <span class="d-block lh-sm">Admin Panel</span>
                    <small class="fw-semibold">StrokeRisk</small>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav mx-lg-auto gap-lg-2 admin-nav">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fa-solid fa-shield-halved me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="fa-solid fa-users-gear me-1"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.retraining*') ? 'active' : '' }}" href="{{ route('admin.retraining') }}">
                            <i class="fa-solid fa-rotate me-1"></i> Retraining
                        </a>
                    </li>
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mt-lg-0">
                    <span class="admin-badge"><i class="fa-solid fa-lock"></i> Admin Only</span>
                    @auth
                        <div class="dropdown user-dropdown">
                            <button class="btn dropdown-toggle user-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user"></i>
                                {{ auth()->user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ url('/') }}">
                                        <i class="fa-solid fa-house-chimney me-2"></i>Landing
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="admin-main">
        <div class="container">
            <div class="admin-toolbar">
                <strong>Mode Admin:</strong> Kelola data, retraining, dan akses pengguna secara aman.
            </div>
            <div class="admin-frame">
                @yield('content')
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
