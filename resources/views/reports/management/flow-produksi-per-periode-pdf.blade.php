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
            margin: 14mm 8mm 14mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 9px;
            line-height: 1.15;
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
            margin: 2px 0 14px 0;
            font-size: 12px;
            color: #636466;
        }

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
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
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
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

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        .summary-block {
            margin-top: 16px;
            width: 72%;
        }

        .summary-title {
            margin: 0 0 6px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-lines {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .summary-lines td {
            border: 0 !important;
            padding: 2px 4px 2px 0;
            vertical-align: top;
        }

        .summary-label {
            width: 130px;
            white-space: nowrap;
        }

        .summary-sep {
            width: 12px;
            text-align: center;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];
        $summaryLines = is_array($data['summary_lines'] ?? null) ? $data['summary_lines'] : [];
        $summaryLines = array_map(static function (array $line): array {
            $line['text'] = str_replace(
                'ST Masuk KD - ST Hasil Racip',
                'ST Hasil Racip - ST Masuk KD',
                (string) ($line['text'] ?? ''),
            );

            return $line;
        }, $summaryLines);
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmt = static function ($value, bool $blankWhenZero = true): string {
            if (!is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 4, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Flow Produksi Per-Periode</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Group Kayu</th>
                <th>Pembelian KB (Ton)</th>
                <th>KB diRacip (Ton)</th>
                <th>ST Hasil Racip (Ton)</th>
                <th>ST Siap Vacuum Stick</th>
                <th>ST Hasil Racip - ST Masuk KD (Ton)</th>
                <th>ST Keluar KD (Ton)</th>
                <th>ST Pakai di S4S (Ton)</th>
                <th>WIP Bersih S4S (m<sup>3</sup>)</th>
                <th>WIP Pakai di FJ (m<sup>3</sup>)</th>
                <th>WIP Hasil FJ (m<sup>3</sup>)</th>
                <th>WIP Pakai di Moulding (m<sup>3</sup>)</th>
                <th>WIP hasil Moulding (m<sup>3</sup>)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $row['No'] ?? $index + 1 }}</td>
                    <td>{{ $row['Group Kayu'] ?? '-' }}</td>
                    <td class="number">{{ $fmt($row['KBTonBeli'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['KBRacip'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['STRacipan'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['STVacuumStick'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['STKDIn'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['STKDOut'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['STm3Input'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['WIPBersihOutput'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['WIPFJInput'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['WIPFJOutput'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['WIPMouldingInput'] ?? null) }}</td>
                    <td class="number">{{ $fmt($row['WIPMouldingOutput'] ?? null) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Grand Total</td>
                <td class="number">{{ $fmt($totals['KBTonBeli'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['KBRacip'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['STRacipan'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['STVacuumStick'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['STKDIn'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['STKDOut'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['STm3Input'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['WIPBersihOutput'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['WIPFJInput'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['WIPFJOutput'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['WIPMouldingInput'] ?? null) }}</td>
                <td class="number">{{ $fmt($totals['WIPMouldingOutput'] ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-block">
        <div class="summary-title">Rangkuman/Rekap La. Flow Produksi :</div>
        <table class="summary-lines">
            <tbody>
                @foreach ($summaryLines as $line)
                    <tr>
                        <td class="summary-label">{{ $line['label'] ?? '' }}</td>
                        <td class="summary-sep">{{ ($line['label'] ?? '') !== '' ? ':' : '' }}</td>
                        <td>{{ $line['text'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
