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

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        .report-table th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            background: #fff;
            border-bottom: 1px solid #000;
        }

        .report-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .center {
            text-align: center;
        }

        .label {
            text-align: left;
        }

        .number {
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

        .total-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
        }

        @include('reports.partials.pdf-footer-table-style');
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $numericColumns = [
            'BeratAwal',
            'BeratMasuk',
            'BJSortOutput',
            'InjctOutput',
            'HStampOutput',
            'PKncOutput',
            'BeratKeluar',
            'BrokerInput',
            'GiliInput',
            'BeratAkhir',
        ];
        $totals = array_fill_keys($numericColumns, 0.0);

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace([' ', ','], ['', '.'], $value));

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $fmt = static function (?float $value, bool $blankWhenZero = true): string {
            if ($value === null) {
                return '';
            }

            if ($blankWhenZero && abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 2, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Mutasi Reject</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 4%;">No</th>
                <th rowspan="2" style="width: 20%;">Nama Reject</th>
                <th rowspan="2" style="width: 8%;">Awal</th>
                <th colspan="4">Masuk</th>
                <th rowspan="2" style="width: 8%;">Total<br>Masuk</th>
                <th colspan="2">Keluar</th>
                <th rowspan="2" style="width: 8%;">Total<br>Keluar</th>
                <th rowspan="2" style="width: 8%;">Akhir</th>
            </tr>
            <tr>
                <th style="width: 8%;">BJ.Sort<br>Output</th>
                <th style="width: 8%;">H.Stamp<br>Output</th>
                <th style="width: 8%;">Inject<br>Output</th>
                <th style="width: 8%;">P.Kunci<br>Output</th>
                <th style="width: 8%;">Broker<br>Input</th>
                <th style="width: 8%;">Gilingan<br>input</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $namaReject = (string) ($row['NamaReject'] ?? '');
                    $values = [];
                    foreach ($numericColumns as $column) {
                        $values[$column] = $toFloat($row[$column] ?? null) ?? 0.0;
                        $totals[$column] += $values[$column];
                    }
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="label">{{ $namaReject }}</td>
                    <td class="number">{{ $fmt($values['BeratAwal']) }}</td>
                    <td class="number">{{ $fmt($values['BJSortOutput']) }}</td>
                    <td class="number">{{ $fmt($values['HStampOutput']) }}</td>
                    <td class="number">{{ $fmt($values['InjctOutput']) }}</td>
                    <td class="number">{{ $fmt($values['PKncOutput']) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($values['BeratMasuk']) }}</td>
                    <td class="number">{{ $fmt($values['BrokerInput']) }}</td>
                    <td class="number">{{ $fmt($values['GiliInput']) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($values['BeratKeluar']) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($values['BeratAkhir']) }}</td>
                </tr>
            @empty
                <tr class="row-odd">
                    <td colspan="12" class="center" style="font-weight: bold; font-size: 11px; font-style: italic;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse

            @if (!empty($rowsData))
                <tr class="total-row">
                    <td colspan="2" class="center">Total</td>
                    <td class="number">{{ $fmt($totals['BeratAwal']) }}</td>
                    <td class="number">{{ $fmt($totals['BJSortOutput']) }}</td>
                    <td class="number">{{ $fmt($totals['HStampOutput']) }}</td>
                    <td class="number">{{ $fmt($totals['InjctOutput']) }}</td>
                    <td class="number">{{ $fmt($totals['PKncOutput']) }}</td>
                    <td class="number">{{ $fmt($totals['BeratMasuk']) }}</td>
                    <td class="number">{{ $fmt($totals['BrokerInput']) }}</td>
                    <td class="number">{{ $fmt($totals['GiliInput']) }}</td>
                    <td class="number">{{ $fmt($totals['BeratKeluar']) }}</td>
                    <td class="number">{{ $fmt($totals['BeratAkhir']) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
