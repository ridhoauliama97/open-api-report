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
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
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
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .stats-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $displayColumns = is_array($data['summary']['display_columns'] ?? null)
            ? $data['summary']['display_columns']
            : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');
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
