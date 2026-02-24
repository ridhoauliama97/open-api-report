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
            margin: 24mm 12mm 20mm 12mm;
            footer: html_reportFooter;
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
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 10px 0;
            font-size: 10px;
            color: #636466;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: 700;
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
            border: 1px solid #9ca3af;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #ffffff;
            color: #000;
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
            font-family: "Calibry", "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            background: #dde4f2;
        }

        .totals-row td.blank {
            background: transparent;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $subRowsData =
            isset($subRows) && is_iterable($subRows)
                ? (is_array($subRows)
                    ? $subRows
                    : collect($subRows)->values()->all())
                : [];

        $mainColumns = array_keys($rowsData[0] ?? []);
        $subColumns = array_keys($subRowsData[0] ?? []);

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim($value);
            if ($normalized === '') {
                return null;
            }

            $normalized = str_replace(' ', '', $normalized);

            // Handle "1,234.56" vs "1.234,56" vs "19,627" (thousand separator).
            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $normalized) === 1) {
                    $normalized = str_replace(',', '', $normalized);
                } else {
                    $normalized = str_replace(',', '.', $normalized);
                }
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $isNumericColumn = static function (string $column, array $rows) use ($toFloat): bool {
            foreach ($rows as $row) {
                if (!array_key_exists($column, $row)) {
                    continue;
                }

                if ($toFloat($row[$column]) !== null) {
                    return true;
                }
            }

            return false;
        };

        $fmt = static function ($value, bool $blankWhenZero = true): string {
            if (!is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 0, '.', ',');
        };

        $mainNumericColumns = [];
        $mainTotals = [];
        foreach ($mainColumns as $column) {
            $isNumeric = $isNumericColumn($column, $rowsData);
            $mainNumericColumns[$column] = $isNumeric;
            if ($isNumeric) {
                $mainTotals[$column] = 0.0;
            }
        }

        $subNumericColumns = [];
        $subTotals = [];
        foreach ($subColumns as $column) {
            $isNumeric = $isNumericColumn($column, $subRowsData);
            $subNumericColumns[$column] = $isNumeric;
            if ($isNumeric) {
                $subTotals[$column] = 0.0;
            }
        }
    @endphp

    <h1 class="report-title">Laporan Mutasi Kayu Bulat - Timbang KG</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                @foreach ($mainColumns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    @foreach ($mainColumns as $column)
                        @php
                            $value = $row[$column] ?? null;
                            $floatValue = $toFloat($value);
                            $isNumeric = $mainNumericColumns[$column] ?? false;
                            if ($isNumeric && $floatValue !== null) {
                                $mainTotals[$column] += $floatValue;
                            }
                        @endphp
                        @if ($isNumeric)
                            <td class="number">{{ $fmt($floatValue ?? 0.0, true) }}</td>
                        @else
                            <td class="label">{{ (string) $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($mainColumns) + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rowsData !== [])
                <tr class="totals-row">
                    <td colspan="2" class="blank" style="text-align:center">Total</td>
                    @for ($index = 1; $index < count($mainColumns); $index++)
                        @php
                            $column = $mainColumns[$index];
                        @endphp
                        @if (($mainNumericColumns[$column] ?? false) === true)
                            <td class="number">{{ $fmt($mainTotals[$column] ?? 0.0, true) }}</td>
                        @else
                            <td></td>
                        @endif
                    @endfor
                </tr>
            @endif
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div class="section-title">Sub Report Mutasi Kayu Bulat - Timbang KG</div>
        <table style="width: 78%;">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    @foreach ($subColumns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($subRowsData as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        @foreach ($subColumns as $column)
                            @php
                                $value = $row[$column] ?? null;
                                $floatValue = $toFloat($value);
                                $isNumeric = $subNumericColumns[$column] ?? false;
                                if ($isNumeric && $floatValue !== null) {
                                    $subTotals[$column] += $floatValue;
                                }
                            @endphp
                            @if ($isNumeric)
                                <td class="number">{{ $fmt($floatValue ?? 0.0, true) }}</td>
                            @else
                                <td class="label">{{ (string) $value }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="2" class="blank" style="text-align:center">Total</td>
                    @for ($index = 1; $index < count($subColumns); $index++)
                        @php
                            $column = $subColumns[$index];
                        @endphp
                        @if (($subNumericColumns[$column] ?? false) === true)
                            <td class="number">{{ $fmt($subTotals[$column] ?? 0.0, true) }}</td>
                        @else
                            <td></td>
                        @endif
                    @endfor
                </tr>
            </tbody>
        </table>
    @endif

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
