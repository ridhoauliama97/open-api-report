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
        .report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .report-table-section, .small-summary {
            border-collapse: collapse;
        }
        .report-table-section td, .small-summary td {
            padding: 1px 2px;
            vertical-align: top;
        }
        .report-table-section {
            width: calc(100% - 2px);
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .small-summary {
            width: 28%;
            margin-top: 4px;
            margin-bottom: 10px;
            margin-left: 8px;
        }
        .small-summary td {
            padding: 3px 6px;
            border: 1px solid #000;
            font-weight: bold;
            background: #fff !important;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');

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

        $formatHeaderLabel = static function (string $key, string $label): string {
            return match ($key) {
                'AdjustmentPlus' => 'Adjust<br>(+)',
                'AdjustmentMinus' => 'Adjust<br>(-)',
                'BongkarSusunPlus' => 'B. Susun<br>(+)',
                'BongkarSusunMinus' => 'B. Susun<br>(-)',
                default => e($label),
            };
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
                        <th @if ($key === 'No') style="width: 42px;" @elseif ($key === 'Jenis') style="width: 180px;" @endif>
                            {{ $formatHeaderLabel((string) $key, (string) $label) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
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
                    @php
                        $columnKeys = array_keys($columns);
                    @endphp
                    @foreach ($columnKeys as $index => $key)
                        @if ($index === 0)
                            <td class="center" colspan="2">Total :</td>
                        @elseif ($index === 1)
                            @continue
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
            <div class="section-subtitle">{{ $inputTable['title'] ?? '-' }}</div>
            <table class="report-table-section" style="width: 62%;">
                <thead>
                    <tr>
                        @foreach ($inputColumns as $key => $label)
                            <th @if ($key === 'No') style="width: 42px;" @elseif ($key === 'Jenis') style="width: 190px;" @endif>
                                {{ $formatHeaderLabel((string) $key, (string) $label) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inputRows as $index => $row)
                        <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @foreach ($inputColumns as $key => $label)
                                <td class="{{ in_array($key, ['No'], true) ? 'center' : ($key === 'Jenis' ? '' : 'number') }}">
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
                        @php
                            $inputColumnKeys = array_keys($inputColumns);
                        @endphp
                        @foreach ($inputColumnKeys as $index => $key)
                            @if ($index === 0)
                                <td class="center" colspan="2">Total :</td>
                            @elseif ($index === 1)
                                @continue
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

    </body>

</html>
