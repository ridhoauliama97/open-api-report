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
        .report-table, .stats-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td, .stats-table th, .stats-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .stats-table th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $displayColumns = is_array($data['summary']['display_columns'] ?? null)
            ? $data['summary']['display_columns']
            : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmt = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtKg = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $isKgColumn = static fn(string $key): bool => $key === 'KBKG';
    @endphp

    <h1 class="report-title">Laporan Discrepancy Rekap Mutasi</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @forelse ($sections as $section)
        <div class="section-title">{{ $section['title'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 46px;"></th>
                    @foreach ($displayColumns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if (($section['key'] ?? '') === 'stock_ber_spk')
                    @php $row = $section['single_row'] ?? null; @endphp
                    @if ($row)
                        <tr class="total-row">
                            <td class="center">Total</td>
                            @foreach ($displayColumns as $key => $label)
                                <td class="number">
                                    {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                                </td>
                            @endforeach
                        </tr>
                    @else
                        <tr>
                            <td colspan="{{ count($displayColumns) + 1 }}" class="empty-state">Tidak ada data untuk
                                section ini.</td>
                        </tr>
                    @endif
                @elseif (($section['key'] ?? '') === 'stock_total')
                    @php $rows = is_array($section['rows'] ?? null) ? $section['rows'] : []; @endphp
                    @php $totalRow = $rows[0] ?? null; @endphp
                    @if ($totalRow)
                        <tr class="total-row">
                            <td class="center">{{ $totalRow['label'] ?? '' }}</td>
                            @foreach ($displayColumns as $key => $label)
                                <td class="number">
                                    {{ $isKgColumn($key) ? $fmtKg($totalRow['metrics'][$key] ?? null) : $fmt($totalRow['metrics'][$key] ?? null) }}
                                </td>
                            @endforeach
                        </tr>
                    @else
                        <tr>
                            <td colspan="{{ count($displayColumns) + 1 }}" class="empty-state">Tidak ada data untuk
                                section ini.</td>
                        </tr>
                    @endif
                @else
                    @php $rows = is_array($section['rows'] ?? null) ? $section['rows'] : []; @endphp
                    @forelse ($rows as $index => $row)
                        <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $row['day'] ?? '' }}</td>
                            @foreach ($displayColumns as $key => $label)
                                <td class="number">
                                    {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($displayColumns) + 1 }}" class="empty-state">Tidak ada data untuk
                                section ini.</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>

        @if (($section['key'] ?? '') === 'stock_total')
            @php $statRows = is_array($section['stats_rows'] ?? null) ? $section['stats_rows'] : []; @endphp
            @if ($statRows !== [])
                <div class="section-title">Statistik Stock</div>
                <table class="report-table stats-table">
                    <thead>
                        <tr>
                            <th style="width: 46px;"></th>
                            @foreach ($displayColumns as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($statRows as $index => $row)
                            <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                <td class="center">{{ $row['label'] ?? '' }}</td>
                                @foreach ($displayColumns as $key => $label)
                                    <td class="number">
                                        {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk periode ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    </body>

</html>
