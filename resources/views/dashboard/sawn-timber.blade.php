<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Dashboard Sawn Timber</title>
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
                <h1 class="h3 mb-2">Dashboard Sawn Timber</h1>
                <p class="text-secondary mb-4">
                    Visualisasi arus masuk dan keluar harian untuk tiap jenis berdasarkan
                    <code>SPWps_LapDashboardSawnTimber</code>.
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

                <form method="GET" action="{{ route('dashboard.sawn-timber.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Tanggal Awal</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Tampilkan Chart</button>
                        <a href="{{ route('dashboard.sawn-timber.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-success">Generate PDF</a>
                        <a href="{{ route('dashboard.sawn-timber.download', ['start_date' => $startDate, 'end_date' => $endDate, 'preview_pdf' => 1]) }}"
                            class="btn btn-outline-primary" target="_blank">Preview PDF</a>
                        <a href="{{ route('dashboard.sawn-timber.preview', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
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
                            <div class="text-secondary small">Jumlah Hari</div>
                            <div class="h4 mb-0">{{ count($chartData['dates']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Jumlah Jenis</div>
                            <div class="h4 mb-0">{{ count($chartData['types']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Jumlah Baris Raw</div>
                            <div class="h4 mb-0">{{ count($chartData['raw_rows']) }}</div>
                        </div>
                    </div>
                </div>

                @if (!empty($chartData['totals_by_type']))
                    <div class="table-responsive mt-4">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th class="text-end">Total Masuk</th>
                                    <th class="text-end">Total Keluar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($chartData['totals_by_type'] as $type => $totals)
                                    <tr>
                                        <td>{{ $type }}</td>
                                        <td class="text-end">
                                            {{ number_format((float) ($totals['in'] ?? 0), 1, ',', '.') }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format((float) ($totals['out'] ?? 0), 1, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Laporan S Akhir dan #Ctr per Jenis</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th class="text-end">S Akhir</th>
                                <th class="text-end">#Ctr</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chartData['stock_by_type'] ?? [] as $type => $row)
                                <tr>
                                    <td>{{ $type }}</td>
                                    <td class="text-end">
                                        {{ number_format((float) ($row['s_akhir'] ?? 0), 1, ',', '.') }}
                                    </td>
                                    <td class="text-end">{{ number_format((float) ($row['ctr'] ?? 0), 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td>Total</td>
                                <td class="text-end">
                                    {{ number_format((float) ($chartData['stock_totals']['s_akhir'] ?? 0), 1, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format((float) ($chartData['stock_totals']['ctr'] ?? 0), 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Chart Arus Masuk per Jenis</h2>
                <div style="height: 420px;">
                    <canvas id="sawnTimberChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const payload = @json($chartData);
            const chartCanvas = document.getElementById('sawnTimberChart');

            if (!payload || !chartCanvas) {
                return;
            }

            const formatNumber = (value) => Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1
            });

            const labels = Array.isArray(payload.dates) ? payload.dates : [];
            const types = Array.isArray(payload.types) ? payload.types : [];

            if (labels.length === 0 || types.length === 0) {
                const parent = chartCanvas.parentElement;
                if (parent) {
                    parent.innerHTML =
                        '<div class="alert alert-info mb-0">Data tidak tersedia untuk periode ini.</div>';
                }
                return;
            }

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

            const datasets = types.map((type, index) => ({
                label: String(type),
                data: (payload.series_by_type && payload.series_by_type[type] && payload.series_by_type[
                        type].in) ?
                    payload.series_by_type[type].in : [],
                backgroundColor: palette[index % palette.length],
                borderColor: palette[index % palette.length],
                borderWidth: 1,
            }));

            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels,
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
