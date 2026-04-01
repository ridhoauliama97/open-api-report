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
            margin: 12mm 8mm 14mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
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
            page-break-inside: auto;
            margin-bottom: 8px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
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

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        .small-summary {
            width: 28%;
            margin-top: 4px;
            margin-bottom: 10px;
        }

        .small-summary td {
            padding: 3px 6px;
            border: 1px solid #000;
            font-weight: bold;
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
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $formatValue = static function ($value, string $format = 'decimal4'): string {
            if ($value === null || abs((float) $value) < 0.0000001) {
                return '';
            }

            return $format === 'integer0'
                ? number_format((float) $value, 0, '.', ',')
                : number_format((float) $value, 4, '.', ',');
        };

        $formatPlain = static function ($value, int $decimals = 2): string {
            if ($value === null) {
                return '';
            }

            return number_format((float) $value, $decimals, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Mutasi</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @forelse ($sections as $section)
        @php
            $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
            $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
            $totals = is_array($section['totals'] ?? null) ? $section['totals'] : [];
            $valueFormat = (string) ($section['value_format'] ?? 'decimal4');
            $inputTable = is_array($section['input_table'] ?? null) ? $section['input_table'] : null;
            $performance = is_array($section['performance'] ?? null) ? $section['performance'] : null;
        @endphp

        <div class="section-title">{{ $section['title'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    @foreach ($columns as $key => $label)
                        <th
                            @if ($key === 'No') style="width: 42px;" @elseif ($key === 'Jenis') style="width: 180px;" @endif>
                            {{ $label }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="{{ count($columns) }}"></td>
                </tr>
            </tfoot>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        @foreach ($columns as $key => $label)
                            <td
                                class="{{ in_array($key, ['No'], true) ? 'center' : (is_numeric($row[$key] ?? null) || isset($totals[$key]) ? 'number' : '') }}">
                                @if ($key === 'No')
                                    {{ $row[$key] ?? '' }}
                                @elseif ($key === 'Jenis')
                                    {{ $row[$key] ?? '' }}
                                @else
                                    {{ $formatValue($row[$key] ?? null, $valueFormat) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="empty-state">Tidak ada data untuk section ini.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    @foreach ($columns as $key => $label)
                        @if ($key === 'No')
                            <td class="center"></td>
                        @elseif ($key === 'Jenis')
                            <td class="center">Total :</td>
                        @else
                            <td class="number">{{ $formatValue($totals[$key] ?? null, $valueFormat) }}</td>
                        @endif
                    @endforeach
                </tr>
            </tbody>
        </table>

        @if ($inputTable)
            @php
                $inputColumns = is_array($inputTable['columns'] ?? null) ? $inputTable['columns'] : [];
                $inputRows = is_array($inputTable['rows'] ?? null) ? $inputTable['rows'] : [];
                $inputTotals = is_array($inputTable['totals'] ?? null) ? $inputTable['totals'] : [];
            @endphp
            <div class="section-title">{{ $inputTable['title'] ?? '-' }}</div>
            <table class="report-table" style="width: 62%;">
                <thead>
                    <tr>
                        @foreach ($inputColumns as $key => $label)
                            <th
                                @if ($key === 'No') style="width: 42px;" @elseif ($key === 'Jenis') style="width: 190px;" @endif>
                                {{ $label }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="{{ count($inputColumns) }}"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($inputRows as $index => $row)
                        <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @foreach ($inputColumns as $key => $label)
                                <td
                                    class="{{ in_array($key, ['No'], true) ? 'center' : ($key === 'Jenis' ? '' : 'number') }}">
                                    @if ($key === 'No' || $key === 'Jenis')
                                        {{ $row[$key] ?? '' }}
                                    @else
                                        {{ $formatValue($row[$key] ?? null, 'decimal4') }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($inputColumns) }}" class="empty-state">Tidak ada data input produksi.
                            </td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        @foreach ($inputColumns as $key => $label)
                            @if ($key === 'No')
                                <td class="center"></td>
                            @elseif ($key === 'Jenis')
                                <td class="center">Total :</td>
                            @else
                                <td class="number">{{ $formatValue($inputTotals[$key] ?? null, 'decimal4') }}</td>
                            @endif
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endif

        @if ($performance)
            <table class="small-summary">
                <tbody>
                    <tr>
                        <td class="center">{{ $performance['left_label'] ?? 'Input' }}</td>
                        <td class="center">{{ $performance['right_label'] ?? 'Output' }}</td>
                        <td class="center">Rendemen</td>
                    </tr>
                    <tr>
                        <td class="number">{{ $formatPlain($performance['input'] ?? null, 2) }}</td>
                        <td class="number">{{ $formatPlain($performance['output'] ?? null, 2) }}</td>
                        <td class="number">
                            {{ $performance['rendemen'] !== null ? number_format((float) $performance['rendemen'], 2, '.', ',') . '%' : '' }}
                        </td>
                    </tr>
                </tbody>
            </table>
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

    @include('reports.partials.pdf-footer-table')
</body>

</html>
