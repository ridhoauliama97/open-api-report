<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Generate Laporan Perbandingan KB Masuk Periode 1 dan 2</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold"
                href="{{ url('/') }}">{{ config('app.name', 'PDF Generator (Open API)') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Laporan Perbandingan KB Masuk Periode 1 dan 2 (PDF)</h1>
                <p class="text-secondary mb-4">
                    Sistem akan mengambil data dari SP_LapPerbandinganKbMasukPeriode1dan2.
                </p>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                    action="{{ route('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2.download') }}"
                    class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label for="period_1_start_date" class="form-label">Periode 1 - Tgl Awal</label>
                        <input type="date" id="period_1_start_date" name="period_1_start_date" class="form-control"
                            value="{{ old('period_1_start_date', old('TglAwalPeriode1', now()->subMonth()->startOfMonth()->toDateString())) }}"
                            required>
                    </div>
                    <div class="col-md-3">
                        <label for="period_1_end_date" class="form-label">Periode 1 - Tgl Akhir</label>
                        <input type="date" id="period_1_end_date" name="period_1_end_date" class="form-control"
                            value="{{ old('period_1_end_date', old('TglAkhirPeriode1', now()->subMonth()->endOfMonth()->toDateString())) }}"
                            required>
                    </div>
                    <div class="col-md-3">
                        <label for="period_2_start_date" class="form-label">Periode 2 - Tgl Awal</label>
                        <input type="date" id="period_2_start_date" name="period_2_start_date" class="form-control"
                            value="{{ old('period_2_start_date', old('TglAwalPeriode2', now()->startOfMonth()->toDateString())) }}"
                            required>
                    </div>
                    <div class="col-md-3">
                        <label for="period_2_end_date" class="form-label">Periode 2 - Tgl Akhir</label>
                        <input type="date" id="period_2_end_date" name="period_2_end_date" class="form-control"
                            value="{{ old('period_2_end_date', old('TglAkhirPeriode2', now()->toDateString())) }}"
                            required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary"
                                formaction="{{ route('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2.preview-pdf') }}"
                                formtarget="_blank">
                                Preview PDF
                            </button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Data
                                (JSON)</button>
                        </div>
                    </div>
                </form>

                <div id="previewJsonWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Preview Data (JSON)</h2>
                    <pre id="previewJsonOutput" class="bg-white border rounded p-3 mb-0" style="max-height: 360px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const previewButton = document.getElementById('previewJsonBtn');
            const previewWrapper = document.getElementById('previewJsonWrapper');
            const previewOutput = document.getElementById('previewJsonOutput');

            if (!previewButton || !previewWrapper || !previewOutput) {
                return;
            }

            previewButton.addEventListener('click', async function() {
                const payload = {
                    period_1_start_date: document.getElementById('period_1_start_date')?.value ??
                        '',
                    period_1_end_date: document.getElementById('period_1_end_date')?.value ?? '',
                    period_2_start_date: document.getElementById('period_2_start_date')?.value ??
                        '',
                    period_2_end_date: document.getElementById('period_2_end_date')?.value ?? '',
                };

                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch(
                        '{{ route('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify(payload),
                        });

                    const body = await response.json();
                    previewOutput.textContent = JSON.stringify(body, null, 2);
                } catch (error) {
                    previewOutput.textContent = JSON.stringify({
                        message: 'Gagal mengambil preview.',
                        error: String(error),
                    }, null, 2);
                }
            });
        });
    </script>
</body>

</html>
