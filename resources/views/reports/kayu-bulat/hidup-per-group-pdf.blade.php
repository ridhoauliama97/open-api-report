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
            margin: 0;
            padding: 0;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
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

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $rowsData = is_iterable($rows ?? null) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
    @endphp

    <h1 class="report-title">Laporan Saldo Hidup Kayu Bulat Per Group</h1>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 44px;">No</th>
                <th>Group</th>
                <th style="width: 120px;">Ton</th>
                <th style="width: 120px;">Rasio (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell" style="width: 44px;">{{ $loop->iteration }}</td>
                    <td class="data-cell">{{ (string) ($row['Group'] ?? '') }}</td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ number_format((float) ($row['Ton'] ?? 0), 4, '.', ',') }}
                    </td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ number_format((float) ($row['Rasio'] ?? 0), 2, '.', ',') }} %
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="4">Tidak ada data.</td>
                </tr>
            @endforelse
            @if ($rowsData !== [])
                <tr class="totals-row">
                    <td class="center" colspan="2" style="font-size: 11px; font-weight: bold;">Total</td>
                    <td class="number" style="font-weight: bold;">
                        {{ number_format((float) ($summaryData['total_ton'] ?? 0), 4, '.', ',') }}
                    </td>
                    <td class="number" style="font-weight: bold;">100.00 %</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
