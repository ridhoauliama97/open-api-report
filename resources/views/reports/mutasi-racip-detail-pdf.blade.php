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
            margin: 18mm 8mm 18mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 8px;
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
            margin: 2px 0 8px 0;
            font-size: 12px;
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
            border: 1px solid #666;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #fff;
        }

        td.center {
            text-align: center;
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

        .totals-row td {
            background: #fff;
            font-weight: 700;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 7px;
            font-style: italic;
        }

        .footer-right {
            font-size: 7px;
            font-style: italic;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_iterable($reportData['rows'] ?? null)
            ? (is_array($reportData['rows'])
                ? $reportData['rows']
                : collect($reportData['rows'])->values()->all())
            : [];
        $detailColumns = is_array($reportData['detail_columns'] ?? null) ? $reportData['detail_columns'] : [];
        $numericColumns = is_array($reportData['numeric_columns'] ?? null) ? $reportData['numeric_columns'] : [];
        $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : [];

        $startText = $reportData['start_date_text'] ?? \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endText = $reportData['end_date_text'] ?? \Carbon\Carbon::parse($endDate)->format('d/m/Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

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

        $formatNumeric = static function (string $column, ?float $value): string {
            if ($value === null) {
                return '';
            }

            $normalized = strtolower(str_replace([' ', '_'], '', $column));
            $isBatangColumn = str_contains($normalized, 'jlhbtg') || str_contains($normalized, 'jmlhbatang');
            $isDimensionColumn = in_array($normalized, ['tebal', 'lebar', 'panjang'], true);
            if (abs($value) < 0.0000001) {
                return '';
            }

            if ($isBatangColumn) {
                return number_format($value, 0, '.', ',');
            }

            if ($isDimensionColumn) {
                $text = number_format($value, 4, '.', '');
                return rtrim(rtrim($text, '0'), '.');
            }

            return number_format($value, 4, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Mutasi Racip Detail</h1>
    <p class="report-subtitle">Dari {{ $startText }} s/d {{ $endText }}</p>
    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Jenis</th>
                <th rowspan="2">Tebal</th>
                <th rowspan="2">Lebar</th>
                <th rowspan="2">Panjang</th>
                <th colspan="2">Sawal</th>
                <th colspan="4">Masuk</th>
                <th colspan="4">Keluar</th>
                <th colspan="2">Akhir</th>
            </tr>
            <tr>
                <th>Sawal</th>
                <th>JlhBtg</th>
                <th>Masuk</th>
                <th>JlhBtg</th>
                <th>Adj Outp</th>
                <th>JlhBtg</th>
                <th>Keluar</th>
                <th>JlhBtg</th>
                <th>Adj Inp</th>
                <th>JlhBtg</th>
                <th>Akhir</th>
                <th>JlhBtg</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number">{{ $formatNumeric('Tebal', $toFloat($row['Tebal'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('Lebar', $toFloat($row['Lebar'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('Panjang', $toFloat($row['Panjang'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('Sawal', $toFloat($row['Sawal'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('SawalJlhBtg', $toFloat($row['SawalJlhBtg'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('Masuk', $toFloat($row['Masuk'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('MskJlhBtg', $toFloat($row['MskJlhBtg'] ?? null)) }}</td>
                    <td class="number">
                        {{ $formatNumeric('AdjusmentOutput', $toFloat($row['AdjusmentOutput'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('AdjOutJlhBtg', $toFloat($row['AdjOutJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number">{{ $formatNumeric('Keluar', $toFloat($row['Keluar'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('KeluarJlhBtg', $toFloat($row['KeluarJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number">{{ $formatNumeric('AdjusmentInput', $toFloat($row['AdjusmentInput'] ?? null)) }}
                    </td>
                    <td class="number">{{ $formatNumeric('AdjInJlhBtg', $toFloat($row['AdjInJlhBtg'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('Akhir', $toFloat($row['Akhir'] ?? null)) }}</td>
                    <td class="number">{{ $formatNumeric('AkhirJlhBtg', $toFloat($row['AkhirJlhBtg'] ?? null)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            @if (!empty($rows))
                <tr class="totals-row">
                    <td colspan="5" class="center" style="font-weight: bold;">Total</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Sawal', $toFloat($totals['Sawal'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('SawalJlhBtg', $toFloat($totals['SawalJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Masuk', $toFloat($totals['Masuk'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('MskJlhBtg', $toFloat($totals['MskJlhBtg'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjusmentOutput', $toFloat($totals['AdjusmentOutput'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjOutJlhBtg', $toFloat($totals['AdjOutJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Keluar', $toFloat($totals['Keluar'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('KeluarJlhBtg', $toFloat($totals['KeluarJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjusmentInput', $toFloat($totals['AdjusmentInput'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AdjInJlhBtg', $toFloat($totals['AdjInJlhBtg'] ?? null)) }}
                    </td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('Akhir', $toFloat($totals['Akhir'] ?? null)) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $formatNumeric('AkhirJlhBtg', $toFloat($totals['AkhirJlhBtg'] ?? null)) }}
                    </td>
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
