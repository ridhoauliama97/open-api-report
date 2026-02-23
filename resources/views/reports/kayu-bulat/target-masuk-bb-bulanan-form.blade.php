<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Laporan Target Masuk Bahan Baku Bulanan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Noto Serif', serif;
        }

        .report-table,
        .summary-table {
            font-size: 0.75rem;
        }

        .report-table th,
        .report-table td,
        .summary-table th,
        .summary-table td {
            white-space: nowrap;
            padding: 0.2rem 0.3rem;
            border: 1px solid #333;
            text-align: center;
        }

        .report-table thead th {
            background-color: #f8f9fa;
        }

        .report-table tbody tr:nth-child(odd) td,
        .summary-table tbody tr:nth-child(odd) td {
            background-color: #c9d1df;
        }

        .report-table tbody tr:nth-child(even) td,
        .summary-table tbody tr:nth-child(even) td {
            background-color: #eef2f8;
        }

        .report-table .sticky-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 2;
            text-align: left;
            font-weight: 600;
        }
    </style>
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
                <h1 class="h3 mb-2">Laporan Target Masuk Bahan Baku Bulanan</h1>
                <p class="text-secondary mb-4">
                    Data diambil dari stored procedure <code>SP_LapTargetMasukBBBulanan</code>.
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

                <form method="GET" action="{{ route('reports.kayu-bulat.target-masuk-bb-bulanan.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Tanggal Awal</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">Refresh Data</button>
                        <a href="{{ route('reports.kayu-bulat.target-masuk-bb-bulanan.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-success">Generate & Download PDF</a>
                        <a href="{{ route('reports.kayu-bulat.target-masuk-bb-bulanan.download', ['start_date' => $startDate, 'end_date' => $endDate, 'preview_pdf' => 1]) }}"
                            target="_blank" class="btn btn-outline-primary">Preview PDF</a>
                        <a href="{{ route('reports.kayu-bulat.target-masuk-bb-bulanan.preview', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-outline-secondary">Preview JSON</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 text-center fw-bold mb-0">Laporan Target Masuk Bahan Baku Bulanan</h2>
                <p class="text-center mb-3">{{ $reportData['period_text'] }}</p>

                <div class="table-responsive mb-3">
                    <table class="table report-table mb-0">
                        <thead>
                            <tr>
                                <th>Nama Group</th>
                                <th>Tgt Bulan</th>
                                @foreach ($reportData['month_columns'] as $month)
                                    <th>{{ $month['label'] }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportData['table_rows'] as $row)
                                <tr>
                                    <td class="sticky-col">{{ $row['jenis'] }}</td>
                                    <td>{{ number_format((float) $row['target_bulanan'], 0, ',', '.') }}</td>
                                    @foreach ($row['monthly_values'] as $value)
                                        <td>{{ number_format((float) $value, 0, ',', '.') }}</td>
                                    @endforeach
                                    <td>{{ number_format((float) $row['total'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="99" class="text-center">Data tidak tersedia untuk periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mb-3" style="max-width: 420px;">
                    <table class="table summary-table mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Avg</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Bulan Capai</th>
                                <th>% Capai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportData['summary_rows'] as $summary)
                                <tr>
                                    <td class="text-start">{{ $summary['jenis'] }}</td>
                                    <td>{{ number_format((float) $summary['avg'], 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) $summary['min'], 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) $summary['max'], 0, ',', '.') }}</td>
                                    <td>{{ $summary['bulan_capai'] }}/{{ $summary['total_bulan_target'] }}</td>
                                    <td>{{ number_format((float) $summary['persen_capai_group'], 2, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">-</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="height: 420px;">
                    <canvas id="targetMasukBbBulananChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const payload = @json($reportData);
            const chartCanvas = document.getElementById('targetMasukBbBulananChart');

            if (!payload || !chartCanvas) {
                return;
            }

            const labels = Array.isArray(payload.chart_labels) ? payload.chart_labels : [];
            const series = payload.chart_series || {};
            const jenisList = Object.keys(series);

            if (labels.length === 0 || jenisList.length === 0) {
                const parent = chartCanvas.parentElement;
                if (parent) {
                    parent.innerHTML =
                        '<div class="alert alert-info mb-0">Data chart tidak tersedia untuk periode ini.</div>';
                }
                return;
            }

            const colorByJenis = (jenis) => {
                const key = String(jenis || '').toUpperCase();
                if (key.includes('JABON')) return 'rgba(13, 110, 253, 0.9)';
                if (key.includes('PULAI')) return 'rgba(25, 135, 84, 0.9)';
                if (key.includes('RAMBUNG')) return 'rgba(220, 53, 69, 0.9)';
                return 'rgba(75, 85, 99, 0.9)';
            };

            const datasets = jenisList.map((jenis) => ({
                label: jenis,
                data: (series[jenis] || []).map((value) => {
                    const num = Number(value || 0);
                    return num <= 0 ? null : num;
                }),
                borderColor: colorByJenis(jenis),
                backgroundColor: colorByJenis(jenis),
                borderWidth: 2,
                fill: false,
                tension: 0.2,
                pointRadius: 3,
            }));

            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets,
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
                            type: 'linear',
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => Number(value).toLocaleString('id-ID')
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) =>
                                    `${context.dataset.label}: ${Number(context.raw || 0).toLocaleString('id-ID')}`
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
