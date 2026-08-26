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
            margin: 0;
            padding: 0;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
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
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        .totals-row td.blank {
            text-align: center;
        }

        .summary-block {
            margin-top: 8px;
        }

        .summary-list {
            margin: 4px 0 0 18px;
            padding: 0;
        }

        .summary-list li {
            margin: 0 0 2px 0;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        usort($rowsData, static function (array $a, array $b): int {
            return [
                (string) ($a['Jenis'] ?? ''),
                (float) ($a['Tebal'] ?? 0),
                (float) ($a['Lebar'] ?? 0),
                (float) ($a['Panjang'] ?? 0),
            ] <=> [
                (string) ($b['Jenis'] ?? ''),
                (float) ($b['Tebal'] ?? 0),
                (float) ($b['Lebar'] ?? 0),
                (float) ($b['Panjang'] ?? 0),
            ];
        });
        $grouped = collect($rowsData)->groupBy(fn($row) => (string) ($row['Jenis'] ?? 'LAINNYA'));
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $toFloat = static fn($v): float => is_numeric($v) ? (float) $v : 0.0;
        $toInt = static fn($v): int => is_numeric($v) ? (int) round((float) $v) : 0;
        $fmtPcs = static fn($v): string => $toInt($v) === 0 ? '' : number_format($toInt($v), 0, '.', ',');
        $fmtM3 = static fn($v): string => abs($toFloat($v)) < 0.0000001 ? '' : number_format($toFloat($v), 4, '.', ',');
        $grand = [
            'AwalPcs' => 0,
            'AwalM3' => 0.0,
            'MasukPcs' => 0,
            'MasukM3' => 0.0,
            'MinusPcs' => 0,
            'MinusM3' => 0.0,
            'JualPcs' => 0,
            'JualM3' => 0.0,
            'AkhirPcs' => 0,
            'AkhirM3' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Barang Jadi Per-Jenis Per-Ukuran (M3)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($grouped as $jenis => $groupRows)
        @php
            $totals = [
                'AwalPcs' => 0,
                'AwalM3' => 0.0,
                'MasukPcs' => 0,
                'MasukM3' => 0.0,
                'MinusPcs' => 0,
                'MinusM3' => 0.0,
                'JualPcs' => 0,
                'JualM3' => 0.0,
                'AkhirPcs' => 0,
                'AkhirM3' => 0.0,
            ];
        @endphp
        <div class="section-title">{{ $jenis }}</div>
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" style="width:10%;">Tebal</th>
                    <th rowspan="2" style="width:10%;">Lebar</th>
                    <th rowspan="2" style="width:10%;">Panjang</th>
                    <th colspan="2">Awal</th>
                    <th colspan="2">Masuk</th>
                    <th colspan="2">Minus</th>
                    <th colspan="2">Jual</th>
                    <th colspan="2">Akhir</th>
                </tr>
                <tr class="headers-row">
                    <th style="width:62px;">Pcs</th>
                    <th style="width:72px;">m3</th>
                    <th style="width:62px;">Pcs</th>
                    <th style="width:72px;">m3</th>
                    <th style="width:62px;">Pcs</th>
                    <th style="width:72px;">m3</th>
                    <th style="width:62px;">Pcs</th>
                    <th style="width:72px;">m3</th>
                    <th style="width:62px;">Pcs</th>
                    <th style="width:72px;">m3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupRows as $row)
                    @php
                        foreach (array_keys($totals) as $key) {
                            if (str_ends_with($key, 'Pcs')) {
                                $totals[$key] += $toInt($row[$key] ?? null);
                                $grand[$key] += $toInt($row[$key] ?? null);
                            } else {
                                $totals[$key] += $toFloat($row[$key] ?? null);
                                $grand[$key] += $toFloat($row[$key] ?? null);
                            }
                        }
                    @endphp
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ $loop->iteration }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['Tebal'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['Lebar'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['Panjang'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['AwalPcs'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtM3($row['AwalM3'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['MasukPcs'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtM3($row['MasukM3'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['MinusPcs'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtM3($row['MinusM3'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['JualPcs'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtM3($row['JualM3'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtPcs($row['AkhirPcs'] ?? null) }}</td>
                        <td class="number data-cell">{{ $fmtM3($row['AkhirM3'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="4" class="blank">Total {{ $jenis }}</td>
                    <td class="number">{{ $fmtPcs($totals['AwalPcs']) }}</td>
                    <td class="number">{{ $fmtM3($totals['AwalM3']) }}</td>
                    <td class="number">{{ $fmtPcs($totals['MasukPcs']) }}</td>
                    <td class="number">{{ $fmtM3($totals['MasukM3']) }}</td>
                    <td class="number">{{ $fmtPcs($totals['MinusPcs']) }}</td>
                    <td class="number">{{ $fmtM3($totals['MinusM3']) }}</td>
                    <td class="number">{{ $fmtPcs($totals['JualPcs']) }}</td>
                    <td class="number">{{ $fmtM3($totals['JualM3']) }}</td>
                    <td class="number">{{ $fmtPcs($totals['AkhirPcs']) }}</td>
                    <td class="number">{{ $fmtM3($totals['AkhirM3']) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($rowsData !== [])
        <div class="summary-block">
            <div class="section-title">Rangkuman</div>
            <ul class="summary-list">
                <li>Awal : <b>{{ $fmtPcs($grand['AwalPcs']) !== '' ? $fmtPcs($grand['AwalPcs']) : '-' }} </b> Pcs /
                    <b>{{ $fmtM3($grand['AwalM3']) !== '' ? $fmtM3($grand['AwalM3']) : '-' }} m3</b>
                </li>
                <li>Masuk : <b>{{ $fmtPcs($grand['MasukPcs']) !== '' ? $fmtPcs($grand['MasukPcs']) : '-' }} </b> Pcs /
                    <b>{{ $fmtM3($grand['MasukM3']) !== '' ? $fmtM3($grand['MasukM3']) : '-' }} m3</b>
                </li>
                <li>Minus : <b>{{ $fmtPcs($grand['MinusPcs']) !== '' ? $fmtPcs($grand['MinusPcs']) : '-' }} </b> Pcs /
                    <b>{{ $fmtM3($grand['MinusM3']) !== '' ? $fmtM3($grand['MinusM3']) : '-' }} m3</b>
                </li>
                <li>Jual : <b>{{ $fmtPcs($grand['JualPcs']) !== '' ? $fmtPcs($grand['JualPcs']) : '-' }} </b> Pcs /
                    <b>{{ $fmtM3($grand['JualM3']) !== '' ? $fmtM3($grand['JualM3']) : '-' }} m3</b>
                </li>
                <li>Akhir : <b>{{ $fmtPcs($grand['AkhirPcs']) !== '' ? $fmtPcs($grand['AkhirPcs']) : '-' }} </b> Pcs /
                    <b>{{ $fmtM3($grand['AkhirM3']) !== '' ? $fmtM3($grand['AkhirM3']) : '-' }} m3</b>
                </li>
            </ul>
        </div>
    @endif
</body>

</html>