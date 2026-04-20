<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="#">{{ config('app.name', 'Laravel') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
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
                @if ($errors->has('login'))
                    <div class="alert alert-danger mt-3 mb-0">{{ $errors->first('login') }}</div>
                @endif
                @if ($errors->any() && !$errors->has('login'))
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>


        <div id="pps-reports" class="mb-3">
            <h2 class="h4 fw-bold mb-0">PPS</h2>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Bahan Baku</h2>
                    <div class="row g-2">
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
                            <a href="{{ route('reports.pps.bonggolan.stock-bonggolan-v2.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Stock Bonggolan
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
                    <h2 class="h5 mb-3">QC</h2>
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
            <h2 class="h4 fw-bold mb-0">WPS</h2>
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
                    <h2 class="h5 mb-3">Mutasi</h2>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.barang-jadi.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Barang Jadi</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.finger-joint.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Finger Joint</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.moulding.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Moulding</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.laminating.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Laminating</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.sanding.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Sanding</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.s4s.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi S4S</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.st.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Sawn Timber (Ton)</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.cca-akhir.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi CC Akhir</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.reproses.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Reproses</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.kayu-bulat.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.kayu-bulat-v2.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat Gantung</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.kayu-bulat-kg.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat - Timbang KG</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi.kayu-bulat-kgv2.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat (Gantung) -
                                Timbang KG</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi-hasil-racip.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Hasil Racip</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.mutasi-racip-detail.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Mutasi Racip Detail</a>
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
                                Rangkuman Jumlah Label Input
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.label-nyangkut.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Label Nyangkut
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.bahan-terpakai.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Bahan Terpakai
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
                                class="btn btn-outline-primary w-100 text-start">Umur S4S Detail</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.rekap-produksi-s4s-consolidated.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Rekap Produksi S4S Consolidated</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.s4s-hidup-detail.index') }}"
                                class="btn btn-outline-primary w-100 text-start">S4S (Hidup) Detail</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.label-s4s-hidup-per-jenis-kayu.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Label S4S (Hidup) Per-Jenis Kayu</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.label-s4s-hidup-per-produk-per-jenis-kayu.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Label S4S (Hidup) Per-Produk &
                                Per-Jenis Kayu</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.rekap-produksi-s4s-per-jenis-per-grade.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Rekap Produksi S4S Per-Jenis &
                                Per-Grade (m3)</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.ketahanan-barang-s4s.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Ketahanan Barang Dagang S4S</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.output-produksi-s4s-per-grade.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Output Produksi S4S Per Grade</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.grade-abc-harian.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Grade ABC Harian</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.s4s.rekap-produksi-rambung-per-grade.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Rekap Produksi Rambung Per Grade</a>
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
                                class="btn btn-outline-primary w-100 text-start">Umur Finger Joint Detail</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.finger-joint.rekap-produksi-finger-joint-consolidated.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Rekap Produksi Finger Joint Consolidated</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Rekap Produksi Finger Joint Per-Jenis & Per-Grade (m3)</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.finger-joint.finger-joint-hidup-detail.index') }}"
                                class="btn btn-outline-primary w-100 text-start">Finger Joint (Hidup) Detail</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('reports.finger-joint.ketahanan-barang-finger-joint.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Ketahanan Barang Dagang Finger Joint</a>
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
                        {{-- <div class="col-12 col-md-6">

                        </div> --}}
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
                    <h2 class="h5 mb-3">Dashboard</h2>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.sawn-timber.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Dasboard Sawn Timber (Chart)
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.barang-jadi.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Barang Jadi
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.cross-cut-akhir.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Cross Cut Akhir
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.finger-joint.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Finger Joint
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.laminating.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Laminating
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.moulding.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Moulding
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.reproses.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Reproses
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="{{ route('dashboard.sanding.index') }}"
                                class="btn btn-outline-primary w-100 text-start">
                                Laporan Dashboard Sanding
                            </a>
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
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const reportCards = Array.from(document.querySelectorAll('main > .col-12 > .card'));

            reportCards.forEach((card, index) => {
                const body = card.querySelector(':scope > .card-body');
                const title = body?.querySelector(':scope > h2');

                if (!body || !title) {
                    return;
                }

                const collapseId = `reportCardCollapse${index + 1}`;
                const sectionTitle = title.textContent.trim();
                const collapse = document.createElement('div');
                collapse.className = 'collapse show';
                collapse.id = collapseId;

                while (body.firstChild) {
                    if (body.firstChild === title) {
                        body.removeChild(title);
                        continue;
                    }

                    collapse.appendChild(body.firstChild);
                }

                body.className = 'card-header bg-white border-0 p-0';
                body.innerHTML = `
                    <button class="btn btn-light w-100 text-start d-flex justify-content-between align-items-center p-4"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#${collapseId}"
                        aria-expanded="true"
                        aria-controls="${collapseId}">
                        <span class="h5 mb-0">${sectionTitle}</span>
                        <span class="small text-secondary">Buka / Tutup</span>
                    </button>
                `;

                const collapseBody = document.createElement('div');
                collapseBody.className = 'card-body p-4';
                while (collapse.firstChild) {
                    collapseBody.appendChild(collapse.firstChild);
                }

                collapse.appendChild(collapseBody);
                card.appendChild(collapse);
            });
        });
    </script>
</body>

</html>
