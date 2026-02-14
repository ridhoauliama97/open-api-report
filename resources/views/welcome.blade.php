<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="#">{{ config('app.name', 'Laravel') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm border">
            <h1 class="display-5 fw-bold">Laravel + Bootstrap</h1>
            <p class="fs-5 text-secondary mb-4">
                Aplikasi Laravel berhasil dibuat dan Bootstrap sudah terintegrasi.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('reports.sales.index') }}" class="btn btn-primary btn-lg">Generate Laporan Penjualan PDF</a>
                <a href="https://laravel.com/docs" target="_blank" class="btn btn-outline-primary btn-lg">Dokumentasi Laravel</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Frontend</h2>
                        <p class="text-secondary mb-0">Bootstrap 5 melalui Vite (`resources/css/app.css` dan `resources/js/app.js`).</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Backend</h2>
                        <p class="text-secondary mb-0">Laravel sudah siap untuk route, controller, model, API, dan generate laporan PDF.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
