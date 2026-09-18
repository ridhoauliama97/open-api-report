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

        .cell-split {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
        }

        .cell-left {
            text-align: left;
        }

        .cell-right {
            text-align: right;
        }

        .total-row td,
        .grand-total-row td {
            font-weight: bold;
        }
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $grades = is_array($data['grades'] ?? null) ? $data['grades'] : [];
        $total = is_array($data['total'] ?? null) ? $data['total'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->isoFormat('DD-MMM-YY');

        $fmtDateCell = static fn(string $v): string => $v === ''
            ? ''
            : \Carbon\Carbon::parse($v)->locale('id')->isoFormat('DD-MMM-YY');
        $fmtPcs = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 0, '.', ',');
        $fmtPct = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 2, '.', '') . '%';

        // Columns: tanggal + (4 grade x 2) + total
        $colCount = 1 + count($grades) * 2 + 1;
    @endphp

    <h1 class="report-title">Laporan Grade ABC Harian</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 78px;">Tanggal</th>
                @foreach ($grades as $g)
                    <th colspan="2">{{ $g }}</th>
                @endforeach
                <th style="width: 90px;">Total</th>
            </tr>
            <tr>
                @foreach ($grades as $g)
                    <th style="width: 80px;">Pcs</th>
                    <th style="width: 50px;">%</th>
                @endforeach
                <th style="width: 90px;">Pcs</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp
            @foreach ($rows as $r)
                @php
                    $rowIndex++;
                    $cls = $rowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                    $cells = is_array($r['cells'] ?? null) ? $r['cells'] : [];
                    $totalPcs = (float) ($r['total_pcs'] ?? 0.0);
                @endphp
                <tr class="{{ $cls }}">
                    <td class="center">{{ $fmtDateCell((string) ($r['date'] ?? '')) }}</td>
                    @foreach ($grades as $g)
                        @php
                            $cell = $cells[$g] ?? null;
                            $pcs = is_array($cell) ? (float) ($cell['pcs'] ?? 0.0) : 0.0;
                            $pct = is_array($cell) ? (float) ($cell['percent'] ?? 0.0) : 0.0;
                        @endphp
                        <td class="number">{{ $fmtPcs($pcs) }}</td>
                        <td class="number">{{ $fmtPct($pct) }}</td>
                    @endforeach
                    <td class="number" style="font-weight: bold;">{{ $fmtPcs($totalPcs) }}</td>
                </tr>
            @endforeach

            @php
                $tCells = is_array($total['cells'] ?? null) ? $total['cells'] : [];
                $tTotal = (float) ($total['total_pcs'] ?? 0.0);
            @endphp
            <tr class="total-row">
                <td class="center">Total</td>
                @foreach ($grades as $g)
                    @php
                        $cell = $tCells[$g] ?? null;
                        $pcs = is_array($cell) ? (float) ($cell['pcs'] ?? 0.0) : 0.0;
                        $pct = is_array($cell) ? (float) ($cell['percent'] ?? 0.0) : 0.0;
                    @endphp
                    <td class="number">{{ $fmtPcs($pcs) }}</td>
                    <td class="number">{{ $fmtPct($pct) }}</td>
                @endforeach
                <td class="number">{{ $fmtPcs($tTotal) }}</td>
            </tr>

            @if ($rows === [])
                <tr>
                    <td colspan="{{ $colCount }}" class="center">Tidak ada data.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>