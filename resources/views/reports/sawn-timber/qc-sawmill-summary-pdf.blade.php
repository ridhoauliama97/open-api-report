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
        }

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            margin: 0;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            margin: 2px 0 20px 0;
            text-align: center;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .headers-row th {
            border-top: 0;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: none !important;
            border-bottom: none !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .report-table tbody tr.row-last td.data-cell {
            border-bottom: 1px solid #000 !important;
        }

        td.meja-cell {
            text-align: left;
            white-space: nowrap;
            font-weight: normal;
        }

        td.percent-cell {
            text-align: center;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            font-size: 11px;
        }

        .date-column {
            width: 29px;
        }

        .meja-column {
            width: 64px;
        }

        .total-column {
            width: 32px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $dateKeys = is_array($data['date_keys'] ?? null) ? $data['date_keys'] : [];
        $mejaRows = is_array($data['meja_rows'] ?? null) ? $data['meja_rows'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $formatDate = static function ($value, string $format = 'd-M'): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat($format);
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatPeriodDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatPercent = static fn($value): string => number_format((float) $value, 1, '.', '') . '%';
    @endphp

    <h1 class="report-title">Laporan QC Sawmill - Summary</h1>
    <p class="report-subtitle">
        Periode {{ $formatPeriodDate($startDate ?? null) }} s/d {{ $formatPeriodDate($endDate ?? null) }}
    </p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th class="meja-column" rowspan="2"></th>
                @foreach ($dateKeys as $dateKey)
                    <th class="date-column">{{ $formatDate($dateKey, 'd-M') }}</th>
                @endforeach
                <th class="total-column">Total</th>
            </tr>
            <tr class="headers-row">
                @foreach ($dateKeys as $dateKey)
                    <th>Accrte</th>
                @endforeach
                <th>AVG</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mejaRows as $mejaRow)
                @php
                    $cells = is_array($mejaRow['cells'] ?? null) ? $mejaRow['cells'] : [];
                @endphp
                <tr
                    class="data-row {{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                    <td class="meja-cell data-cell">{{ $mejaRow['nama_meja'] ?? '-' }}</td>
                    @foreach ($dateKeys as $dateKey)
                        @php
                            $cell = is_array($cells[$dateKey] ?? null) ? $cells[$dateKey] : null;
                        @endphp
                        <td class="percent-cell data-cell">{{ $cell ? $formatPercent($cell['accurate'] ?? 0) : '' }}
                        </td>
                    @endforeach
                    <td class="percent-cell data-cell" style="font-weight: bold;">
                        {{ $formatPercent($mejaRow['avg_accurate'] ?? 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($dateKeys) + 2 }}" class="percent-cell">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
