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

        .group-title {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
        }
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->isoFormat('DD-MMM-YY');

        $eps = 0.0000001;
        $fmt = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 4, '.', '');

        $grandTotals = [
            'InS4S' => 0.0,
            'InFJ' => 0.0,
            'InMoulding' => 0.0,
            'InLaminating' => 0.0,
            'InCCAkhir' => 0.0,
            'InWIP' => 0.0,
            'InReproses' => 0.0,
            'Output' => 0.0,
            'OutReproses' => 0.0,
        ];
        foreach ($groups as $g) {
            $t = is_array($g['totals'] ?? null) ? $g['totals'] : [];
            foreach (array_keys($grandTotals) as $key) {
                $grandTotals[$key] += (float) ($t[$key] ?? 0.0);
            }
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Moulding Per-Jenis & Per-Grade (m3)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($groups as $group)
        @php
            $jenis = (string) ($group['jenis'] ?? '');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
        @endphp

        <div class="group-title">{{ $jenis }}</div>

        <table style="margin-bottom: 12px;">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 10%;">Jenis Kayu</th>
                    <th style="width: 10%;">Nama Grade</th>
                    <th style="width: 8%;">In S4S</th>
                    <th style="width: 8%;">In FJ</th>
                    <th style="width: 8%;">In Moulding</th>
                    <th style="width: 8%;">In Laminating</th>
                    <th style="width: 8%;">In CCAkhir</th>
                    <th style="width: 8%;">In WIP</th>
                    <th style="width: 8%;">In Reproses</th>
                    <th style="width: 8%;">Output</th>
                    <th style="width: 8%;">Out Reproses</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @foreach ($rows as $row)
                    @php
                        $i++;
                        $row = is_array($row) ? $row : (array) $row;
                        $cls = $i % 2 === 1 ? 'row-odd' : 'row-even';
                    @endphp
                    <tr class="{{ $cls }}">
                        <td class="center">{{ $i }}</td>
                        <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td>{{ (string) ($row['NamaGrade'] ?? '') }}</td>
                        <td class="number">{{ $fmt($row['InS4S'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InFJ'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InMoulding'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InLaminating'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InCCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InWIP'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InReproses'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmt($row['Output'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmt($row['OutReproses'] ?? null) }}</td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="3" class="center">Total</td>
                    <td class="number">{{ $fmt($totals['InS4S'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InFJ'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InMoulding'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InLaminating'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InCCAkhir'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InWIP'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InReproses'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['Output'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['OutReproses'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if ($groups !== [])
        <table style="margin-top: 6px;">
            <tbody>
                <tr class="totals-row">
                    <td style="width:23%" class="center">Grand Total</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InS4S']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InFJ']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InMoulding']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InLaminating']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InCCAkhir']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InWIP']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['InReproses']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['Output']) }}</td>
                    <td class="number" style="width: 8%">{{ $fmt($grandTotals['OutReproses']) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>

</html>