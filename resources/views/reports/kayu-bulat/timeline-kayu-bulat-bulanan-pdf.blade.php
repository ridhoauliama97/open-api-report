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
                $normalized = str_replace(',', '.');
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

        $formatMonthKey = static function ($value) use ($normalize, $endDate): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_numeric($value)) {
                $monthNum = (int) $value;
                if ($monthNum >= 1 && $monthNum <= 12) {
                    $year = \Carbon\Carbon::parse((string) $endDate)->format('Y');

                    return sprintf('%s-%02d', $year, $monthNum);
                }
            }

            $raw = trim((string) $value);
            if (preg_match('/^\d{1,2}$/', $raw) === 1) {
                $monthNum = (int) $raw;
                if ($monthNum >= 1 && $monthNum <= 12) {
                    $year = \Carbon\Carbon::parse((string) $endDate)->format('Y');

                    return sprintf('%s-%02d', $year, $monthNum);
                }
            }

            $monthAliases = [
                'jan' => 1,
                'januari' => 1,
                'feb' => 2,
                'februari' => 2,
                'mar' => 3,
                'maret' => 3,
                'apr' => 4,
                'april' => 4,
                'mei' => 5,
                'may' => 5,
                'jun' => 6,
                'juni' => 6,
                'jul' => 7,
                'juli' => 7,
                'agu' => 8,
                'agustus' => 8,
                'aug' => 8,
                'sep' => 9,
                'sept' => 9,
                'september' => 9,
                'okt' => 10,
                'oct' => 10,
                'oktober' => 10,
                'nov' => 11,
                'november' => 11,
                'des' => 12,
                'dec' => 12,
                'desember' => 12,
            ];
            $normalized = $normalize($raw);
            if (isset($monthAliases[$normalized])) {
                $year = \Carbon\Carbon::parse((string) $endDate)->format('Y');

                return sprintf('%s-%02d', $year, $monthAliases[$normalized]);
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->format('Y-m');
            } catch (\Throwable $exception) {
                return null;
            }
        };

        $formatMonthLabel = static function (string $monthKey): string {
            try {
                return \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->locale('id')->translatedFormat('M y');
            } catch (\Throwable $exception) {
                return $monthKey;
            }
        };

        $formatNumber = static function (?float $value): string {
            if ($value === null || abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 2, '.', ',');
        };

        $columns = array_keys($rowsData[0] ?? []);
        $supplierColumn = $findColumn($columns, ['NmSupplier', 'NamaSupplier', 'Supplier', 'Nama Supplier']);
        $dateColumn = $findColumn($columns, ['DateCreate', 'Tanggal', 'Tgl', 'Date', 'Bulan']);

        $valueColumn = $findColumn($columns, ['KBTon', 'Tonase', 'Ton', 'Berat', 'TotalTon', 'Total', 'Qty', 'Jumlah']);
        if ($valueColumn === null) {
            foreach ($columns as $column) {
                $key = $normalize((string) $column);
                if (
                    in_array(
                        $key,
                        [
                            'datecreate',
                            'tanggal',
                            'tgl',
                            'bulan',
                            'supplier',
                            'nmsupplier',
                            'namasupplier',
                            'tahun',
                            'year',
                            'ranking',
                            'rank',
                        ],
                        true,
                    )
                ) {
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
        $monthKeys = [];
        $grandByMonth = [];
        $grandTotal = 0.0;

        if ($canPivot) {
            foreach ($rowsData as $row) {
                $supplier = trim((string) ($row[$supplierColumn] ?? ''));
                $supplier = $supplier !== '' ? $supplier : 'Tanpa Supplier';
                $monthKey = $formatMonthKey($row[$dateColumn] ?? null);
                if ($monthKey === null) {
                    continue;
                }

                $value = $toFloat($row[$valueColumn] ?? null) ?? 0.0;

                if (!isset($pivotRows[$supplier])) {
                    $pivotRows[$supplier] = [
                        'supplier' => $supplier,
                        'months' => [],
                        'total' => 0.0,
                    ];
                }

                $pivotRows[$supplier]['months'][$monthKey] =
                    ($pivotRows[$supplier]['months'][$monthKey] ?? 0.0) + $value;
                $pivotRows[$supplier]['total'] += $value;

                $grandByMonth[$monthKey] = ($grandByMonth[$monthKey] ?? 0.0) + $value;
                $grandTotal += $value;
                $monthKeys[$monthKey] = true;
            }
        }

        $displayYear = \Carbon\Carbon::parse((string) $endDate)->format('Y');
        $monthHeaders = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthHeaders[] = sprintf('%s-%02d', $displayYear, $month);
        }
        $pivotRows = array_values($pivotRows);
        usort(
            $pivotRows,
            static fn(array $a, array $b): int => strcmp((string) $a['supplier'], (string) $b['supplier']),
        );
    @endphp

    <h1 class="report-title">Laporan Time Line Kayu Bulat - Bulanan (JTG/PLI)</h1>
    <p class="report-subtitle">
        Periode {{ \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d M Y') }} s/d
        {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d M Y') }}
    </p>

    @if ($canPivot && $monthHeaders !== [])
        <table>
            <thead>
                <tr class="headers-row">
                    <th rowspan="2" style="width: 34px;">No</th>
                    <th rowspan="2" style="text-align: center; width: 170px;">Nama Supplier</th>
                    <th colspan="{{ count($monthHeaders) }}">{{ $displayYear }}</th>
                    <th rowspan="2">Total</th>
                </tr>
                <tr class="headers-row">
                    @foreach ($monthHeaders as $monthKey)
                        <th>{{ (int) substr($monthKey, -2) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($pivotRows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td style="text-align: left;">{{ $row['supplier'] }}</td>
                        @foreach ($monthHeaders as $monthKey)
                            <td class="number-right">{{ $formatNumber($row['months'][$monthKey] ?? null) }}</td>
                        @endforeach
                        <td class="number-right" style="font-weight: bold">{{ $formatNumber($row['total'] ?? null) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($monthHeaders) + 4 }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if ($pivotRows !== [])
                    <tr class="totals-row">
                        <td colspan="2">Total :</td>
                        @foreach ($monthHeaders as $monthKey)
                            <td class="number-right">
                                {{ $formatNumber($grandByMonth[$monthKey] ?? null) }}</td>
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
