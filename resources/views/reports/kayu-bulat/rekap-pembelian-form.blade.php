<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Rekap Pembelian Kayu Bulat</title>
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
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-2">Rekap Pembelian Kayu Bulat</h1>
                <p class="text-secondary mb-4">
                    Laporan dari <code>SPWps_LapRekapPembelianKayuBulat</code> dengan tampilan chart dan tabel total.
                    Chart dibentuk per bulan untuk masing-masing tahun.
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

                @if ($errorMessage)
                    <div class="alert alert-danger">{{ $errorMessage }}</div>
                @endif

                <form method="GET" action="{{ route('reports.kayu-bulat.rekap-pembelian.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_year" class="form-label">Tahun Awal</label>
                        <input type="number" id="start_year" name="start_year" class="form-control" min="1900"
                            max="2999" value="{{ $startYear }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_year" class="form-label">Tahun Akhir</label>
                        <input type="number" id="end_year" name="end_year" class="form-control" min="1900"
                            max="2999" value="{{ $endYear }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Refresh Data</button>
                        <a href="{{ route('reports.kayu-bulat.rekap-pembelian.download', ['start_year' => $startYear, 'end_year' => $endYear]) }}"
                            class="btn btn-success">Generate PDF</a>
                        <a href="{{ route('reports.kayu-bulat.rekap-pembelian.download', ['start_year' => $startYear, 'end_year' => $endYear, 'preview_pdf' => 1]) }}"
                            target="_blank" class="btn btn-outline-primary">Preview PDF</a>
                        <a href="{{ route('reports.kayu-bulat.rekap-pembelian.preview', ['start_year' => $startYear, 'end_year' => $endYear]) }}"
                            class="btn btn-outline-secondary">Preview JSON</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Ringkasan</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Rentang Tahun</div>
                            <div class="h4 mb-0">{{ $startYear }} - {{ $endYear }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Jumlah Baris Raw</div>
                            <div class="h4 mb-0">{{ count($reportData['rows'] ?? []) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Total Pembelian</div>
                            <div class="h4 mb-0">
                                {{ number_format((float) ($reportData['grand_total'] ?? 0), 4, ',', '.') }} Ton
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Chart Pembelian Bulanan per Tahun</h2>
                <div style="height: 420px;">
                    <canvas id="monthlyByYearChart"></canvas>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const payload = @json($reportData);
            const dailyCanvas = document.getElementById('monthlyByYearChart');

            if (!payload || !dailyCanvas) {
                return;
            }

            const formatNumber = (value) => Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 4,
                maximumFractionDigits: 4
            });

            const palette = [
                'rgba(13, 110, 253, 0.8)',
                'rgba(25, 135, 84, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(253, 126, 20, 0.8)',
                'rgba(111, 66, 193, 0.8)',
                'rgba(32, 201, 151, 0.8)',
                'rgba(214, 51, 132, 0.8)',
                'rgba(108, 117, 125, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(0, 123, 255, 0.8)'
            ];

            const months = Array.isArray(payload.chart_month_labels) ? payload.chart_month_labels : [];
            const years = Array.isArray(payload.chart_years) ? payload.chart_years : [];

            if (months.length === 0 || years.length === 0) {
                const parentA = dailyCanvas.parentElement;
                if (parentA) {
                    parentA.innerHTML =
                        '<div class="alert alert-info mb-0">Data bulanan tidak tersedia untuk periode ini.</div>';
                }
                return;
            }

            const datasets = years.map((year, index) => ({
                label: String(year),
                data: (payload.chart_series_by_year && payload.chart_series_by_year[year]) ? payload
                    .chart_series_by_year[year] : [],
                backgroundColor: palette[index % palette.length],
                borderColor: palette[index % palette.length],
                borderWidth: 1,
            }));

            new Chart(dailyCanvas, {
                type: 'line',
                data: {
                    labels: months,
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {},
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: formatNumber
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${formatNumber(context.raw)}`
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
