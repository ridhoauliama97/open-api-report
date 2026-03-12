<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 12mm 10mm 14mm 10mm;
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
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
        }

        .kesimpulan-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .kesimpulan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .kesimpulan-table td {
            border: none;
            padding: 1px 0;
            vertical-align: top;
        }

        .kesimpulan-label {
            white-space: nowrap;
            padding-right: 6px;
        }

        .kesimpulan-sep {
            width: 8px;
            text-align: center;
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
        $data = is_array($reportData ?? null) ? $reportData : [];
        $dateKeys = is_array($data['date_keys'] ?? null) ? $data['date_keys'] : [];
        $mejaGroups = is_array($data['meja_groups'] ?? null) ? $data['meja_groups'] : [];
        $totalsByDate = is_array($data['totals_by_date'] ?? null) ? $data['totals_by_date'] : [];
        $grandTotal = (float) ($data['grand_total'] ?? 0.0);

        $jumlahHk = count($dateKeys);

        // HK meja sawmill = total kombinasi meja x tanggal yang punya ton > 0 (di semua tebal/UOM).
        $jumlahHkMeja = 0;
        $eps = 0.0000001;
        foreach ($mejaGroups as $g) {
            $rows = is_array($g['rows'] ?? null) ? $g['rows'] : [];
            foreach ($dateKeys as $dk) {
                $sum = 0.0;
                foreach ($rows as $r) {
                    $values = is_array($r['values'] ?? null) ? $r['values'] : [];
                    $sum += (float) ($values[$dk] ?? 0.0);
                }
                if (abs($sum) >= $eps) {
                    $jumlahHkMeja++;
                }
            }
        }

        $tonPerHari = $jumlahHk > 0 ? $grandTotal / $jumlahHk : 0.0;
        $tonPerMejaPerHari = $jumlahHkMeja > 0 ? $grandTotal / $jumlahHkMeja : 0.0;

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $fmt = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');
        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->format('d-M');
            } catch (\Throwable $exception) {
                return $key;
            }
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Hasil Sawmill / Meja</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 46px;">No. Meja</th>
                <th rowspan="2" style="width: 56px;">Tebal</th>
                <th rowspan="2" style="width: 40px;">UOM</th>
                <th colspan="{{ count($dateKeys) + 1 }}">Tanggal</th>
            </tr>
            <tr>
                @foreach ($dateKeys as $dk)
                    <th style="width: 52px;">{{ $dateLabel($dk) }}</th>
                @endforeach
                <th style="width: 56px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($mejaGroups as $mejaIndex => $group)
                @php
                    $noMeja = (int) ($group['no_meja'] ?? 0);
                    $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                    $rowspan = max(1, count($rows));
                @endphp

                @foreach ($rows as $ridx => $r)
                    @php
                        $rowIndex++;
                        $values = is_array($r['values'] ?? null) ? $r['values'] : [];
                        $rowTotal = (float) ($r['row_total'] ?? 0.0);
                        $tebal = (float) ($r['tebal'] ?? 0.0);
                        $uom = (string) ($r['uom'] ?? '');
                    @endphp
                    <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        @if ($ridx === 0)
                            <td class="center" rowspan="{{ $rowspan }}">{{ $noMeja }}</td>
                        @endif
                        <td class="center">{{ rtrim(rtrim(number_format($tebal, 1, '.', ','), '0'), '.') }}</td>
                        <td class="center">{{ $uom }}</td>
                        @foreach ($dateKeys as $dk)
                            <td class="number">{{ $fmt((float) ($values[$dk] ?? 0.0)) }}</td>
                        @endforeach
                        <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtTotal($rowTotal) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="{{ 4 + count($dateKeys) }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($mejaGroups !== [])
                <tr class="totals-row">
                    <td colspan="3" class="center">Total (Ton)</td>
                    @foreach ($dateKeys as $dk)
                        <td class="number">{{ $fmtTotal((float) ($totalsByDate[$dk] ?? 0.0)) }}</td>
                    @endforeach
                    <td class="number">{{ $fmtTotal($grandTotal) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="kesimpulan-title">Kesimpulan</div>
    <table class="kesimpulan-table">
        <tr>
            <td style="width: 50%;">
                <table class="kesimpulan-table">
                    <tr>
                        <td class="kesimpulan-label">Jumlah HK</td>
                        <td class="kesimpulan-sep">:</td>
                        <td class="number">{{ $jumlahHk }}</td>
                    </tr>
                    <tr>
                        <td class="kesimpulan-label">Ton/Hari</td>
                        <td class="kesimpulan-sep">:</td>
                        <td class="number">{{ $fmtTotal($tonPerHari) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table class="kesimpulan-table">
                    <tr>
                        <td class="kesimpulan-label">Jumlah HK Meja Sawmill</td>
                        <td class="kesimpulan-sep">:</td>
                        <td class="number">{{ $jumlahHkMeja }}</td>
                    </tr>
                    <tr>
                        <td class="kesimpulan-label">Ton/Meja/Hari</td>
                        <td class="kesimpulan-sep">:</td>
                        <td class="number">{{ $fmtTotal($tonPerMejaPerHari) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
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
