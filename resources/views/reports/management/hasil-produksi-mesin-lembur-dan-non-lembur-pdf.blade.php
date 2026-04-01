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
            margin: 10mm 6mm 12mm 6mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
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
            padding: 2px 4px;
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

        .date-group-start td {
            border-top: 1px solid #000 !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groupedRows = is_array($data['grouped_rows'] ?? null) ? $data['grouped_rows'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtNumber = static function ($value, int $decimals = 4, bool $blankWhenZero = true): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, $decimals, '.', ',');
        };

        $fmtInt = static function ($value): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            return number_format((float) $value, 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Hasil Produksi Mesin Lembur Dan Non Lembur</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <colgroup>
            <col style="width: 78px;">
            <col style="width: 108px;">
            <col style="width: 40px;">
            <col style="width: 42px;">
            <col style="width: 58px;">
            <col style="width: 40px;">
            <col style="width: 42px;">
            <col style="width: 58px;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">Tanggal</th>
                <th rowspan="2">Nama Mesin</th>
                <th colspan="3">Jam Kerja Normal</th>
                <th colspan="3">Jam Kerja Lembur</th>
            </tr>
            <tr>
                <th>TK</th>
                <th>HM</th>
                <th>mtr3</th>
                <th>TK</th>
                <th>HM</th>
                <th>mtr3</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="8"></td>
            </tr>
        </tfoot>
        <tbody>
            @php $rowIndex = 0; @endphp
            @foreach ($groupedRows as $group)
                @php
                    $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                    $rowspan = count($rows);
                @endphp
                @foreach ($rows as $innerIndex => $row)
                    @php $rowIndex++; @endphp
                    <tr
                        class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $innerIndex === 0 ? 'date-group-start' : '' }}">
                        @if ($innerIndex === 0)
                            <td rowspan="{{ $rowspan }}" class="center">
                                {{ $reportService->formatTanggalDisplay((string) ($group['Tanggal'] ?? ''), (string) ($group['Hari'] ?? '')) }}
                            </td>
                        @endif
                        <td>{{ $row['NamaMesin'] ?? '-' }}</td>
                        <td class="center">{{ $fmtInt($row['JmlhAnggota'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['JamKerja'] ?? null) }}</td>
                        <td class="number">{{ $fmtNumber($row['Output'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['JmlhAnggotaLembur'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['JamKerjaLembur'] ?? null) }}</td>
                        <td class="number">{{ $fmtNumber($row['OutputLembur'] ?? null) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="center">Grand Total</td>
                <td></td>
                <td></td>
                <td class="number">{{ $fmtNumber($grandTotals['output'] ?? null) }}</td>
                <td></td>
                <td></td>
                <td class="number">{{ $fmtNumber($grandTotals['output_lembur'] ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
