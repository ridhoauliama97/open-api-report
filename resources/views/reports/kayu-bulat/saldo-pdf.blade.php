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
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 10px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
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
            border: 1px solid #9ca3af;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #ffffff;
            color: #000;
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
            background: #dde4f2;
            font-weight: 700;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $start = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $end = \Carbon\Carbon::parse($endDate)->format('d/m/Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $toFloat = static function ($value): float {
            return is_numeric($value) ? (float) $value : 0.0;
        };

        $fmt = static function (float $value, bool $blankWhenZero = true): string {
            if ($blankWhenZero && abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 4, '.', '');
        };

        $tonToM3Factor = 1.416;
        $totalTon = 0.0;
        $totalM3 = 0.0;
    @endphp

    <h1 class="report-title">Laporan Saldo Kayu Bulat</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 90px;">Tanggal Masuk</th>
                <th style="width: 140px;">Jenis Kayu</th>
                <th>Supplier</th>
                <th style="width: 85px;">Ton</th>
                <th style="width: 85px;">M3</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $ton = $toFloat($row['Ton'] ?? 0);
                    $m3 = $ton * $tonToM3Factor;
                    $totalTon += $ton;
                    $totalM3 += $m3;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ (string) ($row['DateCreate'] ?? '') }}</td>
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

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
