<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Laporan Stok Racip Kayu Lat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Noto Serif', serif;
        }

        .report-table {
            font-size: 0.75rem;
        }

        .report-table th,
        .report-table td {
            white-space: nowrap;
            padding: 0.2rem 0.3rem;
            border: 1px solid #333;
            text-align: center;
        }

        .report-table thead th {
            background-color: #fff;
        }

        .zebra-table tbody tr:nth-child(odd) td {
            background-color: #c9d1df;
        }

        .zebra-table tbody tr:nth-child(even) td {
            background-color: #eef2f8;
        }

        .zebra-table tbody tr:last-child td {
            background-color: #fff;
        }

        .group-title {
            margin: 0 0 4px 0;
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .section {
            margin-bottom: 5px;
        }

        .cell-right {
            text-align: right;
            padding-right: 0.5rem;
        }

        .subtotal-label {
            text-align: right;
            font-weight: 700;
            padding-right: 0.75rem;
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
                <h1 class="h3 mb-2">Laporan Stok Racip Kayu Lat</h1>
                <p class="text-secondary mb-4">
                    Data diambil dari stored procedure <code>sp_LapStockRacipKayuLat</code> dengan parameter
                    <code>TglAkhir</code>.
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

                <form method="GET" action="{{ route('reports.stock-racip-kayu-lat.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-8 d-flex align-items-end gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">Refresh Data</button>
                        <a href="{{ route('reports.stock-racip-kayu-lat.download', ['end_date' => $endDate]) }}"
                            class="btn btn-success">Generate & Download PDF</a>
                        <a href="{{ route('reports.stock-racip-kayu-lat.download', ['end_date' => $endDate, 'preview_pdf' => 1]) }}"
                            target="_blank" class="btn btn-outline-primary">Preview PDF</a>
                        <a href="{{ route('reports.stock-racip-kayu-lat.preview', ['end_date' => $endDate]) }}"
                            class="btn btn-outline-secondary">Preview JSON</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 text-center fw-bold mb-0">Laporan Stok Racip Kayu Lat</h2>
                <p class="text-center mb-3">Per Tanggal {{ $reportData['end_date_text'] ?? $endDate }}</p>

                @php
                    $fmt4 = static function ($value): string {
                        $num = (float) ($value ?? 0);
                        return abs($num) < 0.0000001 ? '' : number_format($num, 4, ',', '.');
                    };
                    $fmtInt = static function ($value): string {
                        return number_format((float) ($value ?? 0), 0, ',', '.');
                    };
                @endphp

                @php $hasData = !empty($reportData['grouped_rows'] ?? []); @endphp
                @if ($hasData)
                    @foreach ($reportData['grouped_rows'] as $group)
                        @php
                            $groupRows = $group['rows'] ?? [];
                            $sumBatang = 0.0;
                            $sumHasil = 0.0;
                            foreach ($groupRows as $r) {
                                $sumBatang += (float) ($r['JmlhBatang'] ?? 0);
                                $sumHasil += (float) ($r['Hasil'] ?? 0);
                            }
                        @endphp
                        <div class="section">
                            <p class="group-title">{{ $group['jenis'] }}</p>
                            <div class="table-responsive mb-2">
                                <table class="table report-table zebra-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tebal</th>
                                            <th>Lebar</th>
                                            <th>Panjang</th>
                                            <th>Jmlh Batang</th>
                                            <th>Hasil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupRows as $row)
                                            <tr>
                                                <td class="cell-right">{{ $fmt4($row['Tebal'] ?? 0) }}</td>
                                                <td class="cell-right">{{ $fmt4($row['Lebar'] ?? 0) }}</td>
                                                <td class="cell-right">{{ $fmt4($row['Panjang'] ?? 0) }}</td>
                                                <td class="cell-right">{{ $fmtInt($row['JmlhBatang'] ?? 0) }}</td>
                                                <td class="cell-right">{{ $fmt4($row['Hasil'] ?? 0) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" class="subtotal-label">Jumlah :</td>
                                            <td class="cell-right fw-bold">{{ $fmtInt($sumBatang) }}</td>
                                            <td class="cell-right fw-bold">{{ $fmt4($sumHasil) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="table-responsive mb-3">
                        <table class="table report-table mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-center">Data tidak tersedia untuk tanggal ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="table-responsive" style="max-width: 520px;">
                    <table class="table report-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-start fw-bold">Jumlah Baris Data Seluruhnya</td>
                                <td class="text-end">{{ number_format((int) ($reportData['summary']['total_rows'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">Jumlah Batang Seluruhnya</td>
                                <td class="text-end">{{ $fmt4($reportData['summary']['total_batang'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">Hasil Seluruhnya</td>
                                <td class="text-end">{{ $fmt4($reportData['summary']['total_hasil'] ?? 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
