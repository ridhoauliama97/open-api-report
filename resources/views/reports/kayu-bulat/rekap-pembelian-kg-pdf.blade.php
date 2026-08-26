<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $yearRows = is_array($data['year_rows'] ?? null) ? $data['year_rows'] : [];
        $monthLabels = is_array($data['month_labels'] ?? null) ? $data['month_labels'] : [];
        $monthTotals = is_array($data['month_totals'] ?? null) ? $data['month_totals'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmt = static fn(float $value): string => abs($value) < 0.0000001 ? '' : number_format($value, 4, '.', ',');

        $startYear = (int) ($summary['start_year'] ?? 0);
        $endYear = (int) ($summary['end_year'] ?? 0);
        $dataCellStyle =
            'border-top:none;border-bottom:none;border-left:1px solid #000;border-right:1px solid #000;font-weight:bold;font-size:11px;';
        $dataCellMonthStyle =
            'border-top:none;border-bottom:none;border-left:1px solid #000;border-right:1px solid #000;';

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
    @endphp

    <h1 class="report-title">Laporan Rekap Pembelian Kayu Bulat (Ton) - Timbang KG</h1>
    <p class="report-subtitle">
        @if ($startYear > 0 && $endYear > 0)
            Periode {{ $startYear }} s/d {{ $endYear }}
        @endif
    </p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 48px;">Tahun</th>
                        @foreach ($monthLabels as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th style="width: 74px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($yearRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell" style="{{ $dataCellStyle }}">{{ $row['tahun'] ?? '' }}</td>
                            @foreach ($monthLabels as $month => $label)
                                <td class="number data-cell" style="{{ $dataCellMonthStyle }}">
                                    {{ $fmt((float) ($row['months'][$month] ?? 0.0)) }}
                                </td>
                            @endforeach
                            <td class="number data-cell" style="{{ $dataCellStyle }}">
                                {{ $fmt((float) ($row['total'] ?? 0.0)) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count($monthLabels) }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
