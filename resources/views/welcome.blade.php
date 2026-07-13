<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --dash-bg: #09090b;
            --dash-panel: #111113;
            --dash-panel-strong: #18181b;
            --dash-soft: #202024;
            --dash-line: rgba(255, 255, 255, .1);
            --dash-line-strong: rgba(255, 255, 255, .16);
            --dash-text: #fafafa;
            --dash-muted: #a1a1aa;
            --dash-faint: #71717a;
            --dash-green: #22c55e;
            --dash-amber: #f59e0b;
            --dash-radius: 8px;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--dash-bg);
            color: var(--dash-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .dashboard-shell {
            display: grid;
            min-height: 100vh;
            grid-template-columns: 280px minmax(0, 1fr);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .04), rgba(255, 255, 255, 0) 340px),
                radial-gradient(circle at 72% 0%, rgba(34, 197, 94, .13), transparent 34rem),
                var(--dash-bg);
        }

        .dashboard-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--dash-line);
            background: rgba(17, 17, 19, .96);
            backdrop-filter: blur(18px);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 1rem;
            border-bottom: 1px solid var(--dash-line);
        }

        .brand-mark {
            display: grid;
            width: 2.25rem;
            height: 2.25rem;
            place-items: center;
            border: 1px solid var(--dash-line-strong);
            border-radius: var(--dash-radius);
            background: linear-gradient(145deg, rgba(255, 255, 255, .14), rgba(255, 255, 255, .03));
            font-weight: 800;
        }

        .brand-title {
            margin: 0;
            font-size: .95rem;
            font-weight: 750;
        }

        .brand-subtitle {
            margin: .12rem 0 0;
            color: var(--dash-muted);
            font-size: .78rem;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            padding: 1rem;
        }

        .sidebar-label {
            margin: .75rem .25rem .35rem;
            color: var(--dash-faint);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .sidebar-link {
            display: flex;
            width: 100%;
            align-items: center;
            gap: .65rem;
            border: 1px solid transparent;
            border-radius: var(--dash-radius);
            padding: .68rem .75rem;
            background: transparent;
            color: var(--dash-muted);
            font: inherit;
            text-align: left;
        }

        .sidebar-link:hover,
        .sidebar-link.is-active {
            border-color: var(--dash-line-strong);
            background: var(--dash-panel-strong);
            color: var(--dash-text);
        }

        .sidebar-link:disabled {
            cursor: not-allowed;
            opacity: .48;
        }

        .sidebar-link:disabled:hover {
            border-color: transparent;
            background: transparent;
            color: var(--dash-muted);
        }

        .sidebar-icon {
            display: grid;
            width: 1.55rem;
            height: 1.55rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 6px;
            background: rgba(255, 255, 255, .07);
            font-size: .78rem;
            font-weight: 800;
        }

        .sidebar-link.is-active .sidebar-icon {
            background: var(--dash-text);
            color: #111113;
        }

        .sidebar-text {
            min-width: 0;
            flex: 1;
            font-weight: 700;
        }

        .count-badge {
            display: attachment-flex;
            min-width: 2rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--dash-line-strong);
            border-radius: 999px;
            padding: .18rem .48rem;
            background: rgba(255, 255, 255, .06);
            color: var(--dash-text);
            font-size: .75rem;
            font-weight: 800;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid var(--dash-line);
        }

        .sidebar-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border: 1px solid var(--dash-line);
            border-radius: var(--dash-radius);
            padding: .75rem;
            background: rgba(255, 255, 255, .035);
        }

        .sidebar-meta span {
            color: var(--dash-muted);
            font-size: .78rem;
        }

        .dashboard-main {
            min-width: 0;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            min-height: 4rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--dash-line);
            background: rgba(9, 9, 11, .78);
            padding: .8rem 1.5rem;
            backdrop-filter: blur(16px);
        }

        .mobile-menu-button {
            display: none;
            width: 2.4rem;
            height: 2.4rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--dash-line-strong);
            border-radius: var(--dash-radius);
            background: var(--dash-panel);
            color: var(--dash-text);
        }

        .topbar-title h1 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }

        .topbar-title p {
            margin: .18rem 0 0;
            color: var(--dash-muted);
            font-size: .82rem;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .user-pill {
            display: attachment-flex;
            max-width: 18rem;
            align-items: center;
            gap: .45rem;
            border: 1px solid var(--dash-line);
            border-radius: 999px;
            padding: .45rem .7rem;
            background: rgba(255, 255, 255, .04);
            color: var(--dash-muted);
            font-size: .82rem;
        }

        .user-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: var(--dash-green);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .14);
        }

        .dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            padding: 1.5rem;
        }

        .auth-panel,
        .notice-panel,
        .stats-card,
        .reports-panel {
            border: 1px solid var(--dash-line);
            border-radius: var(--dash-radius);
            background: linear-gradient(180deg, rgba(255, 255, 255, .055), rgba(255, 255, 255, .025));
            box-shadow: 0 18px 70px rgba(0, 0, 0, .28);
        }

        .auth-panel {
            padding: 1rem;
        }

        .auth-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: .75rem;
            align-items: end;
        }

        .field-label {
            margin-bottom: .35rem;
            color: var(--dash-muted);
            font-size: .78rem;
            font-weight: 700;
        }

        .dashboard-input {
            width: 100%;
            border: 1px solid var(--dash-line-strong);
            border-radius: var(--dash-radius);
            background: rgba(0, 0, 0, .2);
            color: var(--dash-text);
            padding: .62rem .75rem;
            outline: none;
        }

        .dashboard-input:focus {
            border-color: rgba(250, 250, 250, .45);
            box-shadow: 0 0 0 .2rem rgba(250, 250, 250, .08);
        }

        .dashboard-input::placeholder {
            color: var(--dash-faint);
        }

        .dashboard-button {
            display: attachment-flex;
            min-height: 2.5rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--dash-line-strong);
            border-radius: var(--dash-radius);
            padding: .55rem .85rem;
            background: var(--dash-text);
            color: #111113;
            font-size: .86rem;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
        }

        .dashboard-button:hover {
            background: #e4e4e7;
            color: #111113;
        }

        .dashboard-button.secondary {
            background: transparent;
            color: var(--dash-text);
        }

        .dashboard-button.secondary:hover {
            background: rgba(255, 255, 255, .07);
            color: var(--dash-text);
        }

        .dashboard-button.danger {
            color: #fecdd3;
        }

        .notice-panel {
            padding: .8rem 1rem;
            color: var(--dash-muted);
        }

        .notice-panel.success {
            border-color: rgba(34, 197, 94, .36);
            color: #bbf7d0;
        }

        .notice-panel.danger {
            border-color: rgba(251, 113, 133, .36);
            color: #fecdd3;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .9rem;
        }

        .stats-card {
            min-height: 9.5rem;
            padding: 1rem;
        }

        .stats-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1.1rem;
        }

        .stats-label {
            color: var(--dash-muted);
            font-size: .82rem;
            font-weight: 700;
        }

        .stats-token {
            color: var(--dash-muted);
            font-size: .74rem;
            font-weight: 800;
        }

        .stats-value {
            margin: 0;
            color: var(--dash-text);
            font-size: clamp(1.85rem, 4vw, 2.55rem);
            font-weight: 800;
            line-height: 1;
        }

        .stats-caption {
            margin: .75rem 0 0;
            color: var(--dash-muted);
            font-size: .84rem;
        }

        .reports-panel {
            overflow: hidden;
        }

        .reports-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--dash-line);
        }

        .reports-heading h2 {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 800;
        }

        .reports-heading p {
            margin: .24rem 0 0;
            color: var(--dash-muted);
            font-size: .82rem;
        }

        .search-wrap {
            position: relative;
            width: min(24rem, 100%);
        }

        .toolbar-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .65rem;
            width: min(40rem, 100%);
        }

        .toolbar-controls .search-wrap {
            flex: 1;
            width: auto;
        }

        .toolbar-controls .dashboard-button {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .search-wrap .dashboard-input {
            padding-left: 2.15rem;
        }

        .search-symbol {
            position: absolute;
            left: .78rem;
            top: 50%;
            color: var(--dash-faint);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .tabs-scroll {
            overflow-x: auto;
            border-bottom: 1px solid var(--dash-line);
        }

        .report-tabs {
            display: flex;
            width: max-content;
            min-width: 100%;
            gap: .35rem;
            padding: .7rem 1rem;
        }

        .tab-button {
            display: attachment-flex;
            align-items: center;
            gap: .45rem;
            border: 1px solid transparent;
            border-radius: var(--dash-radius);
            padding: .48rem .68rem;
            background: transparent;
            color: var(--dash-muted);
            font: inherit;
            font-size: .82rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .tab-button:hover,
        .tab-button.is-active {
            border-color: var(--dash-line-strong);
            background: rgba(255, 255, 255, .07);
            color: var(--dash-text);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .reports-table th,
        .reports-table td {
            border-bottom: 1px solid var(--dash-line);
            padding: .78rem 1rem;
            vertical-align: middle;
        }

        .reports-table th {
            background: rgba(255, 255, 255, .055);
            color: var(--dash-muted);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .reports-table td {
            color: var(--dash-text);
            font-size: .88rem;
        }

        .reports-table tbody tr:hover {
            background: rgba(255, 255, 255, .035);
        }

        .row-number {
            width: 5rem;
            color: var(--dash-faint) !important;
            font-variant-numeric: tabular-nums;
        }

        .report-title {
            font-weight: 750;
        }

        .category-chip {
            display: attachment-flex;
            border: 1px solid var(--dash-line);
            border-radius: 999px;
            padding: .18rem .5rem;
            background: rgba(255, 255, 255, .04);
            color: var(--dash-muted);
            font-size: .76rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .table-action {
            text-align: right;
        }

        .empty-state {
            display: none;
            padding: 2.2rem 1rem;
            color: var(--dash-muted);
            text-align: center;
        }

        .empty-state.is-visible {
            display: block;
        }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9;
            display: none;
            background: rgba(0, 0, 0, .56);
        }

        body.sidebar-open .sidebar-backdrop {
            display: block;
        }

        @media (max-width: 1080px) {
            .dashboard-shell {
                grid-template-columns: 240px minmax(0, 1fr);
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .dashboard-shell {
                display: block;
            }

            .dashboard-sidebar {
                position: fixed;
                z-index: 10;
                width: min(22rem, calc(100vw - 2rem));
                transform: translateX(-105%);
                transition: transform .2s ease;
            }

            body.sidebar-open .dashboard-sidebar {
                transform: translateX(0);
            }

            .mobile-menu-button {
                display: attachment-flex;
            }

            .topbar {
                align-items: flex-start;
                padding: .85rem 1rem;
            }

            .topbar-actions {
                width: 100%;
                justify-content: space-between;
            }

            .dashboard-content {
                padding: 1rem;
            }

            .reports-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .search-wrap {
                width: 100%;
            }

            .toolbar-controls {
                width: 100%;
                align-items: stretch;
                flex-direction: column;
            }

            .auth-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    @php
        $viewErrors =
            ($errors ?? null) instanceof \Illuminate\Support\ViewErrorBag
                ? $errors
                : new \Illuminate\Support\ViewErrorBag();

        $ascendsSharedReportTotal = collect(
            \Illuminate\Support\Facades\File::allFiles(resource_path('views/ascends/shared')),
        )
            ->filter(fn($file) => $file->getFilename() === 'pdf.blade.php')
            ->count();
    @endphp

    <div class="dashboard-shell">
        <aside class="dashboard-sidebar" aria-label="Navigasi laporan">
            <div class="sidebar-brand">
                <div class="brand-mark">{{ strtoupper(substr(config('app.name', 'R'), 0, 1)) }}</div>
                <div>
                    <p class="brand-title">{{ config('app.name', 'Laravel') }}</p>
                    <p class="brand-subtitle">Report Command Center</p>
                </div>
            </div>
            <nav class="sidebar-nav" id="dashboard-menu">
                <div class="sidebar-label">Menu Laporan</div>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-meta">
                    <span>Total aktif</span>
                    <strong id="dashboard-total">0</strong>
                </div>
            </div>
        </aside>

        <div class="sidebar-backdrop" data-sidebar-close></div>

        <main class="dashboard-main">
            <header class="topbar">
                <div class="topbar-actions">
                    <button class="mobile-menu-button" type="button" data-sidebar-toggle
                        aria-label="Buka menu">☰</button>
                    <div class="topbar-title">
                        <h1>Dashboard Report PDF</h1>
                        <p>Pilih menu, filter sub menu, lalu buka form laporan dari tabel.</p>
                    </div>
                </div>

                <div class="topbar-actions">
                    @auth
                        <div class="user-pill" title="User aktif">
                            <span class="user-dot"></span>
                            <span class="text-truncate">{{ auth()->user()->name ?: auth()->user()->Username }}</span>
                        </div>
                        <form method="POST" action="{{ route('web.logout') }}">
                            @csrf
                            <button type="submit" class="dashboard-button secondary danger">Logout</button>
                        </form>
                    @endauth
                </div>
            </header>

            <section class="dashboard-content">
                @guest
                    <div class="auth-panel">
                        <form method="POST" action="{{ route('web.login') }}" class="auth-form">
                            @csrf
                            <div>
                                <label for="dashboard-username" class="field-label">Username</label>
                                <input type="text" id="dashboard-username" name="username" class="dashboard-input"
                                    required value="{{ old('username') }}">
                            </div>
                            <div>
                                <label for="dashboard-password" class="field-label">Password</label>
                                <input type="password" id="dashboard-password" name="password" class="dashboard-input"
                                    required>
                            </div>
                            <button type="submit" class="dashboard-button">Login</button>
                        </form>
                    </div>
                @endguest

                @if (session('success'))
                    <div class="notice-panel success">{{ session('success') }}</div>
                @endif
                @if ($viewErrors->has('login'))
                    <div class="notice-panel danger">{{ $viewErrors->first('login') }}</div>
                @endif
                @if ($viewErrors->any() && !$viewErrors->has('login'))
                    <div class="notice-panel danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($viewErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="stats-grid" id="dashboard-stats"></div>
                <div class="reports-panel" id="dashboard-reports"></div>
            </section>
        </main>
    </div>

    <div id="legacy-report-source" hidden>
        <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
            <div class="container">
                <a class="navbar-brand fw-semibold" href="/">{{ config('app.name', 'Laravel') }}</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav">
                        <a class="nav-link" href="#ascends-reports">Ascends</a>
                        <a class="nav-link" href="#wps-reports">WPS</a>
                        <a class="nav-link" href="#pps-reports">PPS</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="container py-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    @auth
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="text-success fw-semibold">
                                Login sebagai: {{ auth()->user()->name ?: auth()->user()->Username }}
                            </div>
                            <form method="POST" action="{{ route('web.logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                            </form>
                        </div>
                    @else
                        <h2 class="h5 mb-3">Login</h2>
                        <form method="POST" action="{{ route('web.login') }}" class="row g-3">
                            @csrf
                            <div class="col-md-5">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required
                                    value="{{ old('username') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </div>
                        </form>
                    @endauth
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-2">Dashboard Report PDF</h1>
                    <p class="text-secondary mb-0">
                        Pilih jenis laporan di bawah ini untuk membuka form generate dan download PDF.
                    </p>
                    @if (session('success'))
                        <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
                    @endif
                    @if ($viewErrors->has('login'))
                        <div class="alert alert-danger mt-3 mb-0">{{ $viewErrors->first('login') }}</div>
                    @endif
                    @if ($viewErrors->any() && !$viewErrors->has('login'))
                        <div class="alert alert-danger mt-3 mb-0">
                            <ul class="mb-0 ps-3">
                                @foreach ($viewErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>


            <div id="ascends-reports" class="mb-3">
                <h2 class="h4 fw-bold mb-0 d-flex align-items-center gap-2 flex-wrap">
                    <span>Ascends</span>
                </h2>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">RU HRM</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.ascends.ru.hrm.employee-list.list-karyawan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    List Karyawan RU
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="pps-reports" class="mb-3">
                <h2 class="h4 fw-bold mb-0 d-flex align-items-center gap-2 flex-wrap">
                    <span>PPS</span>
                    <span id="pps-report-total" class="badge text-bg-primary">0 Total Laporan</span>
                </h2>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Bahan Baku</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.bahan-baku.stock-bahan-baku-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stok Bahan Baku V2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.bahan-baku.mutasi-bahan-baku.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Bahan Baku
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Barang Jadi</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.barang-jadi.mutasi-barang-jadi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Barang Jadi
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.barang-jadi.stock-label-barang-jadi-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Barang Jadi V2
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Broker</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.broker.mutasi-broker.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Broker
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.broker.stock-broker.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Broker
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.broker.stock-broker-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Broker V2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.broker.broker-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Broker Produksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Bonggolan</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.bonggolan.mutasi-bonggolan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Bonggolan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.bonggolan.stock-bonggolan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Bonggolan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.bonggolan.stock-bonggolan-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Bonggolan V2
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Crusher</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.crusher.mutasi-crusher.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Crusher
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.crusher.stock-crusher.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Crusher
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.crusher.stock-crusher-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Crusher V2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.crusher.crusher-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Crusher Produksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Gilingan</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.gilingan.mutasi-gilingan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Gilingan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.gilingan.stock-gilingan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Gilingan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.gilingan.stock-gilingan-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Gilingan V2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.gilingan.gilingan-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Gilingan Produksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Inject</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.inject.inject-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Inject Produksi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.inject.packing.packing-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Packing Produksi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.inject.spanner.spanner-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Spanner Produksi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.inject.pasang-kunci.pasang-kunci-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Pasang Kunci Produksi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.inject.hot-stamping.hot-stamping-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Hot Stamping Produksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Mixer</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.mixer.mutasi-mixer.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Mixer
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.mixer.stock-mixer.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Mixer
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.mixer.stock-mixer-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Mixer V2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.mixer.mixer-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Mixer Produksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Washing</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.washing.stock-washing.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Washing
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.washing.stock-washing-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Washing V2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.washing.washing-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Harian Hasil Washing Produksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Furniture WIP</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.furniture-wip.mutasi-furniture-wip.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Furniture WIP
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.furniture-wip.stock-furniture-wip-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Furniture WIP V2
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Komponen Pendukung</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                {{-- <a href="{{ route('reports.pps.semua-label.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Label
                                </a> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Reject</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.reject.mutasi-reject.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Reject
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.reject.stock-reject.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Reject
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Good Transfer</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                {{-- <a href="{{ route('reports.pps.semua-label.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Label
                                </a> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Rekap Produksi</h2>
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.inject.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Inject (FWIP)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.inject-bj.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Inject (BJ)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.hot-stamping-fwip.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Hot Stamping (FWIP)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.packing-bj.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Packing (BJ)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.pasang-kunci-fwip.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Pasang Kunci (FWIP)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.spanner-fwip.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Spanner (FWIP)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.broker.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Broker
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.washing.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Washing
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.mixer.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Mixer
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.gilingan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Gilingan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.rekap-produksi.crusher.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Harian - Crusher
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Semua Label</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.semua-label.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Label
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Stok</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                {{-- <a href="{{ route('reports.pps.semua-label.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Label
                                </a> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Dashboard</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                {{-- <a href="{{ route('reports.pps.semua-label.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Label
                                </a> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Laporan QC</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.qc.qc-harian-bahan-baku.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan QC Harian Bahan Baku
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.qc.qc-harian-broker.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporaan QC Harian Broker
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.qc.qc-harian-washing.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan QC Harian Washing
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.pps.qc.qc-harian-mixer.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporaan QC Harian Mixer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Adjustment</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                {{-- <a href="{{ route('reports.pps.semua-label.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Label
                                </a> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div id="wps-reports" class="mb-3">
                <h2 class="h4 fw-bold mb-0 d-flex align-items-center gap-2 flex-wrap">
                    <span>WPS</span>
                    <span id="wps-report-total" class="badge text-bg-success">0 Total Laporan</span>
                </h2>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Proses Produksi</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi CCAkhir
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-fj-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi FJ
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-laminating-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi Laminating
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-moulding-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi Moulding
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-packing-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi Packing
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-sanding-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi Sanding
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.proses-produksi.produksi-s4s-per-nomor-produksi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per Nomor Produksi S4S
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Cross Cut Akhir</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.cross-cut-akhir.umur-cc-akhir-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur CCAkhir Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.cross-cut-akhir.cc-akhir-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Cross Cut Akhir (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.cross-cut-akhir.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Cross Cut Akhir
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.cca-akhir.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi CC Akhir
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.cross-cut-akhir.rekap-produksi-cc-akhir-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi CCAkhir Consolidated
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.cross-cut-akhir.rekap-produksi-cc-akhir-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi CCAkhir Per-Jenis &amp; Per-Grade (m3)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.cross-cut-akhir.ketahanan-barang-cc-akhir.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang CCAkhir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Sanding</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sanding.umur-sanding-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Sanding Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sanding.sanding-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Sanding (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.sanding.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Sanding
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.sanding.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Sanding
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sanding.rekap-produksi-sanding-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Sanding Consolidated
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sanding.rekap-produksi-sanding-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Sanding Per-Jenis &amp; Per-Grade (m3)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sanding.ketahanan-barang-sanding.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang Sanding
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Reproses</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.reproses.umur-reproses-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Reproses Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.reproses.reproses-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Reproses (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.reproses.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Reproses</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.reproses.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Reproses
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.reproses.ketahanan-barang-reproses.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang Reproses
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Barang Jadi</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Barang Jadi Per-Jenis Per-Ukuran (M3)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.barang-jadi.saldo-barang-jadi-hidup-per-jenis-per-produk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Saldo Barang Jadi Hidup Per-Jenis Per-Produk
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.barang-jadi.barang-jadi-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Barang Jadi (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.barang-jadi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Barang Jadi</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.barang-jadi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Barang Jadi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.barang-jadi.umur-barang-jadi-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Barang Jadi Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.barang-jadi.rekap-produksi-barang-jadi-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Packing Consolidated
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.barang-jadi.rekap-produksi-packing-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Packing Per-Jenis Per-Grade (m3)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Rendemen Kayu</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.rendemen-kayu.rekap-rendemen-non-rambung.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Rendemen Non Rambung
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.rendemen-kayu.rekap-rendemen-rambung.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Rendemen Rambung
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.rendemen-kayu.rendemen-semua-proses.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rendemen Semua Proses
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.rendemen-kayu.produksi-per-spk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Per SPK
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Penjualan Kayu</h2>
                        <div class="row g-2 mb-4">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan-kayu.penjualan-lokal.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penjualan Lokal
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan.penjualan-barang-jadi-m3.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penjualan Barang Jadi (M3)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan.surat-jalan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Surat Jalan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan-kayu.koordinat-tanah.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Koordinat Tanah
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan-kayu.rekap-penjualan-per-produk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Penjualan Per-Produk
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan-kayu.timeline-rekap-penjualan-per-produk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Timeline Rekap Penjualan Per-Produk
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan-kayu.rekap-penjualan-ekspor-per-produk-per-buyer.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Penjualan Ekspor Per-Produk dan Per-Buyer
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.penjualan-kayu.rekap-penjualan-ekspor-per-buyer-per-produk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Penjualan Ekspor Per-Buyer dan Per-Produk
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Management</h2>
                        <div class="row g-2 mb-4">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.stock-hidup-per-nospk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Hidup Per NoSPK
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.stock-hidup-per-nospk-discrepancy.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Hidup Per NoSPK (Discrepancy)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.discrepancy-rekap-mutasi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Discrepancy Rekap Mutasi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.rekap-mutasi.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Mutasi
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.rekap-mutasi-cross-tab.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Mutasi (Cross Tab)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.flow-produksi-per-periode.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Flow Produksi Per-Periode
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.dashboard-ru.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard RU
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.produksi-semua-mesin.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Semua Mesin
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.produksi-hulu-hilir.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Produksi Hulu Hilir
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.hasil-produksi-mesin-lembur-dan-non-lembur.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Hasil Produksi Mesin Lembur Dan Non Lembur
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.label-perhari.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Label Perhari
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.management.rekap-stock-on-hand.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Stock On Hand
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Verifikasi</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.rangkuman-label-input.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rangkuman Jumlah Label Input
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.label-nyangkut.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Label Nyangkut
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.bahan-terpakai.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Bahan Terpakai
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.verifikasi.rangkuman-bongkar-susun.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rangkuman Bongkar Susun
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.verifikasi.bahan-yang-dihasilkan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rangkuman Bahan Yang Di Hasilkan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.verifikasi.kapasitas-racip-kayu-bulat-hidup.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Kapasitas Racip Kayu Bulat Hidup (Ton)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">S4S</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.umur-s4s-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Umur S4S Detail</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.rekap-produksi-s4s-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Rekap Produksi S4S
                                    Consolidated</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.s4s-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan S4S (Hidup) Detail</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.label-s4s-hidup-per-jenis-kayu.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Label S4S (Hidup)
                                    Per-Jenis
                                    Kayu</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.s4s.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Mutasi S4S</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.s4s.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard S4S
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.s4s-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard S4S v2
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.label-s4s-hidup-per-produk-per-jenis-kayu.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Label S4S (Hidup)
                                    Per-Produk &
                                    Per-Jenis Kayu</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.rekap-produksi-s4s-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Rekap Produksi S4S
                                    Per-Jenis &
                                    Per-Grade (m3)</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.ketahanan-barang-s4s.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Ketahanan Barang Dagang
                                    S4S</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.output-produksi-s4s-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Output Produksi S4S Per
                                    Grade</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.grade-abc-harian.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Grade ABC Harian</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.s4s.rekap-produksi-rambung-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Rekap Produksi Rambung Per
                                    Grade</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Finger Joint</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.finger-joint.umur-finger-joint-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Umur Finger Joint
                                    Detail</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.finger-joint.rekap-produksi-finger-joint-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Finger Joint Consolidated</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.finger-joint.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Finger Joint</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.finger-joint.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Finger Joint
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Finger Joint Per-Jenis & Per-Grade (m3)</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.finger-joint.finger-joint-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Finger Joint (Hidup)
                                    Detail</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.finger-joint.ketahanan-barang-finger-joint.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang Finger Joint</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Laminating</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.laminating.umur-laminating-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Laminating Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.laminating.rekap-produksi-laminating-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Laminating Consolidated
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.laminating.laminating-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Laminating (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.laminating.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Laminating
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.laminating.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Laminating
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.laminating.rekap-produksi-laminating-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Laminating Per-Jenis & Per-Grade (m3)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.laminating.ketahanan-barang-laminating.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang Laminating
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Moulding</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.moulding.umur-moulding-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Moulding Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.moulding.rekap-produksi-moulding-consolidated.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Moulding Consolidated
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.moulding.moulding-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Moulding (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.moulding.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Moulding</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.moulding.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dashboard Moulding
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.moulding.rekap-produksi-moulding-per-jenis-per-grade.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produksi Moulding Per-Jenis & Per-Grade (m3)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.moulding.ketahanan-barang-moulding.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang Moulding
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Kayu Bulat</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.saldo.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Kayu Bulat Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.rekap-pembelian.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Pembelian Kayu Bulat (Chart)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.target-masuk-bb.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Target Masuk Bahan Baku Harian
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.target-masuk-bb-bulanan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Target Masuk Bahan Baku Bulanan
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.kayu-bulat.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Mutasi Kayu Bulat</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.kayu-bulat-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Mutasi Kayu Bulat
                                    Gantung</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-bulanan-per-supplier.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat Bulanan Per Supplier/Hari
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-bulanan-per-supplier-grafik.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat Per Supplier Bulanan (Grafik)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-per-supplier-group.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat Per-Supplier Berdasarkan Group Kayu
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-kayu-bulat-int-ton.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat - (Int Ton)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-kayu-bulat-ext-ton.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat - (Ext Ton)
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi-hasil-racip.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Mutasi Hasil Racip</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi-racip-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">Laporan Mutasi Racip Detail</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.stock-opname.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock Opname Kayu Bulat
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.hidup-per-group.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Hidup Kayu Bulat Per Group
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.hidup.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Kayu Bulat Hidup
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Perbandingan KB Masuk Periode 1 & 2
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.kb-khusus-bangkang.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan KB Khusus Bangkang
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.balok-sudah-semprot.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Balok Sudah Semprot
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.stock-racip-kayu-lat.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stok Racip Kayu Lat
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.hasil-output-racip-harian.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Hasil Output Racip Harian
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.timeline-kayu-bulat-harian.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Time Line Kayu Bulat - Harian (JTG/PLI)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.timeline-kayu-bulat-bulanan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Time Line Kayu Bulat - Bulanan (JTG/PLI)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.umur-kayu-bulat-non-rambung.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Kayu Bulat (NON RAMBUNG)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Kayu Bulat (Rambung)</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.rekap-rendemen-rambung-per-supplier.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Rendemen Rambung Per Supplier
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.umur-kayu-bulat-rambung.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Kayu Bulat (Rambung)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.supplier-intel.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Supplier Intel
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-kayu-bulat-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat - (Int KG)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-kayu-bulat-extkg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat - (Ext KG)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.kayu-bulat-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Kayu Bulat - Timbang KG</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.kayu-bulat-kgv2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Kayu Bulat (Gantung) - Timbang KG</a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.penerimaan-per-supplier-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat Per-Supplier - Timbang KG
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.saldo-hidup-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Saldo Hidup Kayu Bulat - Timbang KG
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.rekap-pembelian-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Pembelian Kayu Bulat (Ton) - Timbang KG
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.rekap-penerimaan-st-dari-sawmill-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Penerimaan ST Dari Sawmill - Timbang KG
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.rekap-produktivitas-sawmill-rp.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produktivitas Sawmill
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.timeline-kayu-bulat-bulanan-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Time Line KB - Bulanan (Rambung)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.timeline-kayu-bulat-harian-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Time Line KB - Harian (Rambung)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Perbanding KB Masuk Periode 1 & 2 - Timbang KG
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Sawn Timber</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.stock-st-basah.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock ST Basah
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.stock-st-kering.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stock ST Kering
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-basah-hidup-per-umur-kayu-ton.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Basah Hidup Per-Umur Kayu (Ton)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-hidup-kering.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Hidup Kering
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.kd-keluar-masuk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan KD (Keluar - Masuk)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.mutasi.st.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi Sawn Timber </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.sawn-timber.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Dasboard Sawn Timber
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.kd-upah-per-customer.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan KD Upah Per-Cutomer
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.kd-upah-per-no-proc-kd-per-customer-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan KD Upah Per-No.Proses KD Per-Cutomer Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.serah-terima-st-kamar-kd.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Serah Terima ST (Kamar KD)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-kamar-kd.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Kamar KD
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.mutasi-kd.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Mutasi KD
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-st-penjualan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap ST Penjualan
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.pembelian-st-per-supplier-ton.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Pembelian ST Per Supplier (Ton)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.pembelian-st-timeline-ton.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Pembelian ST Time Line (Ton)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.label-st-hidup-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Label ST (Hidup) Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.ketahanan-barang-st.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Ketahanan Barang Dagang ST
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-rambung-mc1-mc2-detail.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Rambung MC1 dan MC2 (Detail)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-rambung-mc1-mc2-rangkuman.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Rambung MC1 dan MC2 (Rangkuman)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.penerimaan-st-dari-sawmill-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan ST Dari Sawmill - Timbang KG
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.penerimaan-st-hasil-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan ST Hasil Sawmill
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-penerimaan-st-dari-sawmill-non-rambung.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Penerimaan ST Dari Sawmill (Non Rambung)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-v2.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Hasil Sawmill Per-Meja (Semua Meja)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Hasil Sawmill Per-Meja (Upah Borongan)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-hasil-sawmill-per-meja.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Hasil Sawmill / Meja
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-produktivitas-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Rekap Produktivitas Sawmill
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.pemakaian-obat-vacuum.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Pemakaian Obat Vacuum
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.lembar-tally-hasil-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Lembar Tally Hasil Sawmill
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.detail-lembar-tally-hasil-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Tally Hasil Sawmill Detail
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.rekap-pcs-telly-hasil-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Rekap Jumlah (Pcs) Telly Hasil Sawmill
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.tracing-st.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Tracing ST
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.total-bagus-kulit-rambung.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Total Bagus/Kulit Rambung
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.qc-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan QC Sawmill
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.qc-sawmill-discrepancy.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan QC Sawmill - Discrepancy
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.qc-sawmill-summary.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan QC Sawmill - Summary
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.stok-opname-st-detail-kd.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Stok Opname ST Detail Pada KD
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.umur-sawn-timber-detail-ton.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Umur Sawn Timber Detail (Ton)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-sawmill-masuk-per-group.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Masuk Per-Group
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-sawmill-masuk-per-group-meja.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST (Sawmill) Masuk Per-Group
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.saldo-st-hidup-per-produk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Saldo ST Hidup Per-Jenis Per-Tebal (Per-Group Jenis Kayu)
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-hidup-per-spk.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Hidup per SPK, per Jenis, per Tebal, per Group Jenis Kayu
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.st-sawmill-hari-tebal-lebar.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan ST Sawmill / Hari / Tebal / Lebar
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.spk.spk-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan SPK Sawmill
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">SPK</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.spk.spk-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan SPK Sawmill
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const legacySource = document.getElementById('legacy-report-source');
            const menuMount = document.getElementById('dashboard-menu');
            const statsMount = document.getElementById('dashboard-stats');
            const reportsMount = document.getElementById('dashboard-reports');
            const dashboardTotal = document.getElementById('dashboard-total');
            const body = document.body;
            const menuOrder = ['ascends', 'wps', 'pps'];
            const auditedTotals = {
                ascends: {{ $ascendsSharedReportTotal }},
            };
            const legacyTotals = {
                ascends: 5,
                wps: 218,
                pps: 70,
            };

            const normalize = (value) => value.toLowerCase().replace(/\s+/g, ' ').trim();
            const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);
            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const collectMenu = (key) => {
                const header = legacySource.querySelector(`#${key}-reports`);
                const categories = [];
                const categoryMap = new Map();
                let current = header?.nextElementSibling;

                while (current && !current.id?.endsWith('-reports')) {
                    const title = current.querySelector('.card-body > h2')?.textContent.trim();
                    const links = Array.from(current.querySelectorAll(
                        'a.btn.btn-outline-primary.w-100.text-start'));

                    if (title && links.length > 0) {
                        const reports = links.map((link) => ({
                            label: link.textContent.replace(/\s+/g, ' ').trim(),
                            href: link.getAttribute('href'),
                        })).filter((report) => report.label && report.href);

                        if (reports.length > 0) {
                            if (!categoryMap.has(title)) {
                                categoryMap.set(title, []);
                                categories.push({
                                    name: title,
                                    reports: categoryMap.get(title)
                                });
                            }

                            categoryMap.get(title).push(...reports);
                        }
                    }

                    current = current.nextElementSibling;
                }

                categories.forEach((category) => {
                    category.reports.sort((a, b) => normalize(a.label).localeCompare(normalize(b
                        .label)));
                });
                categories.sort((a, b) => normalize(a.name).localeCompare(normalize(b.name)));

                const total = categories.reduce((sum, category) => sum + category.reports.length, 0);
                const auditedTotal = auditedTotals[key] ?? total;

                return {
                    key,
                    label: key === 'wps' ? 'WPS' : key === 'pps' ? 'PPS' : 'Ascends',
                    legacyTotal: legacyTotals[key],
                    total: auditedTotal,
                    categories,
                };
            };

            const menus = menuOrder.map(collectMenu);
            const menuByKey = new Map(menus.map((menu) => [menu.key, menu]));
            let activeMenuKey = 'wps';
            let activeTab = 'all';
            let searchValue = '';

            dashboardTotal.textContent = formatNumber(menus.reduce((sum, menu) => sum + menu.total, 0));

            const renderSidebar = () => {
                menuMount.querySelectorAll('[data-menu-target]').forEach((item) => item.remove());

                menus.forEach((menu) => {
                    const isDisabled = menu.key === 'ascends';
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `sidebar-link ${menu.key === activeMenuKey ? 'is-active' : ''}`;
                    button.dataset.menuTarget = menu.key;
                    button.disabled = isDisabled;
                    button.title = isDisabled ? 'Menu Ascends sementara dinonaktifkan' :
                        `Buka menu ${menu.label}`;
                    button.innerHTML = `
                        <span class="sidebar-icon">${menu.label.charAt(0)}</span>
                        <span class="sidebar-text">${menu.label}</span>
                        <span class="count-badge">${formatNumber(menu.total)}</span>
                    `;
                    button.addEventListener('click', () => {
                        if (isDisabled) {
                            return;
                        }

                        activeMenuKey = menu.key;
                        activeTab = 'all';
                        searchValue = '';
                        body.classList.remove('sidebar-open');
                        renderDashboard();
                    });
                    menuMount.appendChild(button);
                });
            };

            const renderStats = (menu) => {
                const gap = Math.max(0, menu.legacyTotal - menu.total);

                statsMount.innerHTML = `
                    <article class="stats-card">
                        <div class="stats-header">
                            <span class="stats-label">Laporan Baru</span>
                            <span class="stats-token">Aktif</span>
                        </div>
                        <p class="stats-value">${formatNumber(menu.total)}</p>
                        <p class="stats-caption"><strong>${menu.label}</strong> laporan sudah dibuat.</p>
                    </article>
                    <article class="stats-card">
                        <div class="stats-header">
                            <span class="stats-label">Crystal Report / Laporan Lama</span>
                            <span class="stats-token">Total Laporan</span>
                        </div>
                        <p class="stats-value">${formatNumber(menu.legacyTotal)}</p>
                        <p class="stats-caption">Total laporan lama.</p>
                    </article>
                    <article class="stats-card">
                        <div class="stats-header">
                            <span class="stats-label">Selisih</span>
                            <span class="stats-token">Total Selisih</span>
                        </div>
                        <p class="stats-value">${formatNumber(gap)}</p>
                        <p class="stats-caption">Selisih jumlah laporan.</p>
                    </article>
                `;
            };

            const visibleReports = (menu) => {
                const needle = normalize(searchValue);
                return menu.categories.flatMap((category) => {
                    if (activeTab !== 'all' && category.name !== activeTab) {
                        return [];
                    }

                    return category.reports
                        .filter((report) => !needle || normalize(`${report.label} ${category.name}`)
                            .includes(needle))
                        .map((report) => ({
                            ...report,
                            category: category.name
                        }));
                });
            };

            const exportReportsPdf = (menu, reports) => {
                const generatedAt = new Date().toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
                const printWindow = window.open('', '_blank');
                const statCards = Array.from(statsMount.querySelectorAll('.stats-card')).map((card) => ({
                    label: card.querySelector('.stats-label')?.textContent.trim() || '',
                    token: card.querySelector('.stats-token')?.textContent.trim() || '',
                    value: card.querySelector('.stats-value')?.textContent.trim() || '',
                    caption: card.querySelector('.stats-caption')?.textContent.trim() || '',
                }));

                if (!printWindow) {
                    alert('Popup export PDF diblokir browser. Izinkan popup lalu coba lagi.');
                    return;
                }

                const pageTitle = `List Laporan ${menu.label}`;
                const companyName = 'Utama Corporation';
                const reportRows = reports.map((report, index) => ({
                    no: index + 1,
                    name: report.label,
                    level: report.category,
                }));
                const splitIndex = Math.ceil(reportRows.length / 2);
                const leftRows = reportRows.slice(0, splitIndex);
                const rightRows = reportRows.slice(splitIndex);
                const pairedRows = leftRows.map((left, index) => ({
                    left,
                    right: rightRows[index] ?? null,
                }));
                const statColors = ['#4f46e5', '#16a34a', '#ea580c'];

                const doc = printWindow.document;
                doc.open();
                doc.write(
                    '\x3c!doctype html>\x3chtml lang="id">\x3chead>\x3cmeta charset="utf-8">\x3ctitle>\x3c/title>\x3c/head>\x3cbody>\x3c/body>\x3c/html>'
                    );
                doc.close();
                doc.title = pageTitle;

                const printStyle = doc.createElement('style');
                printStyle.textContent = `
                    @page { size: A4 portrait; margin: 0; }
                    * { box-sizing: border-box; }
                    body {
                        margin: 0;
                        color: #111827;
                        background: #ffffff;
                        font-family: Arial, Helvetica, sans-serif;
                        font-size: 13px;
                    }
                    .export-cover { background: #09111f; color: #ffffff; padding: 22mm 14mm 18mm; }
                    .cover-inner { display: flex; align-items: center; justify-content: space-between; gap: 18mm; }
                    .brand-block { display: flex; align-items: center; gap: 12px; }
                    .brand-mark {
                        display: grid;
                        width: 44px;
                        height: 44px;
                        place-items: center;
                        background: #ffffff;
                        color: #111827;
                        font-size: 17px;
                        font-weight: 800;
                    }
                    .brand-title { margin: 0; font-size: 24px; font-weight: 800; line-height: 1; }
                    .brand-subtitle,
                    .document-label {
                        color: #cbd5e1;
                        font-size: 13px;
                        letter-spacing: .02em;
                        text-transform: uppercase;
                    }
                    .document-title { margin-top: 4px; font-size: 21px; font-weight: 800; }
                    .accent-bar { display: flex; height: 7px; }
                    .accent-red { flex: 3; background: #b91c1c; }
                    .accent-orange { flex: 2; background: #f97316; }
                    .export-body { padding: 13mm 14mm 16mm; }
                    .meta-grid {
                        display: grid;
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                        gap: 18mm;
                        padding-bottom: 11mm;
                        border-bottom: 2px solid #e2e8f0;
                    }
                    .meta-label,
                    .summary-label {
                        color: #64748b;
                        font-weight: 800;
                        text-transform: uppercase;
                    }
                    .meta-label { margin-bottom: 4px; font-size: 13px; }
                    .meta-value { color: #111827; font-size: 17px; line-height: 1.25; }
                    .section-title {
                        display: flex;
                        align-items: center;
                        gap: 9px;
                        margin: 11mm 0 7mm;
                        color: #111827;
                        font-size: 17px;
                        font-weight: 800;
                        text-transform: uppercase;
                    }
                    .section-title::before {
                        content: "";
                        width: 5px;
                        height: 18px;
                        background: #b91c1c;
                    }
                    .summary-grid {
                        display: grid;
                        grid-template-columns: repeat(${Math.max(statCards.length, 1)}, minmax(0, 1fr));
                        gap: 6mm;
                    }
                    .summary-card {
                        min-height: 22mm;
                        border: 1px solid #dbe3ec;
                        border-top: 4px solid var(--summary-color);
                        padding: 5mm 6mm;
                    }
                    .summary-label { font-size: 13px; }
                    .summary-value {
                        margin-top: 1px;
                        color: var(--summary-color);
                        font-size: 26px;
                        font-weight: 800;
                        line-height: 1;
                    }
                    .report-table {
                        width: 100%;
                        border-collapse: collapse;
                        table-layout: fixed;
                        font-size: 12px;
                    }
                    .report-table th,
                    .report-table td {
                        border: 1px solid #dbe3ec;
                        padding: 5px 7px;
                        vertical-align: middle;
                    }
                    .report-table th {
                        background: #f1f5f9;
                        color: #64748b;
                        font-size: 12px;
                        text-align: left;
                        text-transform: uppercase;
                    }
                    .report-table .unit-level {
                        width: 112px;
                        color: #64748b;
                        font-size: 11px;
                        font-weight: 800;
                        text-align: center;
                        text-transform: uppercase;
                        overflow-wrap: anywhere;
                    }
                    .unit-name { width: calc((100% - 224px) / 2); overflow-wrap: break-word; }
                    .unit-prefix { color: #64748b; font-weight: 800; }
                    .row-even td { background: #ffffff; }
                    .row-odd td { background: #edf1f6; }
                    .empty-message {
                        border: 1px solid #dbe3ec;
                        padding: 12mm;
                        background: #edf1f6;
                        color: #64748b;
                        font-weight: 800;
                        text-align: center;
                    }
                    .print-footer {
                        display: flex;
                        justify-content: space-between;
                        gap: 12px;
                        margin: 12mm 14mm 0;
                        border-top: 2px solid #e2e8f0;
                        padding-top: 6px;
                        color: #64748b;
                        font-size: 11px;
                    }
                `;
                doc.head.appendChild(printStyle);

                const append = (parent, tag, className = '', text = '') => {
                    const node = doc.createElement(tag);
                    if (className) {
                        node.className = className;
                    }
                    if (text !== '') {
                        node.textContent = text;
                    }
                    parent.appendChild(node);

                    return node;
                };
                const addMeta = (parent, label, value) => {
                    const item = append(parent, 'div');
                    append(item, 'div', 'meta-label', label);
                    append(item, 'div', 'meta-value', value);
                };
                const addSectionTitle = (parent, text) => append(parent, 'div', 'section-title', text);
                const addReportCell = (row, report) => {
                    const nameCell = append(row, 'td', 'unit-name');
                    const prefix = append(nameCell, 'span', 'unit-prefix', `${report.no}.`);
                    nameCell.appendChild(doc.createTextNode(` ${report.name}`));
                    append(row, 'td', 'unit-level', report.level);

                    return prefix;
                };

                const page = append(doc.body, 'div', 'export-page');
                const cover = append(page, 'header', 'export-cover');
                const coverInner = append(cover, 'div', 'cover-inner');
                const brandBlock = append(coverInner, 'div', 'brand-block');
                append(brandBlock, 'div', 'brand-mark', 'UC');
                const brandText = append(brandBlock, 'div');
                append(brandText, 'p', 'brand-title', companyName);
                append(brandText, 'div', 'brand-subtitle', 'Indonesia');
                const docTitle = append(coverInner, 'div');
                docTitle.style.textAlign = 'right';
                append(docTitle, 'div', 'document-label', 'Dokumen Resmi');
                append(docTitle, 'div', 'document-title', 'Laporan Data Analytics');

                const accent = append(page, 'div', 'accent-bar');
                append(accent, 'div', 'accent-red');
                append(accent, 'div', 'accent-orange');

                const main = append(page, 'main', 'export-body');
                const metaGrid = append(main, 'section', 'meta-grid');
                addMeta(metaGrid, 'Dataset', pageTitle);
                addMeta(metaGrid, 'Dihasilkan Pada', generatedAt);
                addMeta(metaGrid, 'Sumber', 'SWL API - ReportAPI Platform');

                addSectionTitle(main, 'Ringkasan Data');
                const summaryGrid = append(main, 'section', 'summary-grid');
                const summaryCards = statCards.length > 0 ?
                    statCards :
                    [{
                        label: 'Total Tampil',
                        value: formatNumber(reports.length)
                    }];
                summaryCards.forEach((stat, index) => {
                    const card = append(summaryGrid, 'article', 'summary-card');
                    card.style.setProperty('--summary-color', statColors[index % statColors.length]);
                    append(card, 'div', 'summary-label', stat.label);
                    append(card, 'div', 'summary-value', stat.value);
                });

                addSectionTitle(main, 'Detail Struktur Unit');
                if (reports.length > 0) {
                    const table = append(main, 'table', 'report-table');
                    const thead = append(table, 'thead');
                    const headerRow = append(thead, 'tr');
                    append(headerRow, 'th', 'unit-name', 'Nama Unit');
                    append(headerRow, 'th', 'unit-level', 'Level');
                    append(headerRow, 'th', 'unit-name', 'Nama Unit');
                    append(headerRow, 'th', 'unit-level', 'Level');
                    const tbody = append(table, 'tbody');
                    pairedRows.forEach((pair, index) => {
                        const row = append(tbody, 'tr', index % 2 === 0 ? 'row-even' : 'row-odd');
                        addReportCell(row, pair.left);
                        if (pair.right) {
                            addReportCell(row, pair.right);
                        } else {
                            append(row, 'td', 'unit-name');
                            append(row, 'td', 'unit-level');
                        }
                    });
                } else {
                    append(main, 'div', 'empty-message',
                    'Tidak ada laporan yang cocok dengan filter saat ini.');
                }

                const footer = append(page, 'footer', 'print-footer');
                append(footer, 'div', '',
                    'Catatan: Laporan ini dihasilkan otomatis dari sistem. Untuk data real-time, lakukan refresh melalui dashboard.'
                    );
                append(footer, 'div', '', `ReportAPI Platform · ${generatedAt}`);

                printWindow.focus();
                setTimeout(() => printWindow.print(), 250);
            };

            const renderReports = (menu) => {
                const reports = visibleReports(menu);
                const tabs = [{
                        name: 'all',
                        label: 'All',
                        count: menu.total
                    },
                    ...menu.categories.map((category) => ({
                        name: category.name,
                        label: category.name,
                        count: category.reports.length,
                    })),
                ];

                reportsMount.innerHTML = `
                    <div class="reports-toolbar">
                        <div class="reports-heading">
                            <h2>List Laporan ${menu.label}</h2>
                            <p><span data-visible-count>${formatNumber(reports.length)}</span> laporan tampil dari ${formatNumber(menu.total)} total.</p>
                        </div>
                        <div class="toolbar-controls">
                            <div class="search-wrap">
                                <span class="search-symbol">⌕</span>
                                <input type="search" class="dashboard-input" value="${escapeHtml(searchValue)}" placeholder="Cari nama laporan atau sub menu..." data-report-search>
                            </div>
                            <button type="button" class="dashboard-button secondary" data-export-pdf>Export to PDF</button>
                        </div>
                    </div>
                    <div class="tabs-scroll">
                        <div class="report-tabs" role="tablist">
                            ${tabs.map((tab) => `
                                    <button type="button" class="tab-button ${tab.name === activeTab ? 'is-active' : ''}" data-tab-target="${tab.name}">
                                        ${tab.label} <span class="count-badge">${formatNumber(tab.count)}</span>
                                    </button>
                                `).join('')}
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="reports-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Laporan</th>
                                    <th>Sub Menu</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${reports.map((report, index) => `
                                        <tr>
                                            <td class="row-number">${index + 1}</td>
                                            <td><div class="report-title">${report.label}</div></td>
                                            <td><span class="category-chip">${report.category}</span></td>
                                            <td class="table-action">
                                                <a href="${report.href}" class="dashboard-button secondary">Buka Form</a>
                                            </td>
                                        </tr>
                                    `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="empty-state ${reports.length === 0 ? 'is-visible' : ''}">Tidak ada laporan yang cocok dengan filter saat ini.</div>
                `;

                reportsMount.querySelector('[data-report-search]')?.addEventListener('input', (event) => {
                    searchValue = event.target.value;
                    renderReports(menu);
                    const searchInput = reportsMount.querySelector('[data-report-search]');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.setSelectionRange(searchValue.length, searchValue.length);
                    }
                });

                reportsMount.querySelector('[data-export-pdf]')?.addEventListener('click', () => {
                    exportReportsPdf(menu, reports);
                });

                reportsMount.querySelectorAll('[data-tab-target]').forEach((tab) => {
                    tab.addEventListener('click', () => {
                        activeTab = tab.dataset.tabTarget;
                        renderReports(menu);
                    });
                });
            };

            const renderDashboard = () => {
                const menu = menuByKey.get(activeMenuKey);
                renderSidebar();
                renderStats(menu);
                renderReports(menu);
            };

            document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => body.classList.add(
                'sidebar-open'));
            document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => body.classList.remove(
                'sidebar-open'));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    body.classList.remove('sidebar-open');
                }
            });

            renderDashboard();
        });
    </script>
</body>

</html>
