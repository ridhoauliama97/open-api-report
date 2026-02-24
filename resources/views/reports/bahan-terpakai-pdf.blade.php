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
            margin: 24mm 12mm 20mm 12mm;
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
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 10px;
            color: #636466;
        }

        .group-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
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
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #ffffff;
            color: #000;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family:"Calibry","Calibri","DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: 700;
            background: #fff;
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
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $subRowsData =
            isset($subRows) && is_iterable($subRows)
                ? (is_array($subRows)
                    ? $subRows
                    : collect($subRows)->values()->all())
                : [];
        $reportDateText = \Carbon\Carbon::parse($reportDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');
        $tonToM3Factor = isset($tonToM3Factor) ? (float) $tonToM3Factor : 1.416;

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (is_string($value)) {
                $normalized = str_replace(',', '.', trim($value));
                if (is_numeric($normalized)) {
                    return (float) $normalized;
                }
            }

            return null;
        };

        $formatNumber = static function ($value, int $decimals = 4) use ($toFloat): string {
            $numeric = $toFloat($value);

            return $numeric !== null ? number_format($numeric, $decimals, '.', ',') : '';
        };

        $subGroups = collect($subRowsData)
            ->groupBy(static fn(array $row): string => (string) ($row['Group'] ?? 'Tanpa Group'))
            ->all();

        $mainGroups = collect($rowsData)
            ->groupBy(static fn(array $row): string => (string) ($row['Group'] ?? 'Tanpa Group'))
            ->all();
        $hasData = !empty($subGroups) || !empty($mainGroups);
    @endphp

    <h1 class="report-title">Laporan Rangkuman Bahan Terpakai</h1>
    <p class="report-subtitle">Per Tanggal : {{ $reportDateText }}</p>

    @foreach ($subGroups as $groupName => $groupRows)
        <p class="group-title">{{ $groupName }}</p>
        <table>
            <thead>
                <tr class="headers-row">
                    <th>NamaMesin</th>
                    <th>Jenis</th>
                    <th style="width: 62px;">Tebal</th>
                    <th style="width: 62px;">Lebar</th>
                    <th style="width: 90px;">Ton</th>
                    <th style="width: 90px;">m3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupRows as $row)
                    @php
                        $ton = $toFloat($row['Ton'] ?? null);
                        $m3 = $ton !== null ? $ton * $tonToM3Factor : null;
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="label">{{ (string) ($row['NamaMesin'] ?? '') }}</td>
                        <td class="label">{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td class="number">{{ $formatNumber($row['Tebal'] ?? null, 0) }}</td>
                        <td class="number">{{ $formatNumber($row['Lebar'] ?? null, 0) }}</td>
                        <td class="number">{{ $formatNumber($ton, 4) }}</td>
                        <td class="number">{{ $formatNumber($m3, 4) }}</td>
                    </tr>
                @endforeach
                @php
                    $totalTon = collect($groupRows)->sum(
                        static fn(array $row): float => $toFloat($row['Ton'] ?? null) ?? 0.0,
                    );
                    $totalM3 = $totalTon * $tonToM3Factor;
                @endphp
                <tr class="total-row totals-row">
                    <td colspan="4" class="number" style="font-weight: bold; text-align: center;">Total</td>
                    <td class="number" style="font-weight: bold">{{ $formatNumber($totalTon, 4) }}</td>
                    <td class="number" style="font-weight: bold">{{ $formatNumber($totalM3, 4) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @foreach ($mainGroups as $groupName => $groupRows)
        <p class="group-title">{{ $groupName }}</p>
        <table>
            <thead>
                <tr class="headers-row">
                    <th>NamaMesin</th>
                    <th>Jenis</th>
                    <th style="width: 62px;">Tebal</th>
                    <th style="width: 62px;">Lebar</th>
                    <th style="width: 76px;">Panjang</th>
                    <th style="width: 80px;">Jlh Batang</th>
                    <th style="width: 90px;">Kubik</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupRows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="label">{{ (string) ($row['NamaMesin'] ?? '') }}</td>
                        <td class="label">{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td class="number">{{ $formatNumber($row['Tebal'] ?? null, 0) }}</td>
                        <td class="number">{{ $formatNumber($row['Lebar'] ?? null, 0) }}</td>
                        <td class="number">{{ $formatNumber($row['Panjang'] ?? null, 0) }}</td>
                        <td class="number">{{ $formatNumber($row['JmlhBatang'] ?? null, 0) }}</td>
                        <td class="number">{{ $formatNumber($row['KubikIN'] ?? null, 4) }}</td>
                    </tr>
                @endforeach
                @php
                    $totalBatang = collect($groupRows)->sum(
                        static fn(array $row): float => $toFloat($row['JmlhBatang'] ?? null) ?? 0.0,
                    );
                    $totalKubik = collect($groupRows)->sum(
                        static fn(array $row): float => $toFloat($row['KubikIN'] ?? null) ?? 0.0,
                    );
                @endphp
                <tr class="total-row totals-row">
                    <td colspan="5" class="number" style="text-align: center; font-weight: bold;">Total</td>
                    <td class="number" style="font-weight: bold;">{{ $formatNumber($totalBatang, 0) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $formatNumber($totalKubik, 4) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if (!$hasData)
        <table>
            <tbody>
                <tr>
                    <td style="text-align: center;">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
