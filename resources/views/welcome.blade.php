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

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="#">{{ config('app.name', 'Laravel') }}</a>
        </div>
    </nav>

    <main class="container py-5">
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
                                <a href="{{ route('reports.kayu-bulat.penerimaan-per-supplier-group.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan Kayu Bulat Per-Supplier Berdasarkan Group Kayu
                                </a>
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
                        <h2 class="h5 mb-3">Dashboard</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <a href="{{ route('dashboard.sawn-timber.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Dasboard Sawn Timber (Chart)
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
                                <a href="{{ route('reports.sawn-timber.penerimaan-st-dari-sawmill-kg.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Penerimaan ST Dari Sawmill - Timbang KG
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{ route('reports.sawn-timber.lembar-tally-hasil-sawmill.index') }}"
                                    class="btn btn-outline-primary w-100 text-start">
                                    Laporan Lembar Tally Hasil Sawmill
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
