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
            margin: 20mm 10mm 16mm 10mm;
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
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
            border: 1px solid #8f8f8f;
            padding: 3px 4px;
            vertical-align: middle;
            word-break: break-word;
            text-align: center;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
        }

        td.number-right {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $normalize = static fn(string $name): string => preg_replace('/[^a-z0-9]/', '', strtolower($name)) ?? '';

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
            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $findColumn = static function (array $columns, array $candidates) use ($normalize): ?string {
            $candidateSet = [];
            foreach ($candidates as $candidate) {
                $candidateSet[$normalize($candidate)] = true;
            }

            foreach ($columns as $column) {
                if (isset($candidateSet[$normalize((string) $column)])) {
                    return (string) $column;
                }
            }

            return null;
        };

        $formatDateDay = static function ($value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->format('d');
            } catch (\Throwable $exception) {
                return null;
            }
        };

        $formatNumber = static function (?float $value): string {
            if ($value === null || abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 2, '.', '');
        };

        $columns = array_keys($rowsData[0] ?? []);
        $supplierColumn = $findColumn($columns, ['NmSupplier', 'NamaSupplier', 'Supplier', 'Nama Supplier']);
        $dateColumn = $findColumn($columns, ['DateCreate', 'Tanggal', 'Tgl', 'Date']);

        $valueColumn = $findColumn($columns, ['Ton', 'Berat', 'TotalTon', 'Total', 'Qty', 'Jumlah']);
        if ($valueColumn === null) {
            foreach ($columns as $column) {
                $key = $normalize((string) $column);
                if (in_array($key, ['datecreate', 'tanggal', 'tgl', 'supplier', 'nmsupplier', 'namasupplier'], true)) {
                    continue;
                }

                foreach ($rowsData as $row) {
                    $num = $toFloat($row[$column] ?? null);
                    if ($num !== null) {
                        $valueColumn = (string) $column;
                        break 2;
                    }
                }
            }
        }

        $canPivot = $supplierColumn !== null && $dateColumn !== null && $valueColumn !== null;

        $pivotRows = [];
        $dayOrderMap = [];
        $grandByDay = [];
        $grandTotal = 0.0;

        if ($canPivot) {
            foreach ($rowsData as $row) {
                $supplier = trim((string) ($row[$supplierColumn] ?? ''));
                $supplier = $supplier !== '' ? $supplier : 'Tanpa Supplier';
                $day = $formatDateDay($row[$dateColumn] ?? null);
                if ($day === null) {
                    continue;
                }

                $value = $toFloat($row[$valueColumn] ?? null) ?? 0.0;

                if (!isset($pivotRows[$supplier])) {
                    $pivotRows[$supplier] = [
                        'supplier' => $supplier,
                        'days' => [],
                        'total' => 0.0,
                    ];
                }

                $pivotRows[$supplier]['days'][$day] = ($pivotRows[$supplier]['days'][$day] ?? 0.0) + $value;
                $pivotRows[$supplier]['total'] += $value;

                $grandByDay[$day] = ($grandByDay[$day] ?? 0.0) + $value;
                $grandTotal += $value;
                $dayOrderMap[(int) $day] = $day;
            }
        }

        ksort($dayOrderMap);
        $dayHeaders = array_values($dayOrderMap);
        $pivotRows = array_values($pivotRows);
        usort(
            $pivotRows,
            static fn(array $a, array $b): int => strcmp((string) $a['supplier'], (string) $b['supplier']),
        );
    @endphp

    <h1 class="report-title">Laporan Time Line Kayu Bulat - Harian (JTG/PLI)</h1>
    <p class="report-subtitle">
        Periode {{ \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d M Y') }} s/d
        {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d M Y') }}
    </p>

    @if ($canPivot && $dayHeaders !== [])
        <table>
            <thead>
                <tr class="headers-row">
                    <th style="width: 34px;">No</th>
                    <th style="text-align: left; width: 170px;">Nama Supplier</th>
                    @foreach ($dayHeaders as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pivotRows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td style="text-align: left;">{{ $row['supplier'] }}</td>
                        @foreach ($dayHeaders as $day)
                            <td class="number-right">{{ $formatNumber($row['days'][$day] ?? null) }}</td>
                        @endforeach
                        <td class="number-right" style="font-weight: bold">{{ $formatNumber($row['total'] ?? null) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($dayHeaders) + 3 }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if ($pivotRows !== [])
                    <tr class="totals-row">
                        <td colspan="2">Total </td>
                        @foreach ($dayHeaders as $day)
                            <td class="number-right">
                                {{ $formatNumber($grandByDay[$day] ?? null) }}</td>
                        @endforeach
                        <td class="number-right">{{ $formatNumber($grandTotal) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr class="headers-row">
                    <th style="width: 34px;">No</th>
                    @foreach ($columns as $column)
                        <th>{{ (string) $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rowsData as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        @foreach ($columns as $column)
                            <td class="center">{{ (string) ($row[$column] ?? '') }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
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
