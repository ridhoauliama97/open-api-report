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
            margin: 24mm 10mm 20mm 10mm;
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

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
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
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
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

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
            background: #fff;
        }

        .report-table tbody tr.data-row td {
            border-top: 0;
            border-bottom: 0;
        }


        tfoot {
            display: table-footer-group;
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
@include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $toFloat = static function ($value): float {
            return is_numeric($value) ? (float) $value : 0.0;
        };

        $fmt = static function (float $value, bool $blankWhenZero = true): string {
            if ($blankWhenZero && abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 4, '.', ',');
        };

        $tonToM3Factor = 1.416;
        $totalTon = 0.0;
        $totalM3 = 0.0;
    @endphp

    <h1 class="report-title">Laporan Saldo Kayu Bulat</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 35px;">No</th>
                <th>Tanggal Masuk</th>
                <th style="width: 140px;">Jenis Kayu</th>
                <th>Supplier</th>
                <th style="width: 85px;">Ton</th>
                <th style="width: 85px;">M3</th>
            </tr>
        </thead>
        
        <tfoot>
            <tr class="table-end-line">
                <td colspan="6"></td>
            </tr>
        </tfoot>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $ton = $toFloat($row['Ton'] ?? 0);
                    $m3 = $ton * $tonToM3Factor;
                    $totalTon += $ton;
                    $totalM3 += $m3;
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">
                        {{ \Carbon\Carbon::parse((string) ($row['DateCreate'] ?? now()))->locale('id')->translatedFormat('d-M-y') }}
                    </td>
                    <td class="label">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td>{{ (string) ($row['NmSupplier'] ?? '') }}</td>
                    <td class="number">{{ $fmt($ton, true) }}</td>
                    <td class="number">{{ $fmt($m3, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="4" class="center">Total (Ton)</td>
                <td class="number">{{ $fmt($totalTon, true) }}</td>
                <td class="number">{{ $fmt($totalM3, true) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
