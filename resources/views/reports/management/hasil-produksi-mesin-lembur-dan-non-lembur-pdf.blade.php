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
        $groupedRows = is_array($data['grouped_rows'] ?? null) ? $data['grouped_rows'] : [];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
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

    @endphp

    <h1 class="report-title">Laporan Hasil Produksi Mesin Lembur Dan Non Lembur</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 15%;">Tanggal</th>
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
                        <td class="center">{{ $fmtNumber($row['JmlhAnggota'] ?? null, 0, true) }}</td>
                        <td class="center">{{ $fmtNumber($row['JamKerja'] ?? null, 0, true) }}</td>
                        <td class="number">{{ $fmtNumber($row['Output'] ?? null, 4, true) }}</td>
                        <td class="center">{{ $fmtNumber($row['JmlhAnggotaLembur'] ?? null, 0, true) }}</td>
                        <td class="center">{{ $fmtNumber($row['JamKerjaLembur'] ?? null, 0, true) }}</td>
                        <td class="number">{{ $fmtNumber($row['OutputLembur'] ?? null, 4, true) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="center">Grand Total</td>
                <td></td>
                <td></td>
                <td class="number">{{ $fmtNumber($grandTotals['output'] ?? null, 4, true) }}</td>
                <td></td>
                <td></td>
                <td class="number">{{ $fmtNumber($grandTotals['output_lembur'] ?? null, 4, true) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="page-break-before: always;">
        <h2 style="text-align: center; margin: 0 0 8px 0; font-size: 14px; font-weight: bold;">Rangkuman</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">No</th>
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
            <tbody>
                @foreach ($summaryRows as $row)
                    <tr class="{{ $loop->index % 2 === 0 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $row['No'] ?? '' }}</td>
                        <td>{{ $row['NamaMesin'] ?? '-' }}</td>
                        <td class="center">{{ $fmtNumber($row['total_tk'] ?? null, 0, true) }}</td>
                        <td class="center">{{ $fmtNumber($row['total_hm'] ?? null, 0, true) }}</td>
                        <td class="number">{{ $fmtNumber($row['Output'] ?? null, 4, true) }}</td>
                        <td class="center">{{ $fmtNumber($row['total_tk_lembur'] ?? null, 0, true) }}</td>
                        <td class="center">{{ $fmtNumber($row['total_hm_lembur'] ?? null, 0, true) }}</td>
                        <td class="number">{{ $fmtNumber($row['OutputLembur'] ?? null, 4, true) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="center">Grand Total</td>
                    <td class="center">{{ $fmtNumber($grandTotals['total_tk'] ?? null, 0, true) }}</td>
                    <td class="center">{{ $fmtNumber($grandTotals['total_hm'] ?? null, 0, true) }}</td>
                    <td class="number">{{ $fmtNumber($grandTotals['output'] ?? null, 4, true) }}</td>
                    <td class="center">{{ $fmtNumber($grandTotals['total_tk_lembur'] ?? null, 0, true) }}</td>
                    <td class="center">{{ $fmtNumber($grandTotals['total_hm_lembur'] ?? null, 0, true) }}</td>
                    <td class="number">{{ $fmtNumber($grandTotals['output_lembur'] ?? null, 4, true) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    </body>

</html>
