<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Dashboard S4S</title>
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
                <h1 class="h3 mb-2">Laporan Dashboard S4S</h1>
                <p class="text-secondary mb-4">
                    Visualisasi dashboard stok S4S berdasarkan <code>SPWps_LapDashboardS4S</code>.
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

                <form method="GET" action="{{ route('dashboard.s4s.index') }}" class="row g-3">
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
                        <a href="{{ route('dashboard.s4s.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-success">Generate PDF</a>
                        <a href="{{ route('dashboard.s4s.download', ['start_date' => $startDate, 'end_date' => $endDate, 'preview_pdf' => 1]) }}"
                            class="btn btn-outline-primary" target="_blank">Preview PDF</a>
                        <a href="{{ route('dashboard.s4s.preview', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                            class="btn btn-outline-secondary">Preview JSON</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
            $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
            $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
            $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');
        @endphp

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Preview Tabel</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2">Tgl</th>
                                @foreach ($groups as $group)
                                    <th colspan="3" class="text-center">{{ $group['label'] ?? '-' }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($groups as $group)
                                    <th class="text-end">Masuk</th>
                                    <th class="text-end">Keluar</th>
                                    <th class="text-end">Akhir</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse((string) ($row['date'] ?? now()))->format('d') }}</td>
                                    @foreach ($groups as $groupKey => $group)
                                        @php
                                            $masuk = (float) ($row['cells'][$groupKey]['masuk'] ?? 0);
                                            $keluar = (float) ($row['cells'][$groupKey]['keluar'] ?? 0);
                                            $akhir = (float) ($row['cells'][$groupKey]['akhir'] ?? 0);
                                        @endphp
                                        <td class="text-end">{{ abs($masuk) < 0.000001 ? '' : $fmt1($masuk) }}</td>
                                        <td class="text-end">{{ abs($keluar) < 0.000001 ? '' : $fmt1($keluar) }}</td>
                                        <td class="text-end">{{ abs($akhir) < 0.000001 ? '' : $fmt1($akhir) }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 1 + count($groups) * 3 }}" class="text-center">Data tidak tersedia.</td>
                                </tr>
                            @endforelse
                            <tr class="fw-semibold">
                                <td>Jlh Container</td>
                                @foreach ($groups as $group)
                                    <td></td>
                                    <td></td>
                                    <td class="text-end">{{ $fmt2($group['container'] ?? 0) }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
