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
            margin: 16mm 8mm 18mm 8mm;
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
            margin-bottom: 8px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            border: 1px solid #000;
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            font-size: 10px;
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
            border: 1.5px solid #000;
            font-weight: bold;
            font-size: 11px;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        $startText = \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y');
        $endText = \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
        $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Dashboard S4S</h1>
    <p class="report-subtitle">Dari {{ $startText }} s/d {{ $endText }}</p>

    <table>
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width: 40px;">Tanggal</th>
                @foreach ($groups as $group)
                    <th colspan="3">{{ $group['label'] ?? '-' }}</th>
                @endforeach
            </tr>
            <tr class="headers-row">
                @foreach ($groups as $group)
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Akhir</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td style="text-align: center;">
                        {{ \Carbon\Carbon::parse((string) ($row['date'] ?? now()))->format('d') }}</td>
                    @foreach ($groups as $groupKey => $group)
                        @php
                            $masuk = (float) ($row['cells'][$groupKey]['masuk'] ?? 0);
                            $keluar = (float) ($row['cells'][$groupKey]['keluar'] ?? 0);
                            $akhir = (float) ($row['cells'][$groupKey]['akhir'] ?? 0);
                        @endphp
                        <td class="number">{{ abs($masuk) < 0.000001 ? '' : $fmt1($masuk) }}</td>
                        <td class="number">{{ abs($keluar) < 0.000001 ? '' : $fmt1($keluar) }}</td>
                        <td class="number">{{ abs($akhir) < 0.000001 ? '' : $fmt1($akhir) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 1 + count($groups) * 3 }}" style="text-align: center;">Data tidak tersedia.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td>Jumlah Container</td>
                @foreach ($groups as $group)
                    <td class="number" colspan="3" style="text-align: center;">{{ $fmt2($group['container'] ?? 0) }}
                    </td>
                @endforeach
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
