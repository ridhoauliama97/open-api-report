<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .report-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }
.summary-lines {
            border-collapse: collapse;
        }
.summary-lines td { padding: 1px 2px; vertical-align: top; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];
        $summaryLines = is_array($data['summary_lines'] ?? null) ? $data['summary_lines'] : [];
        $summaryLines = array_map(static function (array $line): array {
            $line['text'] = str_replace(
                'ST Masuk KD - ST Hasil Racip',
                'ST Hasil Racip - ST Masuk KD',
                (string) ($line['text'] ?? ''),
            );

            return $line;
        }, $summaryLines);
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmt = static function ($value, bool $blankWhenZero = true): string {
            if (!is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 4, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Flow Produksi Per-Periode</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 7.46%;">Group Kayu</th>
                <th style="width: 7.46%;">Pembelian KB<br>(Ton)</th>
                <th style="width: 7.46%;">KB diRacip<br>(Ton)</th>
                <th style="width: 7.46%;">ST Hasil Racip<br>(Ton)</th>
                <th style="width: 7.46%;">ST Siap Vacuum Stick<br>(Ton)</th>
                <th style="width: 7.46%;">ST Hasil Racip - <br> ST Masuk KD<br>(Ton)</th>
                <th style="width: 7.46%;">ST Keluar KD<br>(Ton)</th>
                <th style="width: 7.46%;">ST Pakai di S4S<br>(Ton)</th>
                <th style="width: 7.46%;">WIP Bersih S4S<br>(m3)</th>
                <th style="width: 7.46%;">WIP Pakai di FJ<br>(m3)</th>
                <th style="width: 7.46%;">WIP Hasil FJ<br>(m3)</th>
                <th style="width: 7.46%;">WIP Pakai di Moulding<br>(m3)</th>
                <th style="width: 7.46%;">WIP hasil Moulding<br>(m3)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $row['No'] ?? $index + 1 }}</td>
                    <td>{{ $row['Group Kayu'] ?? '-' }}</td>
                    <td class="number">{{ $fmt($row['KBTonBeli'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['KBRacip'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['STRacipan'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['STVacuumStick'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['STKDIn'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['STKDOut'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['STm3Input'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['WIPBersihOutput'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['WIPFJInput'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['WIPFJOutput'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['WIPMouldingInput'] ?? null, true) }}</td>
                    <td class="number">{{ $fmt($row['WIPMouldingOutput'] ?? null, true) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="center">Total</td>
                <td class="number">{{ $fmt($totals['KBTonBeli'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['KBRacip'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['STRacipan'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['STVacuumStick'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['STKDIn'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['STKDOut'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['STm3Input'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['WIPBersihOutput'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['WIPFJInput'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['WIPFJOutput'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['WIPMouldingInput'] ?? null, true) }}</td>
                <td class="number">{{ $fmt($totals['WIPMouldingOutput'] ?? null, true) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-block">
        <div class="summary-title">Rangkuman/Rekap La. Flow Produksi :</div>
        <table class="summary-lines">
            <tbody>
                @foreach ($summaryLines as $line)
                    <tr>
                        <td class="summary-label">{{ $line['label'] ?? '' }}</td>
                        <td class="summary-sep">{{ ($line['label'] ?? '') !== '' ? ':' : '' }}</td>
                        <td>{{ $line['text'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    </body>

</html>