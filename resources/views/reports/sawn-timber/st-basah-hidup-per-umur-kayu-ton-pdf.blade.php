<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
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
            width: 100%;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : null;

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $eps = 0.0000001;
        $fmt = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');

        $periodCols = ['<= 2 Minggu', '2 - 4 Minggu', '4 - 6 Minggu', '6 - 8 Minggu', '> 8 Minggu', 'Total'];
    @endphp

    <h1 class="report-title">Laporan ST Basah Hidup Per-Umur Kayu (Ton)</h1>
    <p class="report-subtitle"></p>

    <table>
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th style="width: 100px;">Group</th>
                <th style="width: 70px;">&le; 2 Minggu</th>
                <th style="width: 70px;">2 - 4 Minggu</th>
                <th style="width: 70px;">4 - 6 Minggu</th>
                <th style="width: 70px;">6 - 8 Minggu</th>
                <th style="width: 70px;">&gt; 8 Minggu</th>
                <th class="total-column" style="width: 70px;">Total</th>
            </tr>
        </thead>

        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($rows as $row)
                @php $rowIndex++; @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td>{{ (string) ($row['Group'] ?? '') }}</td>
                    @foreach ($periodCols as $col)
                        @php $val = (float) ($row[$col] ?? 0.0); @endphp
                        <td class="number {{ $col === 'Total' ? 'total-column' : '' }}">{{ $fmt($val) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [] && is_array($totals))
                <tr class="totals-row">
                    <td class="center" colspan="2" style="font-weight: bold;">Total</td>
                    @foreach ($periodCols as $col)
                        @php $val = (float) ($totals[$col] ?? 0.0); @endphp
                        <td class="number" style="font-weight: bold;">{{ $fmtTotal($val) }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    </body>

</html>
