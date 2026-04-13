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

        .report-meta {
            margin: 0 0 8px 0;
            font-size: 11px;
            color: #000;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
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
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
        }

        /* Hilangkan garis horizontal antar baris data (hanya untuk baris data). */
        tbody tr.data-row td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
        }

        .section-line td {
            border: 0 !important;
            border-top: 1px solid #000 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: transparent !important;
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
        $rowsKeluar = is_array($data['rows_keluar'] ?? null) ? $data['rows_keluar'] : [];
        $rowsMasih = is_array($data['rows_masih'] ?? null) ? $data['rows_masih'] : [];
        $groupCols = is_array($data['group_columns'] ?? null) ? $data['group_columns'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt4 = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmt1 = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 1, '.', ',');

        $dateLabel = static function (string $key): string {
            $t = trim($key);
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($t)->format('d-M-y');
            } catch (\Throwable $exception) {
                return $t;
            }
        };

        $renderRows = function (array $rows, int &$rowIndex) use ($groupCols, $fmt4, $fmt1, $dateLabel) {
            foreach ($rows as $row) {
                $rowIndex++;
                $tanggalOut = trim((string) ($row['Tanggal (Out)'] ?? ''));
                $tanggalIn = trim((string) ($row['Tanggal (In)'] ?? ''));
                $noKd = (string) ($row['No.KD'] ?? '');
                $hari = (int) ($row['Hari'] ?? 0);
                $ave = (float) ($row['Ave Tebal'] ?? 0.0);
                $total = (float) ($row['Total'] ?? 0.0);

                echo '<tr class="data-row ' . ($rowIndex % 2 === 1 ? 'row-odd' : 'row-even') . '">';
                echo '<td class="center">' . e($dateLabel($tanggalOut)) . '</td>';
                echo '<td class="center">' . e($dateLabel($tanggalIn)) . '</td>';
                echo '<td class="center">' . e($noKd) . '</td>';
                echo '<td class="center" style="font-weight:bold;">' . e($hari === 0 ? '' : (string) $hari) . '</td>';
                echo '<td class="number">' . e($fmt1($ave)) . '</td>';

                foreach ($groupCols as $col) {
                    $v = (float) ($row[$col] ?? 0.0);
                    echo '<td class="number">' . e($fmt4($v)) . '</td>';
                }

                echo '<td class="number" style="font-weight:bold;">' . e($fmt4($total)) . '</td>';
                echo '</tr>';
            }
        };
    @endphp

    <h1 class="report-title">Laporan KD (Keluar - Masuk)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>
    @if (!empty($noKd))
        <p class="report-meta">Filter No KD : <strong>{{ $noKd }}</strong></p>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Tanggal (Out)</th>
                <th style="width: 70px;">Tanggal (In)</th>
                <th style="width: 46px;">No.KD</th>
                <th style="width: 42px;">Hari</th>
                <th style="width: 56px;">Ave Tebal</th>
                @foreach ($groupCols as $col)
                    <th>{{ $col }}</th>
                @endforeach
                <th style="width: 62px;">Total</th>
            </tr>
        </thead>

        <tbody>
            @php $rowIndex = 0; @endphp

            @php $renderRows($rowsKeluar, $rowIndex); @endphp

            @if ($rowsKeluar !== [])
                <tr class="totals-row">
                    <td colspan="5" class="center" style="font-weight: bold;">Total</td>
                    @foreach ($groupCols as $col)
                        @php $v = (float) (($totals['keluar'][$col] ?? 0.0)); @endphp
                        <td class="number" style="font-weight: bold;">{{ number_format($v, 4, '.', ',') }}</td>
                    @endforeach
                    @php $vTotal = (float) (($totals['keluar']['Total'] ?? 0.0)); @endphp
                    <td class="number" style="font-weight: bold;">{{ number_format($vTotal, 4, '.', ',') }}</td>
                </tr>
            @endif

            @if ($rowsMasih !== [])
                <tr class="section-line">
                    <td colspan="{{ 6 + count($groupCols) }}"></td>
                </tr>
                @php $renderRows($rowsMasih, $rowIndex); @endphp
                <tr class="totals-row">
                    <td colspan="5" class="center" style="font-weight: bold;">Total</td>
                    @foreach ($groupCols as $col)
                        @php $v = (float) (($totals['masih'][$col] ?? 0.0)); @endphp
                        <td class="number" style="font-weight: bold;">{{ number_format($v, 4, '.', ',') }}</td>
                    @endforeach
                    @php $vTotal = (float) (($totals['masih']['Total'] ?? 0.0)); @endphp
                    <td class="number" style="font-weight: bold;">{{ number_format($vTotal, 4, '.', ',') }}</td>
                </tr>
            @endif

            @if ($rowsKeluar !== [] || $rowsMasih !== [])
                <tr class="totals-row">
                    <td colspan="5" class="center" style="font-weight: bold;">Total</td>
                    @foreach ($groupCols as $col)
                        @php $v = (float) (($totals['grand'][$col] ?? 0.0)); @endphp
                        <td class="number" style="font-weight: bold;">{{ number_format($v, 4, '.', ',') }}</td>
                    @endforeach
                    @php $vTotal = (float) (($totals['grand']['Total'] ?? 0.0)); @endphp
                    <td class="number" style="font-weight: bold;">{{ number_format($vTotal, 4, '.', ',') }}</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="{{ 6 + count($groupCols) }}"></td>
            </tr>
        </tfoot>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
