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

        .col-no {
            width: 3%;
        }

        .col-jenis {
            width: 17%;
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

        $formatColumnHeader = static function (string $column): string {
            $trimmed = trim($column);
            if ($trimmed === '') {
                return '';
            }

            $normalizedForCheck = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $trimmed));
            if ($normalizedForCheck === 'JENIS') {
                return 'Jenis';
            }

            $label = preg_replace('/[_-]+/', ' ', $trimmed);
            $label = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', (string) $label);
            $label = preg_replace('/\s+/', ' ', (string) $label);

            return trim((string) $label);
        };

        $isJenisColumn = static function (string $column): bool {
            $normalized = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $column));

            return str_starts_with($normalized, 'JENIS');
        };

        $isSaldoAkhirColumn = static function (string $column): bool {
            $normalized = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $column));

            return in_array($normalized, ['SALDOAKHIR', 'AKHIR'], true);
        };

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

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

        $fmt = static function ($value, bool $blankWhenZero = true) use ($toFloat): string {
            $float = $toFloat($value);
            if ($float === null) {
                return '';
            }

            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 2, '.', ',');
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

    <h1 class="report-title">Laporan Mutasi Kayu Bulat (Gantung) - Timbang KG</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th class="col-no">No</th>
                @foreach ($mainColumns as $column)
                    <th class="{{ $isJenisColumn($column) ? 'col-jenis' : 'col-uniform' }}">
                        {{ $formatColumnHeader($column) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center col-no data-cell">{{ $loop->iteration }}</td>
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
                            <td class="number {{ $isJenisColumn($column) ? 'col-jenis' : 'col-uniform' }} data-cell" @if ($isSaldoAkhirColumn($column)) style="font-weight: bold;" @endif>
                                {{ $fmt($value, true) }}
                            </td>
                        @else
                            <td class="label {{ $isJenisColumn($column) ? 'col-jenis' : 'col-uniform' }} data-cell">
                                {{ (string) $value }}
                            </td>
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
        <div class="section-title">Sub Report Mutasi Kayu Bulat (Gantung) - Timbang KG</div>
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th class="col-no">No</th>
                    @foreach ($subColumns as $column)
                        <th class="{{ $isJenisColumn($column) ? 'col-jenis' : 'col-uniform' }}">
                            {{ $formatColumnHeader($column) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($subRowsData as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center col-no data-cell">{{ $loop->iteration }}</td>
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
                                <td class="number {{ $isJenisColumn($column) ? 'col-jenis' : 'col-uniform' }} data-cell" @if ($isSaldoAkhirColumn($column)) style="font-weight: bold;" @endif>
                                    {{ $fmt($value, true) }}
                                </td>
                            @else
                                <td class="label {{ $isJenisColumn($column) ? 'col-jenis' : 'col-uniform' }} data-cell">
                                    {{ (string) $value }}
                                </td>
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
</body>

</html>