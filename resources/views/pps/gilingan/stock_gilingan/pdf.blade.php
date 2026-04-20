<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 14mm 10mm 14mm 10mm;
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

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 10px;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .center {
            text-align: center;
        }

        .number {
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

        .total-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: #000 solid 1px;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $reportDateText = \Carbon\Carbon::parse($endDate ?? now()->toDateString())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $headerLabels = [
            'DateCreate' => 'Tanggal',
            'NoGilingan' => 'No Gilingan',
            'NamaGilingan' => 'Nama Gilingan',
            'NamaWarehouse' => 'Nama Warehouse',
            'Blok' => 'Lokasi',
            'Berat' => 'Berat (Kg)',
        ];
        $centerColumns = ['DateCreate', 'NoGilingan', 'NamaWarehouse', 'Blok'];
        $preferredOrder = ['DateCreate', 'NoGilingan', 'NamaGilingan', 'NamaWarehouse', 'Blok', 'Berat'];
        $columns = array_keys($rowsData[0] ?? []);
        $visibleColumns =
            $columns !== []
                ? array_values(
                    array_filter($preferredOrder, static fn(string $column): bool => in_array($column, $columns, true)),
                )
                : $preferredOrder;
        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (!is_string($value)) {
                return null;
            }
            $normalized = trim(str_replace([' ', ','], ['', '.'], $value));

            return is_numeric($normalized) ? (float) $normalized : null;
        };
        $numericColumns = array_values(array_intersect(['Berat'], $visibleColumns));
        $firstNumericColumnIndex = null;
        foreach ($visibleColumns as $index => $column) {
            if (in_array($column, $numericColumns, true)) {
                $firstNumericColumnIndex = $index;
                break;
            }
        }
        $totals = [];
        foreach ($numericColumns as $column) {
            $totals[$column] = 0.0;
        }
        $displayValue = static function (array $row, string $column) {
            if ($column === 'DateCreate') {
                $dateValue = $row[$column] ?? null;

                if ($dateValue !== null && $dateValue !== '') {
                    try {
                        return \Carbon\Carbon::parse((string) $dateValue)->locale('id')->translatedFormat('d-M-y');
                    } catch (\Throwable) {
                        return (string) $dateValue;
                    }
                }

                return '';
            }

            if ($column === 'Blok') {
                $blok = trim((string) ($row['Blok'] ?? ''));
                $idLokasi = trim((string) ($row['IdLokasi'] ?? ''));

                return trim(
                    $blok !== '' && $idLokasi !== '' ? "{$blok} - {$idLokasi}" : ($blok !== '' ? $blok : $idLokasi),
                );
            }

            return $row[$column] ?? '';
        };
    @endphp

    <h1 class="report-title">Laporan Stock Gilingan</h1>
    <p class="report-subtitle">Periode : {{ $reportDateText }}</p>

    <h3>Gudang : {{ $warehouseName }}</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                @foreach ($visibleColumns as $column)
                    <th>{{ $headerLabels[$column] ?? $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    @foreach ($visibleColumns as $column)
                        @php
                            $rawValue = $displayValue($row, $column);
                            $numericValue = $toFloat($rawValue);
                            $cellClass = in_array($column, $centerColumns, true) ? 'center' : '';
                            if ($numericValue !== null && in_array($column, $numericColumns, true)) {
                                $totals[$column] += $numericValue;
                            }
                        @endphp
                        @if ($numericValue !== null && in_array($column, $numericColumns, true))
                            <td class="number">{{ number_format($numericValue, 2, '.', ',') }}</td>
                        @else
                            <td class="{{ $cellClass }}">{{ (string) $rawValue }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr class="row-odd">
                    <td colspan="{{ count($visibleColumns) + 1 }}" class="center"
                        style="font-weight: bold; font-size: 11px; font-style: italic;">Tidak ada data.</td>
                </tr>
            @endforelse
            @if (!empty($numericColumns))
                <tr class="total-row">
                    <td colspan="{{ ($firstNumericColumnIndex ?? 0) + 1 }}" class="center">Total</td>
                    @foreach (array_slice($visibleColumns, $firstNumericColumnIndex ?? count($visibleColumns)) as $column)
                        @if (in_array($column, $numericColumns, true))
                            <td class="number">{{ number_format($totals[$column], 2, '.', ',') }}</td>
                        @else
                            <td>&nbsp;</td>
                        @endif
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
