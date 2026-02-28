<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Dashboard Barang Jadi</title>
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
                <h1 class="h3 mb-2">Laporan Dashboard Barang Jadi</h1>
                <p class="text-secondary mb-4">
                    Visualisasi arus masuk dan keluar harian per jenis barang jadi berdasarkan
                    <code>SPWps_LapDashboardBJ</code>.
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

                <form method="GET" action="{{ route('dashboard.barang-jadi.index') }}" class="row g-3">
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
                        <button type="submit" class="btn btn-primary">Tampilkan Data</button>
                        <a href="{{ route('dashboard.barang-jadi.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-success">Generate PDF</a>
                        <a href="{{ route('dashboard.barang-jadi.download', ['start_date' => $startDate, 'end_date' => $endDate, 'preview_pdf' => 1]) }}"
                            class="btn btn-outline-primary" target="_blank">Preview PDF</a>
                        <a href="{{ route('dashboard.barang-jadi.preview', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-outline-secondary">Preview JSON</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $columns = is_array($reportData['columns'] ?? null) ? $reportData['columns'] : [];
            $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
            $sAkhirByColumn = is_array($reportData['s_akhir_by_column'] ?? null) ? $reportData['s_akhir_by_column'] : [];
            $percentByColumn = is_array($reportData['percent_by_column'] ?? null) ? $reportData['percent_by_column'] : [];
            $ctrByColumn = is_array($reportData['ctr_by_column'] ?? null) ? $reportData['ctr_by_column'] : [];
            $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : ['s_akhir' => 0, 'ctr' => 0];

            $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
            $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');
            $fmtPct = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',') . '%';
        @endphp

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Ringkasan</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Jumlah Hari</div>
                            <div class="h4 mb-0">{{ count($rows) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Jumlah Kolom</div>
                            <div class="h4 mb-0">{{ count($columns) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="text-secondary small">Jumlah Baris Raw</div>
                            <div class="h4 mb-0">{{ count($reportData['raw_rows'] ?? []) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Preview Tabel</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2">Tanggal</th>
                                @foreach ($columns as $column)
                                    <th colspan="2" class="text-center">{{ $column }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($columns as $column)
                                    <th class="text-end">Masuk</th>
                                    <th class="text-end">Keluar</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse((string) ($row['date'] ?? now()))->format('d-M') }}</td>
                                    @foreach ($columns as $column)
                                        @php
                                            $inflow = (float) (($row['cells'][$column]['in'] ?? 0) ?: 0);
                                            $outflow = (float) (($row['cells'][$column]['out'] ?? 0) ?: 0);
                                        @endphp
                                        <td class="text-end">{{ abs($inflow) < 0.000001 ? '' : $fmt1($inflow) }}</td>
                                        <td class="text-end">{{ abs($outflow) < 0.000001 ? '' : $fmt1($outflow) }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 1 + count($columns) * 2 }}" class="text-center">Data tidak tersedia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td>S Akhir</td>
                                @foreach ($columns as $column)
                                    <td class="text-end">{{ $fmt2($sAkhirByColumn[$column] ?? 0) }}</td>
                                    <td class="text-end">{{ $fmtPct($percentByColumn[$column] ?? 0) }}</td>
                                @endforeach
                            </tr>
                            <tr class="fw-semibold">
                                <td>#Ctr</td>
                                @foreach ($columns as $column)
                                    <td class="text-end">{{ $fmt2($ctrByColumn[$column] ?? 0) }}</td>
                                    <td></td>
                                @endforeach
                            </tr>
                            <tr class="fw-semibold">
                                <td>Total</td>
                                <td class="text-end">{{ $fmt2($totals['s_akhir'] ?? 0) }}</td>
                                <td class="text-end">{{ $fmt2($totals['ctr'] ?? 0) }}</td>
                                <td colspan="{{ max(count($columns) * 2 - 2, 0) }}"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
