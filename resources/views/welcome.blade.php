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
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Laporan Mutasi</h2>
                        <div class="row g-2">
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.barang-jadi.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Barang Jadi</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.finger-joint.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Finger Joint</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.moulding.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Moulding</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.laminating.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Laminating</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.sanding.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Sanding</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.s4s.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi S4S</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.st.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Sawn Timber (Ton)</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.kayu-bulat.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.kayu-bulat-v2.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat Gantung</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.kayu-bulat-kg.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat - Timbang KG</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi.kayu-bulat-kgv2.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Kayu Bulat (Gantung) - Timbang KG</a></div>
                            <div class="col-12 col-md-6"><a href="{{ route('reports.mutasi-hasil-racip.index') }}" class="btn btn-outline-primary w-100 text-start">Mutasi Hasil Racip</a></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Laporan Verifikasi</h2>
                        <div class="d-grid gap-2">
                            <a href="{{ route('reports.rangkuman-label-input.index') }}" class="btn btn-outline-primary text-start">
                                Rangkuman Jumlah Label Input
                            </a>
                            <a href="{{ route('reports.label-nyangkut.index') }}" class="btn btn-outline-primary text-start">
                                Label Nyangkut
                            </a>
                            <a href="{{ route('reports.bahan-terpakai.index') }}" class="btn btn-outline-primary text-start">
                                Bahan Terpakai
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
