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
            margin: 20mm 12mm 20mm 12mm;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
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
            background: #fff;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 0;
            border-left: 0;
        }

        @include('reports.partials.pdf-footer-table-style') .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: 0;
        }

        .col-uniform {
            width: 5.25%;
        }

        .col-jenis {
            width: 16%;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border-top: 0;
            border-right: 0;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
        }

        .report-table thead tr.headers-row:first-child th {
            border-top: 1px solid #000;
        }

        .report-table thead tr.headers-row:first-child th[rowspan] {
            border-bottom: 1px solid #000;
        }

        .report-table thead tr.headers-row:first-child th[colspan] {
            border-bottom: 0;
        }

        .report-table thead tr.headers-row:last-child th {
            border-top: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            border-right: 1px solid #000 !important;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_iterable($reportData['rows'] ?? null)
            ? (is_array($reportData['rows'])
                ? $reportData['rows']
                : collect($reportData['rows'])->values()->all())
            : [];
        $detailColumns = is_array($reportData['detail_columns'] ?? null) ? $reportData['detail_columns'] : [];
        $numericColumns = is_array($reportData['numeric_columns'] ?? null) ? $reportData['numeric_columns'] : [];
        $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : [];

        $formatDateText = static function ($value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_array($value)) {
                return null;
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return null;
            }
        };
        $startText = $formatDateText($reportData['start_date_text'] ?? null) ?? ($formatDateText($startDate) ?? '');
        $endText = $formatDateText($reportData['end_date_text'] ?? null) ?? ($formatDateText($endDate) ?? '');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (is_string($value)) {
                $normalized = str_replace(',', '.', trim($value));
                if (is_numeric($normalized)) {
                    return (float) $normalized;
                }
            }

            return null;
        };

        $formatNumeric = static function (string $column, ?float $value): string {
            if ($value === null) {
                return '';
            }

            $normalized = strtolower(str_replace([' ', '_'], '', $column));
            $isBatangColumn = str_contains($normalized, 'jlhbtg') || str_contains($normalized, 'jmlhbatang');
            $isDimensionColumn = in_array($normalized, ['tebal', 'lebar', 'panjang'], true);
            if (abs($value) < 0.0000001) {
                return '';
            }

            if ($isBatangColumn) {
                return number_format($value, 0, '.', ',');
            }

            if ($isDimensionColumn) {
                $text = number_format($value, 4, '.', ',');
                return rtrim(rtrim($text, '0'), '.');
            }

            return number_format($value, 4, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Mutasi Racip Detail</h1>
    <p class="report-subtitle">Periode {{ $startText }} s/d {{ $endText }}</p>
    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="border-top: 1px solid #000;">No</th>
                <th rowspan="2" style="border-top: 1px solid #000;">Jenis</th>
                <th rowspan="2" style="border-top: 1px solid #000;">Tebal (mm)</th>
                <th rowspan="2" style="border-top: 1px solid #000;">Lebar (mm)</th>
                <th rowspan="2" style="border-top: 1px solid #000;">Panjang (ft)</th>
                <th colspan="2" style="border-top: 1px solid #000;">Saldo Awal</th>
                <th colspan="4" style="border-top: 1px solid #000;">Masuk</th>
                <th colspan="4" style="border-top: 1px solid #000;">Keluar</th>
                <th colspan="2" style="border-top: 1px solid #000;">Akhir</th>
            </tr>
            <tr class="headers-row">
                <th style="border-top: 1px solid #000;">Saldo Awal</th>
                <th style="border-top: 1px solid #000;">Jlh Batang</th>
                <th style="border-top: 1px solid #000;">Masuk</th>
                <th style="border-top: 1px solid #000;">Jlh Batang</th>
                <th style="border-top: 1px solid #000;">Adj Outp</th>
                <th style="border-top: 1px solid #000;">Jlh Batang</th>
                <th style="border-top: 1px solid #000;">Keluar</th>
                <th style="border-top: 1px solid #000;">Jlh Batang</th>
                <th style="border-top: 1px solid #000;">Adj Inp</th>
                <th style="border-top: 1px solid #000;">Jlh Batang</th>
                <th style="border-top: 1px solid #000;">Akhir</th>
                <th style="border-top: 1px solid #000;">Jlh Batang</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    <td class="data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number data-cell" style="text-align: center;">
                        {{ $formatNumeric('Tebal', $toFloat($row['Tebal'] ?? null)) }}</td>
                    <td class="number data-cell" style="text-align: center;">
                        {{ $formatNumeric('Lebar', $toFloat($row['Lebar'] ?? null)) }}</td>
                    <td class="number data-cell" style="text-align: center;">
                        {{ $formatNumeric('Panjang', $toFloat($row['Panjang'] ?? null)) }}</td>
                    <td class="number data-cell">{{ $formatNumeric('Sawal', $toFloat($row['Sawal'] ?? null)) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ $formatNumeric('SawalJlhBtg', $toFloat($row['SawalJlhBtg'] ?? null)) }}</td>
                    <td class="number data-cell">{{ $formatNumeric('Masuk', $toFloat($row['Masuk'] ?? null)) }}</td>
                    <td class="number data-cell">{{ $formatNumeric('MskJlhBtg', $toFloat($row['MskJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number data-cell">
                        {{ $formatNumeric('AdjusmentOutput', $toFloat($row['AdjusmentOutput'] ?? null)) }}</td>
                    <td class="number data-cell">
                        {{ $formatNumeric('AdjOutJlhBtg', $toFloat($row['AdjOutJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number data-cell">{{ $formatNumeric('Keluar', $toFloat($row['Keluar'] ?? null)) }}</td>
                    <td class="number data-cell">
                        {{ $formatNumeric('KeluarJlhBtg', $toFloat($row['KeluarJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number data-cell">
                        {{ $formatNumeric('AdjusmentInput', $toFloat($row['AdjusmentInput'] ?? null)) }}
                    </td>
                    <td class="number data-cell">
                        {{ $formatNumeric('AdjInJlhBtg', $toFloat($row['AdjInJlhBtg'] ?? null)) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ $formatNumeric('Akhir', $toFloat($row['Akhir'] ?? null)) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ $formatNumeric('AkhirJlhBtg', $toFloat($row['AkhirJlhBtg'] ?? null)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            @if (!empty($rows))
                <tr class="totals-row">
                    <td colspan="5" class="center" style="font-weight: bold;">Total</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Sawal', $toFloat($totals['Sawal'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('SawalJlhBtg', $toFloat($totals['SawalJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Masuk', $toFloat($totals['Masuk'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('MskJlhBtg', $toFloat($totals['MskJlhBtg'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjusmentOutput', $toFloat($totals['AdjusmentOutput'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjOutJlhBtg', $toFloat($totals['AdjOutJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Keluar', $toFloat($totals['Keluar'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('KeluarJlhBtg', $toFloat($totals['KeluarJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjusmentInput', $toFloat($totals['AdjusmentInput'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjInJlhBtg', $toFloat($totals['AdjInJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Akhir', $toFloat($totals['Akhir'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AkhirJlhBtg', $toFloat($totals['AkhirJlhBtg'] ?? null)) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
