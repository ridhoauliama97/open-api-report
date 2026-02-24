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
            margin: 18mm 10mm 18mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family:"Noto Serif", serif;
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
            margin: 2px 0;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
            text-align: center;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: center;
            font-family:"Calibri","DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
        }

        .footer-wrap {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-wrap td {
            border: none;
            padding: 0;
            background: transparent;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
            text-align: left;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }

        .trend-up {
            color: #0b8f3c;
            font-weight: bold;
        }

        .trend-down {
            color: #d11a2a;
            font-weight: bold;
        }

        .trend-flat {
            color: #636466;
            font-weight: bold;
        }
    
        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    
        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rows = is_array($rows ?? null) ? $rows : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i:s');
        $period1StartText = \Carbon\Carbon::parse($period1StartDate)->locale('id')->translatedFormat('d M Y');
        $period1EndText = \Carbon\Carbon::parse($period1EndDate)->locale('id')->translatedFormat('d M Y');
        $period2StartText = \Carbon\Carbon::parse($period2StartDate)->locale('id')->translatedFormat('d M Y');
        $period2EndText = \Carbon\Carbon::parse($period2EndDate)->locale('id')->translatedFormat('d M Y');
        $formatNumber = static function ($value): string {
            $number = is_numeric($value) ? (float) $value : 0.0;
            if (abs($number) < 0.0000001) {
                return '';
            }
            return number_format($number, 4, '.', ',');
        };
        $formatPercent = static function ($value): string {
            $number = is_numeric($value) ? (float) $value : 0.0;
            if (abs($number) < 0.0000001) {
                return '';
            }
            return number_format($number, 0, '.', ',');
        };
        $calculatePercent = static function (float $ton1, float $ton2): float {
            if ($ton1 == 0.0 && $ton2 == 0.0) {
                return 0.0;
            }

            if ($ton1 == 0.0) {
                // Menyesuaikan pola existing laporan lama (nilai maksimum 999% saat basis nol).
                return 999.0;
            }

            return (($ton2 - $ton1) / $ton1) * 100;
        };

        $totalTon1 = 0.0;
        $totalTon2 = 0.0;

        foreach ($rows as $row) {
            $totalTon1 += is_numeric($row['Ton1'] ?? null) ? (float) $row['Ton1'] : 0.0;
            $totalTon2 += is_numeric($row['Ton2'] ?? null) ? (float) $row['Ton2'] : 0.0;
        }

        $totalPercent = $calculatePercent($totalTon1, $totalTon2);
    @endphp

    <h1 class="report-title">Laporan Perbandingan KB Masuk Periode 1 dan 2</h1>
    <p class="report-subtitle">
        Periode 1: {{ $period1StartText }} s/d {{ $period1EndText }}
    </p>
    <p class="report-subtitle" style="margin-bottom:20px">
        Periode 2: {{ $period2StartText }} s/d {{ $period2EndText }}
    </p>

    <table>
        <thead>
            <tr class="headers-row" style="border: 1.5px solid #000">
                <th style="width: 34px;">No</th>
                <th>Nama Supplier</th>
                <th style="width: 120px;">No.Tlp/HP</th>
                <th style="width: 80px;">Ton1</th>
                <th style="width: 80px;">Ton2</th>
                <th style="width: 110px;">Persen (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $ton1 = is_numeric($row['Ton1'] ?? null) ? (float) $row['Ton1'] : 0.0;
                    $ton2 = is_numeric($row['Ton2'] ?? null) ? (float) $row['Ton2'] : 0.0;
                    $percent = $calculatePercent($ton1, $ton2);
                    $trendClass = $percent > 0 ? 'trend-up' : ($percent < 0 ? 'trend-down' : 'trend-flat');
                    $trendIcon = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '→');
                    $percentText = $formatPercent($percent);
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ (string) ($row['NmSupplier'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['NoTlp'] ?? '') }}</td>
                    <td class="number">{{ $formatNumber($ton1) }}</td>
                    <td class="number">{{ $formatNumber($ton2) }}</td>
                    <td class="number {{ $trendClass }}">
                        {{ $percentText !== '' ? $percentText . '%' : '' }}{{ $percentText !== '' ? ' ' . $trendIcon : '' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="6">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="grand-total-row totals-row" style="border: 1.5px solid #000">
                <td colspan="3" class="center">Grand Total</td>
                <td class="number">{{ $formatNumber($totalTon1) }}</td>
                <td class="number">{{ $formatNumber($totalTon2) }}</td>
                <td
                    class="number {{ $totalPercent > 0 ? 'trend-up' : ($totalPercent < 0 ? 'trend-down' : 'trend-flat') }}">
                    {{ $formatPercent($totalPercent) !== '' ? $formatPercent($totalPercent) . '%' : '' }}{{ $formatPercent($totalPercent) !== '' ? ' ' . ($totalPercent > 0 ? '↑' : ($totalPercent < 0 ? '↓' : '→')) : '' }}
                </td>
            </tr>
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <table class="footer-wrap">
            <tr>
                <td class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</td>
                <td class="footer-right">Halaman {PAGENO} dari {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
