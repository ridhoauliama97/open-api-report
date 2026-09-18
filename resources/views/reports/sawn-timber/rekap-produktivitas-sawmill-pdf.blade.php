<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
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

        .meta-layout,
        .meta-table,
        .note-table,
        .ratio-table {
            width: auto;
        }

        table.meta-layout,
        table.meta-table,
        table.note-table,
        table.ratio-table,
        table.meta-layout td,
        table.meta-table td,
        table.note-table td,
        table.ratio-table td,
        table.meta-layout th,
        table.meta-table th,
        .meta-label,
        .meta-separator,
        .meta-value {
            border: 0;
        }

        .group-title,
        .customer-title,
        .grade-output,
        .date-separator,
        .grade-title {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
        }

        .total-row td,
        .grand-total-row td,
        .row-last td,
        .before-total td,
        .totals-label {
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->isoFormat('DD-MMM-YY');

        $fmt = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');
        $fmtInt = static fn(int $v): string => $v === 0 ? '' : (string) $v;
        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable $exception) {
                return $key;
            }
        };

        $totJabon = 0.0;
        $totKayuL = 0.0;
        $totMc1 = 0.0;
        $totMc2 = 0.0;
        $totStd = 0.0;
        $totAll = 0.0;
        $totJumlahMeja = 0;
        foreach ($rows as $r) {
            $totJumlahMeja += (int) ($r['JumlahMeja'] ?? 0);
            $totJabon += (float) ($r['JABON'] ?? 0.0);
            $totKayuL += (float) ($r['RAMBUNG KAYU L'] ?? 0.0);
            $totMc1 += (float) ($r['RAMBUNG MC 1'] ?? 0.0);
            $totMc2 += (float) ($r['RAMBUNG MC 2'] ?? 0.0);
            $totStd += (float) ($r['RAMBUNG STD'] ?? 0.0);
            $totAll += (float) ($r['Total'] ?? 0.0);
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produktivitas Sawmill</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 12%;">Jumlah Meja</th>
                <th style="width: 10%">Jabon</th>
                <th>Rambung <br> Kayu Lat</th>
                <th>Rambung <br> MC 1</th>
                <th>Rambung <br> MC 2</th>
                <th>Rambung <br> STD</th>
                <th style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    $tanggal = trim((string) ($row['Tanggal'] ?? ''));
                    $jumlahMeja = (int) ($row['JumlahMeja'] ?? 0);
                    $jabon = (float) ($row['JABON'] ?? 0.0);
                    $kayuL = (float) ($row['RAMBUNG KAYU L'] ?? 0.0);
                    $mc1 = (float) ($row['RAMBUNG MC 1'] ?? 0.0);
                    $mc2 = (float) ($row['RAMBUNG MC 2'] ?? 0.0);
                    $std = (float) ($row['RAMBUNG STD'] ?? 0.0);
                    $total = (float) ($row['Total'] ?? 0.0);
                @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $dateLabel($tanggal) }}</td>
                    <td class="center">{{ $fmtInt($jumlahMeja) }}</td>
                    <td class="number">{{ $fmt($jabon) }}</td>
                    <td class="number">{{ $fmt($kayuL) }}</td>
                    <td class="number">{{ $fmt($mc1) }}</td>
                    <td class="number">{{ $fmt($mc2) }}</td>
                    <td class="number">{{ $fmt($std) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [])
                <tr class="totals-row">
                    <td class="center" style="font-weight: bold;">Total</td>
                    <td class="center" style="font-weight: bold;">{{ $totJumlahMeja === 0 ? '' : $totJumlahMeja }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totJabon) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totKayuL) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totMc1) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totMc2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totStd) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totAll) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>

</html>