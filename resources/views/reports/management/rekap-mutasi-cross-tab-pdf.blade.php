<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $statsRows = is_array($data['stats_rows'] ?? null) ? $data['stats_rows'] : [];
        $displayColumns = is_array($data['summary']['display_columns'] ?? null)
            ? $data['summary']['display_columns']
            : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmt = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtKg = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $isKgColumn = static fn(string $key): bool => $key === 'KBKG';
    @endphp

    <h1 class="report-title">Laporan Rekap Mutasi (Cross Tab)</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 42px;">Tanggal</th>
                @foreach ($displayColumns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $row['day'] ?? '' }}</td>
                    @foreach ($displayColumns as $key => $label)
                        <td class="number">
                            {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($displayColumns) + 1 }}" class="empty-state">Tidak ada data untuk periode
                        ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($statsRows !== [])
        <table class="report-table stats-table">
            <thead>
                <tr>
                    <th style="width: 42px;"></th>
                    @foreach ($displayColumns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($statsRows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center"><strong>{{ $row['label'] ?? '' }}</strong></td>
                        @foreach ($displayColumns as $key => $label)
                            <td class="number">
                                {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    </body>

</html>
