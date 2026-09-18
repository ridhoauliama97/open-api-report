<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style')
    <style>
        table {
            width: 100%;
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
        }
    </style>


</head>

<body>
    @php
        $rowsData = is_iterable($rows ?? null) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
    @endphp

    <h1 class="report-title" style="margin-bottom: 20px;">Laporan Saldo Hidup Kayu Bulat Per Group</h1>

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