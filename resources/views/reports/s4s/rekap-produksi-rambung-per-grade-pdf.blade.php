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

        .total-row td {
            font-weight: bold;
        }

        .col-no {
            width: 3%;
        }

        .col-jenis {
            width: 17%;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $inputCols = is_array($data['input_columns'] ?? null) ? $data['input_columns'] : [];
        $outputCols = is_array($data['output_columns'] ?? null) ? $data['output_columns'] : [];
        $total = is_array($data['total'] ?? null) ? $data['total'] : [];

        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtDay = static fn(string $v): string => $v === ''
            ? ''
            : \Carbon\Carbon::parse($v)->locale('id')->translatedFormat('d-M-y');
        $fmtTotal = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 2, '.', '');
        $fmtRatio = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 1, '.', '') . '%';

        $colCount = 1 + count($inputCols) * 2 + count($outputCols) * 2;

        // Dynamic columns: split leftover width proportionally (Tanggal 7%).
        $pairCount = count($inputCols) + count($outputCols);
        $pairPct = $pairCount > 0 ? round(93.0 / $pairCount, 2) : 0.0;
        $halfPairPct = $pairPct > 0 ? round($pairPct / 2, 2) : 0.0;
        $inputPct = $pairPct * count($inputCols);
        $outputPct = $pairPct * count($outputCols);
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Rambung Per Grade</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="3" style="width: 7%;">Tanggal</th>
                <th colspan="{{ count($inputCols) * 2 }}" style="width: {{ $inputPct }}%;">Input</th>
                <th colspan="{{ count($outputCols) * 2 }}" style="width: {{ $outputPct }}%;">Output</th>
            </tr>
            <tr>
                @foreach ($inputCols as $c)
                    <th colspan="2" style="width: {{ $pairPct }}%;">{{ $c }}</th>
                @endforeach
                @foreach ($outputCols as $c)
                    <th colspan="2" style="width: {{ $pairPct }}%;">{{ $c }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($inputCols as $c)
                    <th style="width: {{ $halfPairPct }}%;">Total</th>
                    <th style="width: {{ $halfPairPct }}%;">Ratio</th>
                @endforeach
                @foreach ($outputCols as $c)
                    <th style="width: {{ $halfPairPct }}%;">Total</th>
                    <th style="width: {{ $halfPairPct }}%;">Ratio</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp
            @foreach ($rows as $r)
                @php
                    $rowIndex++;
                    $cls = $rowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                    $in = is_array($r['input'] ?? null) ? $r['input'] : [];
                    $out = is_array($r['output'] ?? null) ? $r['output'] : [];
                @endphp
                <tr class="{{ $cls }}">
                    <td class="center">{{ $fmtDay((string) ($r['date'] ?? '')) }}</td>
                    @foreach ($inputCols as $c)
                        @php
                            $cell = $in[$c] ?? null;
                            $v = is_array($cell) ? (float) ($cell['total'] ?? 0.0) : 0.0;
                            $p = is_array($cell) ? (float) ($cell['ratio'] ?? 0.0) : 0.0;
                        @endphp
                        <td class="number">{{ $fmtTotal($v) }}</td>
                        <td class="number">{{ $fmtRatio($p) }}</td>
                    @endforeach
                    @foreach ($outputCols as $c)
                        @php
                            $cell = $out[$c] ?? null;
                            $v = is_array($cell) ? (float) ($cell['total'] ?? 0.0) : 0.0;
                            $p = is_array($cell) ? (float) ($cell['ratio'] ?? 0.0) : 0.0;
                        @endphp
                        <td class="number">{{ $fmtTotal($v) }}</td>
                        <td class="number">{{ $fmtRatio($p) }}</td>
                    @endforeach
                </tr>
            @endforeach

            @if ($rows !== [])
                <tr class="table-end-line">
                    <td colspan="{{ $colCount }}"></td>
                </tr>
            @endif

            @php
                $tIn = is_array($total['input'] ?? null) ? $total['input'] : [];
                $tOut = is_array($total['output'] ?? null) ? $total['output'] : [];
            @endphp
            <tr class="total-row">
                <td class="center">Total</td>
                @foreach ($inputCols as $c)
                    @php
                        $cell = $tIn[$c] ?? null;
                        $v = is_array($cell) ? (float) ($cell['total'] ?? 0.0) : 0.0;
                        $p = is_array($cell) ? (float) ($cell['ratio'] ?? 0.0) : 0.0;
                    @endphp
                    <td class="number">{{ $fmtTotal($v) }}</td>
                    <td class="number">{{ $fmtRatio($p) }}</td>
                @endforeach
                @foreach ($outputCols as $c)
                    @php
                        $cell = $tOut[$c] ?? null;
                        $v = is_array($cell) ? (float) ($cell['total'] ?? 0.0) : 0.0;
                        $p = is_array($cell) ? (float) ($cell['ratio'] ?? 0.0) : 0.0;
                    @endphp
                    <td class="number">{{ $fmtTotal($v) }}</td>
                    <td class="number">{{ $fmtRatio($p) }}</td>
                @endforeach
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
