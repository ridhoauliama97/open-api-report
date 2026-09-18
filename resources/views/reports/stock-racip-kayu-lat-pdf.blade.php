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

        .group-title {
            font-size: 10px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .center td {
            text-align: center;
        }

        .zebra tbody tr:nth-child(odd) td {
            background: #c9d1df;
        }

        .zebra tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .zebra tbody tr.totals-row td {
            background: #fff !important;
        }

        .group-cols col.col-no {
            width: 8%;
        }

        .group-cols col.col-tebal,
        .group-cols col.col-lebar,
        .group-cols col.col-panjang {
            width: 14%;
        }

        .group-cols col.col-jumlah {
            width: 24%;
        }

        .group-cols col.col-hasil {
            width: 26%;
        }
    </style>


</head>

<body>
    @php
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $endDateText = $reportData['end_date_text'] ?? $endDate;
        $fmt4 = static function ($value): string {
            $num = (float) ($value ?? 0);
            return abs($num) < 0.0000001 ? '' : number_format($num, 4, '.', ',');
        };
        $fmtInt = static function ($value): string {
            $num = (float) ($value ?? 0);
            return abs($num) < 0.0000001 ? '' : number_format($num, 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Stok Racip Kayu Lat</h1>
    <p class="report-subtitle">Per Tanggal : {{ $endDateText }}</p>

    @if (!empty($groupedRows))
        @foreach ($groupedRows as $group)
            @php
                $groupRows = $group['rows'] ?? [];
                $sumBatang = 0.0;
                $sumHasil = 0.0;
                foreach ($groupRows as $r) {
                    $sumBatang += (float) ($r['JmlhBatang'] ?? 0);
                    $sumHasil += (float) ($r['Hasil'] ?? 0);
                }
            @endphp
            <div class="section">
                <p class="group-title">{{ $group['jenis'] }}</p>
                <table class="report-table zebra group-cols">
                    <colgroup class="group-cols">
                        <col class="col-no">
                        <col class="col-tebal">
                        <col class="col-lebar">
                        <col class="col-panjang">
                        <col class="col-jumlah">
                        <col class="col-hasil">
                    </colgroup>
                    <thead>
                        <tr class="headers-row">
                            <th>No</th>
                            <th>Tebal (mm)</th>
                            <th>Lebar (mm)</th>
                            <th>Panjang (ft)</th>
                            <th>Jumlah Batang (pcs)</th>
                            <th>Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupRows as $row)
                            <tr class="data-row center">
                                <td class="data-cell" style="text-align: center;">{{ $loop->iteration }}</td>
                                <td class="data-cell">{{ $fmtInt($row['Tebal'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmtInt($row['Lebar'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmtInt($row['Panjang'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmtInt($row['JmlhBatang'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmt4($row['Hasil'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td colspan="4">Jumlah</td>
                            <td>{{ $fmtInt($sumBatang) }}</td>
                            <td>{{ $fmt4($sumHasil) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <table>
            <tbody>
                <tr>
                    <td>Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>

</html>