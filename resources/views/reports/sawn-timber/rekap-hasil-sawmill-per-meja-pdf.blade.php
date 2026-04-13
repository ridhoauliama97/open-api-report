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
            margin: 15mm 10mm 15mm 10mm;
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
            border: 1px solid #000;
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
            /* Default: hanya garis vertikal antar kolom (seperti laporan-laporan lain). */
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

        /* Hilangkan garis horizontal antar baris data (tetap sisakan garis vertikal antar kolom). */
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
            border-top: 1px solid #000;
        }

        .group-start td {
            border-top: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        /* Footer line to “close” the table on each page fragment when table is split across pages. */
        .tfoot-line td {
            border-top: 1px solid #000;
            padding: 0;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        .kesimpulan-title {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .kesimpulan-item {
            font-weight: bold;
            display: flex;
            align-items: baseline;
            gap: 6px;
            padding: 1px 0;
        }

        .kesimpulan-item .label {
            font-weight: bold;
            white-space: nowrap;
        }

        .kesimpulan-item .sep {
            width: 8px;
            text-align: center;
            flex: 0 0 auto;
        }

        .kesimpulan-item .value {
            margin-left: auto;
            text-align: right;
            white-space: nowrap;
        }

        .kesimpulan-grid {
            width: 100%;
            margin-top: 10px;
            border: 0;
        }

        .kesimpulan-grid td {
            width: 50%;
            border: 0;
            padding: 0 18px 0 0;
            vertical-align: top;
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
                    $subtotalByDate = [];
                    foreach ($dateKeys as $dk) {
                        $subtotalByDate[$dk] = 0.0;
                    }
                    $subtotalGroup = 0.0;
                @endphp

                @foreach ($rows as $ridx => $r)
                    @php
                        $rowIndex++;
                        $values = is_array($r['values'] ?? null) ? $r['values'] : [];
                        $rowTotal = (float) ($r['row_total'] ?? 0.0);
                        $tebal = (float) ($r['tebal'] ?? 0.0);
                        $uom = (string) ($r['uom'] ?? '');
                        foreach ($dateKeys as $dk) {
                            $subtotalByDate[$dk] += (float) ($values[$dk] ?? 0.0);
                        }
                        $subtotalGroup += $rowTotal;
                    @endphp
                    <tr
                        class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}{{ $ridx === 0 ? ' group-start' : '' }}">
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

                @if ($rows !== [])
                    <tr class="subtotal-row">
                        <td colspan="3" class="center">Sub Total Meja {{ $noMeja }}</td>
                        @foreach ($dateKeys as $dk)
                            <td class="number">{{ $fmtTotal((float) ($subtotalByDate[$dk] ?? 0.0)) }}</td>
                        @endforeach
                        <td class="number">{{ $fmtTotal($subtotalGroup) }}</td>
                    </tr>
                @endif
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

    <table class="kesimpulan-grid">
        <tbody>
            <tr>
                <td>
                    <div class="kesimpulan-item">
                        <span class="label">Jumlah HK</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $jumlahHk }}</span>
                    </div>
                </td>
                <td>
                    <div class="kesimpulan-item">
                        <span class="label">Jumlah HK Meja Sawmill</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $jumlahHkMeja }}</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="kesimpulan-item">
                        <span class="label">Ton/Hari</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $fmtTotal($tonPerHari) }}</span>
                    </div>
                </td>
                <td>
                    <div class="kesimpulan-item">
                        <span class="label">Ton/Meja/Hari</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $fmtTotal($tonPerMejaPerHari) }}</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
