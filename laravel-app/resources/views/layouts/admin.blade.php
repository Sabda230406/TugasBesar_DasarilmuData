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
            --admin-bg-panel: #0f3d35;
            --admin-surface: #ffffff;
            --admin-soft: #f6faf8;
            --admin-line: #dce8e2;
            --admin-line-strong: #bfd5cb;
            --admin-text: #10231a;
            --admin-muted: #60736a;
            --admin-brand: #0f8f72;
            --admin-brand-deep: #03624f;
            --admin-brand-soft: #dff8ef;
            --admin-accent: #2563eb;
            --admin-warning: #b45309;
            --admin-danger: #dc2626;
            --admin-slate: #334155;
            --shadow-sm: 0 10px 26px rgba(15, 61, 53, 0.08);
            --shadow-md: 0 22px 54px rgba(15, 61, 53, 0.12);
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--admin-text);
            background:
                linear-gradient(180deg, rgba(5, 46, 36, 0.12), rgba(246, 250, 248, 0) 260px),
                #f6faf8;
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
            border-radius: 8px;
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
            padding: 0;
        }

        .admin-toolbar {
            background: linear-gradient(135deg, var(--admin-bg), var(--admin-bg-soft));
            color: #ecfdf5;
            border: 1px solid rgba(187, 247, 208, 0.2);
            border-radius: 8px;
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
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: var(--shadow-sm);
            padding: 0.5rem;
            min-width: 220px;
        }

        .user-dropdown .dropdown-item {
            border-radius: 6px;
            font-weight: 600;
        }

        .notification-dropdown {
            position: relative;
        }

        .notification-button {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(187, 247, 208, 0.22);
            background: rgba(255, 255, 255, 0.12);
            color: #ecfdf5;
            position: relative;
        }

        .notification-button:hover,
        .notification-button:focus {
            background: var(--admin-brand-soft);
            color: var(--admin-bg);
        }

        .notification-count {
            position: absolute;
            top: -5px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.66rem;
            font-weight: 900;
            border: 2px solid var(--admin-bg);
        }

        .notification-menu {
            width: min(360px, calc(100vw - 1.5rem));
            padding: 0.55rem;
            border-radius: 8px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--shadow-md);
        }

        .notification-menu-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0.55rem 0.7rem;
            border-bottom: 1px solid var(--admin-line);
            margin-bottom: 0.35rem;
        }

        .notification-menu-head span {
            display: block;
            color: var(--admin-muted);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .notification-read-btn {
            color: var(--admin-brand-deep);
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .notification-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.7rem 0.55rem;
            border-radius: 8px;
            color: var(--admin-text);
            text-decoration: none;
        }

        .notification-item:hover,
        .notification-item.is-unread {
            background: #f0fbf7;
        }

        .notification-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
            background: var(--admin-brand-soft);
            color: var(--admin-brand-deep);
        }

        .notification-copy {
            min-width: 0;
        }

        .notification-copy strong,
        .notification-copy small {
            display: block;
        }

        .notification-copy small {
            color: var(--admin-muted);
            font-size: 0.78rem;
            line-height: 1.35;
        }

        .notification-empty {
            padding: 1rem 0.55rem;
            color: var(--admin-muted);
            font-weight: 700;
            text-align: center;
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

        .admin-shell,
        .admin-page-stack {
            display: grid;
            gap: 1.25rem;
        }

        .admin-page-head,
        .admin-hero {
            border: 1px solid rgba(15, 143, 114, 0.16);
            border-radius: 8px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(239, 248, 245, 0.98)),
                linear-gradient(135deg, rgba(15, 143, 114, 0.08), rgba(37, 99, 235, 0.06));
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .admin-page-head h1,
        .admin-hero h1 {
            letter-spacing: 0;
        }

        .admin-page-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--admin-bg);
            color: #dff8ef;
            box-shadow: 0 12px 22px rgba(5, 46, 36, 0.16);
        }

        .admin-panel,
        .admin-card,
        .admin-stat,
        .filter-card,
        .pool-mini-stat {
            border: 1px solid var(--admin-line);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-sm);
        }

        .admin-panel,
        .admin-card {
            padding: 1.25rem;
        }

        .admin-card,
        .admin-stat {
            height: 100%;
        }

        .filter-card {
            padding: 1rem;
            background: #fbfefd;
        }

        .pool-mini-stat {
            padding: 1rem;
            background: #fbfefd;
        }

        .pool-mini-stat span {
            display: block;
            color: var(--admin-muted);
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .pool-mini-stat strong {
            display: block;
            color: var(--admin-text);
            font-size: 1.65rem;
            font-weight: 800;
            margin-top: 0.25rem;
        }

        .pool-mini-stat small {
            color: var(--admin-muted);
        }

        .admin-stat {
            padding: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .admin-stat span {
            display: block;
            color: var(--admin-muted);
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .admin-stat strong {
            display: block;
            color: var(--admin-text);
            font-size: 1.85rem;
            font-weight: 800;
            margin-top: 0.35rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef5ff;
            color: var(--admin-accent);
        }

        .admin-table {
            margin-bottom: 0;
        }

        .admin-table td,
        .admin-table th {
            vertical-align: middle;
        }

        .admin-table thead th {
            border-bottom: 1px solid var(--admin-line-strong);
            padding: 0.85rem 0.65rem;
        }

        .admin-table tbody td {
            border-color: var(--admin-line);
            padding: 0.95rem 0.65rem;
        }

        .admin-table tbody tr {
            transition: background-color 0.16s ease;
        }

        .admin-table tbody tr:hover {
            background: #f8fcfa;
        }

        .table-title {
            font-weight: 800;
            color: var(--admin-text);
        }

        .muted-line {
            color: var(--admin-muted);
            font-size: 0.88rem;
        }

        .entity-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 220px;
        }

        .entity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            flex: 0 0 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eaf3ff;
            color: var(--admin-accent);
            font-weight: 800;
        }

        .entity-avatar.admin {
            background: var(--admin-brand-soft);
            color: var(--admin-brand-deep);
        }

        .role-badge,
        .model-pill,
        .model-chip,
        .status-chip,
        .status-badge,
        .metric-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 999px;
            padding: 0.42rem 0.66rem;
            font-size: 0.78rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .role-badge,
        .model-pill,
        .model-chip,
        .metric-pill {
            background: #eef3f1;
            color: var(--admin-slate);
        }

        .role-badge.admin,
        .model-pill.ready,
        .model-chip.ready,
        .status-chip.status-success,
        .status-badge {
            background: var(--admin-brand-soft);
            color: var(--admin-brand-deep);
        }

        .status-chip.status-primary {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-chip.status-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-chip.status-warning {
            background: #fef3c7;
            color: var(--admin-warning);
        }

        .status-chip.status-secondary,
        .status-chip.status-light {
            background: #eef2f7;
            color: var(--admin-slate);
        }

        .risk-chip.low {
            background: var(--admin-brand-soft);
            color: var(--admin-brand-deep);
        }

        .risk-chip.high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            border: 1px solid var(--admin-line);
            border-radius: 8px;
            padding: 0.95rem;
            text-decoration: none;
            color: var(--admin-text);
            background: #fbfefd;
            font-weight: 800;
            transition: 0.18s ease;
        }

        .quick-action:hover {
            transform: translateY(-1px);
            border-color: rgba(15, 143, 114, 0.34);
            background: #f0fbf7;
            color: var(--admin-brand-deep);
        }

        .quick-action i {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef5ff;
            color: var(--admin-accent);
        }

        .training-loader {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 4px solid #dbeafe;
            border-top-color: var(--admin-brand-deep);
            animation: admin-spin 0.9s linear infinite;
            flex: 0 0 44px;
        }

        .training-loader.done {
            animation: none;
            border-color: var(--admin-brand-soft);
            background: var(--admin-brand-soft);
            color: var(--admin-brand-deep);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .training-loader.failed {
            animation: none;
            border-color: #fee2e2;
            background: #fee2e2;
            color: #b91c1c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .progress-step-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.85rem;
        }

        .progress-step {
            border: 1px solid var(--admin-line);
            border-radius: 999px;
            padding: 0.38rem 0.62rem;
            color: var(--admin-muted);
            background: #fff;
            font-size: 0.76rem;
            font-weight: 800;
        }

        .progress-step.active,
        .progress-step.done {
            color: var(--admin-brand-deep);
            border-color: rgba(15, 143, 114, 0.28);
            background: var(--admin-brand-soft);
        }

        @keyframes admin-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-badge {
            padding: 0.52rem 0.76rem;
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

        .btn {
            border-radius: 8px;
            font-weight: 800;
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
            border-radius: 8px;
            min-height: 44px;
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

        .preview-box,
        .error-box {
            max-width: 100%;
            max-height: 320px;
            white-space: pre-wrap;
            overflow: auto;
            border-radius: 8px;
            font-size: 0.82rem;
        }

        @include('partials.pagination-styles')

        @media (max-width: 767.98px) {
            .admin-frame {
                padding: 0;
            }

            .admin-main {
                padding-top: 1rem;
            }

            .admin-toolbar {
                display: none;
            }

            .admin-page-head,
            .admin-hero,
            .admin-panel,
            .admin-card {
                padding: 1rem;
            }

            .responsive-table thead {
                display: none;
            }

            .responsive-table,
            .responsive-table tbody,
            .responsive-table tr,
            .responsive-table td {
                display: block;
                width: 100%;
            }

            .responsive-table tbody tr {
                border: 1px solid var(--admin-line);
                border-radius: 8px;
                padding: 0.5rem;
                margin-bottom: 0.75rem;
                background: #fff;
            }

            .responsive-table tbody tr.detail-row {
                border: 0;
                padding: 0;
            }

            .responsive-table tbody td {
                border: 0;
                display: grid;
                grid-template-columns: minmax(108px, 38%) 1fr;
                gap: 0.75rem;
                align-items: center;
                padding: 0.55rem 0.35rem;
            }

            .responsive-table tbody td::before {
                content: attr(data-label);
                color: var(--admin-muted);
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .responsive-table tbody td[colspan] {
                display: block;
            }

            .responsive-table tbody td[colspan]::before {
                content: none;
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
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.models*') ? 'active' : '' }}" href="{{ route('admin.models') }}">
                            <i class="fa-solid fa-brain me-1"></i> Models
                        </a>
                    </li>
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mt-lg-0">
                    <span class="admin-badge"><i class="fa-solid fa-lock"></i> Admin Only</span>
                    @auth
                        @include('partials.notification-dropdown')
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
