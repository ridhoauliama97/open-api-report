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
            font-family: "Noto Serif", serif;
            font-size: 8px;
            line-height: 1.2;
            color: #000;
        }

        .title {
            text-align: center;
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .subtitle {
            text-align: center;
            margin: 2px 0 8px 0;
            font-size: 10px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            background: #fff;
            font-weight: 700;
        }

        td.label {
            white-space: nowrap;
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

        .chart-wrap {
            padding: 6px;
            margin-top: 8px;
            margin-bottom: 10px;
        }

        .chart-title {
            text-align: center;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .summary-list {
            margin: 4px 0 10px 0;
            padding-left: 16px;
        }

        .summary-list li {
            margin: 2px 0;
            font-size: 10px;
        }

        .summary-label {
            display: inline-block;
            min-width: 190px;
            font-weight: 700;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 7px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $chartData = is_array($chartData ?? null) ? $chartData : [];
        $dates = is_array($chartData['dates'] ?? null) ? $chartData['dates'] : [];
        $types = is_array($chartData['types'] ?? null) ? $chartData['types'] : [];
        $totalsByType = is_array($chartData['totals_by_type'] ?? null) ? $chartData['totals_by_type'] : [];
        $stockByType = is_array($chartData['stock_by_type'] ?? null) ? $chartData['stock_by_type'] : [];
        $stockTotals = is_array($chartData['stock_totals'] ?? null)
            ? $chartData['stock_totals']
            : ['s_akhir' => 0, 'ctr' => 0];
        $dailyIn = is_array($chartData['daily_in_totals'] ?? null) ? $chartData['daily_in_totals'] : [];
        $dailyOut = is_array($chartData['daily_out_totals'] ?? null) ? $chartData['daily_out_totals'] : [];
        $rawRows = is_array($chartData['raw_rows'] ?? null) ? $chartData['raw_rows'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, ',', '.');
        $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, ',', '.');

        $dailyNet = [];
        $totalInAll = 0.0;
        $totalOutAll = 0.0;
        foreach ($dailyIn as $idx => $value) {
            $inVal = (float) ($value ?? 0);
            $outVal = (float) ($dailyOut[$idx] ?? 0);
            $dailyNet[] = $inVal - $outVal;
            $totalInAll += $inVal;
            $totalOutAll += $outVal;
        }
        $netAll = $totalInAll - $totalOutAll;

        $lineW = 980;
        $lineH = 240;
        $padL = 56;
        $padR = 12;
        $padT = 14;
        $padB = 34;
        $plotW = $lineW - $padL - $padR;
        $plotH = $lineH - $padT - $padB;
        $xStep = count($dates) > 1 ? $plotW / (count($dates) - 1) : 0;
        $maxLine = 0.0;
        foreach ($dailyIn as $v) {
            if ((float) $v > $maxLine) {
                $maxLine = (float) $v;
            }
        }
        foreach ($dailyOut as $v) {
            if ((float) $v > $maxLine) {
                $maxLine = (float) $v;
            }
        }
        $yStep = 200.0;
        $maxLine = $maxLine > 0 ? $maxLine : $yStep;
        $maxLine = ceil($maxLine / $yStep) * $yStep;
        $yTicks = max((int) ($maxLine / $yStep), 1);

        $barW = 980;
        $barH = 260;
        $barPadL = 170;
        $barPadR = 16;
        $barPadT = 12;
        $barPadB = 20;
        $barPlotW = $barW - $barPadL - $barPadR;
        $barRowH = max(count($types), 1) > 0 ? ($barH - $barPadT - $barPadB) / max(count($types), 1) : 20;
        $maxSakhir = 0.0;
        foreach ($types as $t) {
            $v = (float) ($stockByType[$t]['s_akhir'] ?? 0);
            if ($v > $maxSakhir) {
                $maxSakhir = $v;
            }
        }
        $maxSakhir = $maxSakhir > 0 ? $maxSakhir : 1.0;
    @endphp

    <h1 class="title">Dashboard Sawn Timber</h1>
    <p class="subtitle">Dari {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d
        {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Total Masuk</th>
                <th>Total Keluar</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $idx => $type)
                @php
                    $inVal = (float) ($totalsByType[$type]['in'] ?? 0);
                    $outVal = (float) ($totalsByType[$type]['out'] ?? 0);
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="number" style="text-align: center">{{ $idx + 1 }}</td>
                    <td class="label">{{ $type }}</td>
                    <td class="number">{{ $fmt1($inVal) }}</td>
                    <td class="number">{{ $fmt1($outVal) }}</td>
                    <td class="number">{{ $fmt1($inVal - $outVal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="chart-wrap">
        <p class="chart-title">Trend Harian Total Masuk vs Keluar</p>
        <svg width="{{ $lineW }}" height="{{ $lineH }}"
            viewBox="0 0 {{ $lineW }} {{ $lineH }}" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="{{ $lineW }}" height="{{ $lineH }}" fill="#fff" />
            <line x1="{{ $padL }}" y1="{{ $padT + $plotH }}" x2="{{ $padL + $plotW }}"
                y2="{{ $padT + $plotH }}" stroke="#333" stroke-width="1" />
            <line x1="{{ $padL }}" y1="{{ $padT }}" x2="{{ $padL }}"
                y2="{{ $padT + $plotH }}" stroke="#333" stroke-width="1" />
            @for ($i = 0; $i <= $yTicks; $i++)
                @php
                    $y = $padT + $plotH - ($plotH / $yTicks) * $i;
                    $tick = $yStep * $i;
                @endphp
                <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $padL + $plotW }}"
                    y2="{{ $y }}" stroke="#e3e3e3" stroke-width="1" />
                <text x="{{ $padL - 6 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="9">
                    {{ number_format($tick, 0, '.', '') }}</text>
            @endfor
            @foreach ($dates as $idx => $date)
                @php
                    $x = $padL + $idx * $xStep;
                    $d = \Carbon\Carbon::parse($date)->format('d/m');
                @endphp
                <text x="{{ $x }}" y="{{ $padT + $plotH + 14 }}" text-anchor="middle"
                    font-size="8">{{ $d }}</text>
            @endforeach
            @php
                $inPts = [];
                $outPts = [];
                foreach ($dates as $idx => $date) {
                    $x = $padL + $idx * $xStep;
                    $inVal = (float) ($dailyIn[$idx] ?? 0);
                    $outVal = (float) ($dailyOut[$idx] ?? 0);
                    $inY = $padT + $plotH - ($inVal / $maxLine) * $plotH;
                    $outY = $padT + $plotH - ($outVal / $maxLine) * $plotH;
                    $inPts[] = round($x, 2) . ',' . round($inY, 2);
                    $outPts[] = round($x, 2) . ',' . round($outY, 2);
                }
            @endphp
            <polyline points="{{ implode(' ', $inPts) }}" fill="none" stroke="#0d6efd" stroke-width="1.8" />
            <polyline points="{{ implode(' ', $outPts) }}" fill="none" stroke="#dc3545" stroke-width="1.8" />
            <text x="{{ $padL + 8 }}" y="{{ $padT + 10 }}" font-size="9" fill="#0d6efd">Masuk</text>
            <text x="{{ $padL + 50 }}" y="{{ $padT + 10 }}" font-size="9" fill="#dc3545">Keluar</text>
        </svg>
    </div>


    <table>
        <thead>
            <tr>
                <th>Jenis</th>
                <th>S Akhir</th>
                <th>#Ctr</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $type)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="label">{{ $type }}</td>
                    <td class="number">{{ $fmt1($stockByType[$type]['s_akhir'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2($stockByType[$type]['ctr'] ?? 0) }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="label" style="font-weight:bold; text-align: center;">Total</td>
                <td class="number" style="font-weight:bold;">{{ $fmt1($stockTotals['s_akhir'] ?? 0) }}</td>
                <td class="number" style="font-weight:bold;">{{ $fmt2($stockTotals['ctr'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="chart-wrap">
        <p class="chart-title">S Akhir per Jenis</p>
        <svg width="{{ $barW }}" height="{{ $barH }}"
            viewBox="0 0 {{ $barW }} {{ $barH }}" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="{{ $barW }}" height="{{ $barH }}" fill="#fff" />
            @foreach ($types as $idx => $type)
                @php
                    $val = (float) ($stockByType[$type]['s_akhir'] ?? 0);
                    $barLen = ($val / $maxSakhir) * $barPlotW;
                    $y = $barPadT + $idx * $barRowH + 2;
                    $h = max($barRowH - 4, 4);
                @endphp
                <text x="{{ $barPadL - 6 }}" y="{{ $y + $h / 2 + 3 }}" text-anchor="end"
                    font-size="8">{{ $type }}</text>
                <rect x="{{ $barPadL }}" y="{{ $y }}" width="{{ $barLen }}"
                    height="{{ $h }}" fill="#198754" />
                <text x="{{ $barPadL + $barLen + 4 }}" y="{{ $y + $h / 2 + 3 }}"
                    font-size="8">{{ number_format($val, 1, '.', '') }}</text>
            @endforeach
        </svg>
    </div>

    <p style="font-size: 10px; margin-bottom: 5px; font-weight: bold; text-decoration: underline;">Summary :</p>
    <table style="width: 55%;">
        <tbody>
            <tr>
                <td class="label">Jumlah Seluruh Hari</td>
                <td class="number">{{ number_format(count($dates), 0, ',', '.') }} Hari</td>
            </tr>
            <tr>
                <td class="label">Jumlah Seluruh Jenis ST</td>
                <td class="number">{{ number_format(count($types), 0, ',', '.') }}Jenis </td>
            </tr>
            <tr>
                <td class="label">Jumlah Baris Raw Data Terhitung</td>
                <td class="number">{{ number_format(count($rawRows), 0, ',', '.') }} Baris Data</td>
            </tr>
            <tr>
                <td class="label">Total Masuk Keseluruhan (Semua Jenis ST)</td>
                <td class="number">{{ $fmt1($totalInAll) }}</td>
            </tr>
            <tr>
                <td class="label">Total Keluar Keseluruhan (Semua Jenis ST)</td>
                <td class="number">{{ $fmt1($totalOutAll) }}</td>
            </tr>
            <tr>
                <td class="label">Total Net Keseluruhan (Semua Jenis ST)</td>
                <td class="number">{{ $fmt1($netAll) }}</td>
            </tr>
        </tbody>
    </table>


    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
