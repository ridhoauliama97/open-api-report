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
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $toFloat = static function ($value): float {
            return is_numeric($value) ? (float) $value : 0.0;
        };

        $fmt = static function (float $value, bool $blankWhenZero = true): string {
            if ($blankWhenZero && abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 4, '.', ',');
        };

        $tonToM3Factor = 1.416;
        $totalTon = 0.0;
        $totalM3 = 0.0;
    @endphp

    <h1 class="report-title">Laporan Saldo Kayu Bulat</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 35px;">No</th>
                <th>Tanggal Masuk</th>
                <th style="width: 140px;">Jenis Kayu</th>
                <th>Supplier</th>
                <th style="width: 85px;">Ton</th>
                <th style="width: 85px;">M3</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $ton = $toFloat($row['Ton'] ?? 0);
                    $m3 = $ton * $tonToM3Factor;
                    $totalTon += $ton;
                    $totalM3 += $m3;
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">
                        {{ \Carbon\Carbon::parse((string) ($row['DateCreate'] ?? now()))->locale('id')->translatedFormat('d-M-y') }}
                    </td>
                    <td class="label">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td>{{ (string) ($row['NmSupplier'] ?? '') }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($ton, true) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($m3, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="4" class="center">Total (Ton)</td>
                <td class="number">{{ $fmt($totalTon, true) }}</td>
                <td class="number">{{ $fmt($totalM3, true) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
