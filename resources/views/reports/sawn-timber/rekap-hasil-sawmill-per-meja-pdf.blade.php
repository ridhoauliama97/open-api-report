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
            font-size: 9px;
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

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $fmt = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');
        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->format('d/m/Y');
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
                <th rowspan="2" style="width: 46px;">No.Meja</th>
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
                        <td class="number">{{ $fmtTotal($rowTotal) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="{{ 4 + count($dateKeys) }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($mejaGroups !== [])
                <tr class="totals-row">
                    <td colspan="3" class="center">Total (ton)</td>
                    @foreach ($dateKeys as $dk)
                        <td class="number">{{ $fmtTotal((float) ($totalsByDate[$dk] ?? 0.0)) }}</td>
                    @endforeach
                    <td class="number">{{ $fmtTotal($grandTotal) }}</td>
                </tr>
            @endif
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
