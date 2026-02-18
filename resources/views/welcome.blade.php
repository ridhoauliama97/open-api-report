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
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="#">{{ config('app.name', 'Laravel') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm border">
            <h1 class="display-5 fw-bold">Welcome!</h1>
            <p class="fs-5 text-secondary mb-4">
                Aplikasi ini dibuat untuk memudahkan dalam menghasilkan laporan PDF dengan menggunakan OpenAPI
                Specification (OAS), library mpdf dan Laravel sebagai framework backend. Dengan aplikasi ini, Anda dapat
                dengan mudah menghasilkan laporan PDF yang sesuai dengan kebutuhan Anda, tanpa perlu khawatir tentang
                kompleksitas teknis yang terkait dengan pembuatan laporan PDF secara manual.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('reports.mutasi.barang-jadi.index') }}" class="btn btn-primary btn-lg">
                    Generate Laporan Mutasi Barang Jadi (PDF)</a>
                <a href="{{ route('reports.mutasi.finger-joint.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Mutasi Finger Joint (PDF)</a>
                <a href="{{ route('reports.mutasi.moulding.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Mutasi Moulding (PDF)</a>
                <a href="{{ route('reports.mutasi.s4s.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Mutasi S4S (PDF)</a>
                <a href="{{ route('reports.mutasi.kayu-bulat.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Mutasi Kayu Bulat (PDF)</a>
                <a href="{{ route('reports.mutasi.kayu-bulat-kg.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Mutasi Kayu Bulat - Timbang KG (PDF)</a>
                <a href="{{ route('reports.mutasi.kayu-bulat-kgv2.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Mutasi Kayu Bulat (Gantung) - Timbang KG (PDF)</a>
                <a href="{{ route('reports.rangkuman-label-input.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Rangkuman Jumlah Label Input (PDF)</a>
                <a href="{{ route('reports.label-nyangkut.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Label Nyangkut (PDF)</a>
                <a href="{{ route('reports.bahan-terpakai.index') }}" class="btn btn-outline-primary btn-lg">
                    Generate Laporan Bahan Terpakai (PDF)</a>
            </div>
        </div>
    </main>
</body>

</html>
