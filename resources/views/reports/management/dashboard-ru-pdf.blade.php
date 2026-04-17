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
            margin: 12mm 6mm 12mm 6mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 7px;
            line-height: 1.1;
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
            margin: 2px 0 12px 0;
            font-size: 10px;
            color: #636466;
        }

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
            page-break-inside: auto;
            font-size: 10px;
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
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
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

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .summary-table {
            width: 38%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-top: 6px;
        }

        .summary-table td {
            border: 0 !important;
            padding: 1px 4px;
            background: #fff !important;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $columnGroups = is_array($data['column_groups'] ?? null) ? $data['column_groups'] : [];
        $subColumns = is_array($data['sub_columns'] ?? null) ? $data['sub_columns'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $summaryLines = is_array($data['summary_lines'] ?? null) ? $data['summary_lines'] : [];
        $periodLabel = (string) ($data['period_label'] ?? '');
        $displayDate = \Carbon\Carbon::parse($reportDate)->locale('id')->translatedFormat('d-M-y');
    @endphp

    <h1 class="report-title">Laporan Dashboard RU {{ $periodLabel }}</h1>
    <div class="report-subtitle">Per tanggal {{ $displayDate }}</div>

    <table class="report-table">
        <colgroup>
            <col style="width: 28px;">
            @foreach ($subColumns as $column)
                <col style="width: 48px;">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                @foreach ($columnGroups as $group)
                    <th colspan="{{ $group['span'] ?? 1 }}">{!! $group['label'] ?? '' !!}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($subColumns as $column)
                    <th>{{ $column['label'] ?? '' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr
                    class="{{ !empty($row['is_footer']) ? 'total-row' : (($index + 1) % 2 === 1 ? 'row-odd' : 'row-even') }}">
                    <td class="center">{{ $row['label'] ?? '' }}</td>
                    @foreach ($subColumns as $column)
                        <td class="number">{{ $row['cells'][$column['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($subColumns) + 1 }}" class="empty-state">Tidak ada data untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($summaryLines !== [])
        <div class="section-title">Rangkuman</div>
        <table class="summary-table">
            <tbody>
                @foreach ($summaryLines as $line)
                    <tr>
                        <td style="width: 110px;">{{ $line['label'] ?? '' }}</td>
                        <td style="width: 14px;">{{ ($line['label'] ?? '') !== '' ? ':' : '' }}</td>
                        <td>{{ $line['value'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
