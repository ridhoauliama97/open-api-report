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
            margin: 20mm 10mm 20mm 10mm;
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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
            table-layout: fixed;
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
            padding: 1px 2px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
        }

        /* Hilangkan garis horizontal antar baris data. */
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

        .empty-row td {
            background: #c9d1df;
            font-weight: bold;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .date-cell {
            white-space: normal;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
        }


        tfoot {
            display: table-footer-group;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $grand = is_array($data['grand_total'] ?? null) ? $data['grand_total'] : null;
        $columns = [
            'Tanggal',
            'Air',
            'Borax (kg)',
            'Rasio Borax (kg/ton)',
            'Boric (kg)',
            'Rasio Boric (kg/ton)',
            'Obat (%)',
            'Borax / Boric',
            'Kaporit (Kg)',
            'Persen (%)',
            'ST (Ton)',
            'Jabon',
            'Jabon TG',
            'Pulai',
            'Rambung',
            'Charge',
            'Menit',
            'Charge (Menit)',
            'ST Ton/Charge',
        ];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmtInt = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 0, '.', ',');
        $fmt2 = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 2, '.', ',');
        $fmt4 = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');

        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return $key;
            }
        };

        $toFloat = static function (mixed $val): ?float {
            if ($val === null) {
                return null;
            }
            if (is_int($val) || is_float($val)) {
                return (float) $val;
            }
            if (!is_string($val)) {
                return null;
            }
            $t = trim($val);
            if ($t === '') {
                return null;
            }
            // Accept "1,23" or "1.23" or "1.234,56" or "1,234.56".
            if (str_contains($t, ',') && str_contains($t, '.')) {
                $lastComma = strrpos($t, ',');
                $lastDot = strrpos($t, '.');
                if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                    // 1.234,56 => thousands '.', decimal ','
                    $t = str_replace('.', ',', $t);
                    $t = str_replace('.', ',', $t);
                } else {
                    // 1,234.56 => thousands ',', decimal '.'
                    $t = str_replace(',', '', $t);
                }
            } elseif (str_contains($t, ',')) {
                $t = str_replace(',', '.', $t);
            }
            if (!is_numeric($t)) {
                return null;
            }
            return (float) $t;
        };

        $formatCell = static function (string $col, mixed $val) use (
            $fmtInt,
            $fmt2,
            $fmt4,
            $dateLabel,
            $toFloat,
        ): string {
            if ($col === 'Tanggal') {
                $t = trim((string) ($val ?? ''));
                return $t === 'Total' ? $t : $dateLabel($t);
            }

            $numCols0 = ['Air', 'Borax (kg)', 'Boric (kg)', 'Kaporit (Kg)', 'Charge', 'Menit', 'Charge (Menit)'];
            $numCols2 = ['Rasio Borax (kg/ton)', 'Rasio Boric (kg/ton)', 'Obat (%)', 'Borax / Boric', 'Persen (%)'];
            $numCols4 = ['ST (Ton)', 'Jabon', 'Jabon TG', 'Pulai', 'Rambung', 'ST Ton/Charge'];

            $f = null;
            if (in_array($col, $numCols0, true)) {
                $f = $fmtInt;
            } elseif (in_array($col, $numCols2, true)) {
                $f = $fmt2;
            } elseif (in_array($col, $numCols4, true)) {
                $f = $fmt4;
            }

            if ($f !== null) {
                $v = $toFloat($val);
                return $v === null ? '' : $f($v);
            }

            return (string) ($val ?? '');
        };
    @endphp

    <h1 class="report-title">Laporan Pemakaian Obat Vacuum</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($rows as $row)
                @php $rowIndex++; @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    @foreach ($columns as $col)
                        @php
                            $val = $row[$col] ?? '';
                            $isNumber = $col !== 'Tanggal';
                        @endphp
                        <td class="{{ $isNumber ? 'number' : 'date-cell' }}">{{ $formatCell($col, $val) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ max(1, count($columns)) }}" class="center">Tidak Ada Data</td>
                </tr>
            @endforelse

            @if ($grand)
                <tr class="totals-row">
                    @foreach ($columns as $col)
                        @php
                            $val = $grand[$col] ?? '';
                            $isNumber = $col !== 'Tanggal';
                        @endphp
                        <td class="{{ $isNumber ? 'number' : 'date-cell' }}">{{ $formatCell($col, $val) }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
