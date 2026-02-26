<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        @page {
            margin: 18mm 10mm 18mm 10mm;
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
            margin: 2px 0 14px;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            page-break-inside: auto;
        }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }

        th, td {
            border: 1px solid #9ca3af;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #fff;
        }

        td.center { text-align: center; }
        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .row-odd td { background: #c9d1df; }
        .row-even td { background: #eef2f8; }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            border: 1.5px solid #000;
            background: #f8f9fc;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left, .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right { text-align: right; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $startText = \Carbon\Carbon::parse((string) ($startDate ?? now()))->locale('id')->translatedFormat('d M Y');
        $endText = \Carbon\Carbon::parse((string) ($endDate ?? now()))->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');
        $grandTotalTon = (float) ($summary['grand_total_ton'] ?? 0.0);

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            return null;
        };

        $mejaSet = [];
        foreach ($groups as $group) {
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            foreach ($rows as $row) {
                $tebal = $toFloat($row['Tebal'] ?? null);
                if ($tebal === null) {
                    continue;
                }
                $mejaSet[(string) (int) round($tebal)] = (int) round($tebal);
            }
        }
        $mejaColumns = array_values($mejaSet);
        sort($mejaColumns, SORT_NUMERIC);

        $grandByMeja = [];
        foreach ($mejaColumns as $meja) {
            $grandByMeja[$meja] = 0.0;
        }
    @endphp

    <h1 class="report-title">Laporan ST (Sawmill) Masuk Per-Group</h1>
    <p class="report-subtitle">Periode {{ $startText }} s/d {{ $endText }}</p>

    <table>
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width: 12%;">Group Jenis</th>
                <th rowspan="2" style="width: 10%;">Jenis Kayu</th>
                <th rowspan="2" style="width: 8%;">Tebal</th>
                <th colspan="{{ max(1, count($mejaColumns)) }}">Meja ke :</th>
                <th rowspan="2" style="width: 10%;">Jumlah</th>
            </tr>
            <tr class="headers-row">
                @forelse ($mejaColumns as $meja)
                    <th>{{ $meja }}</th>
                @empty
                    <th>-</th>
                @endforelse
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                @php
                    $groupName = (string) ($group['name'] ?? 'Tanpa Group');
                    $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                    usort($rows, static function (array $a, array $b): int {
                        $left = is_numeric($a['Tebal'] ?? null) ? (float) $a['Tebal'] : 0.0;
                        $right = is_numeric($b['Tebal'] ?? null) ? (float) $b['Tebal'] : 0.0;
                        return $left <=> $right;
                    });
                    $groupJenis = '-';
                    foreach ($rows as $candidateRow) {
                        $candidateJenis = trim((string) ($candidateRow['Jenis'] ?? ''));
                        if ($candidateJenis !== '') {
                            $groupJenis = $candidateJenis;
                            break;
                        }
                    }
                    $groupByMeja = [];
                    foreach ($mejaColumns as $meja) {
                        $groupByMeja[$meja] = 0.0;
                    }
                @endphp

                @foreach ($rows as $row)
                    @php
                        $tebalInt = is_numeric($row['Tebal'] ?? null) ? (int) round((float) $row['Tebal']) : null;
                        $ton = (float) ($row['STTon'] ?? 0.0);
                        if ($tebalInt !== null && array_key_exists($tebalInt, $groupByMeja)) {
                            $groupByMeja[$tebalInt] += $ton;
                            $grandByMeja[$tebalInt] += $ton;
                        }
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        @if ($loop->first)
                            <td class="center" rowspan="{{ max(1, count($rows) + 1) }}">{{ $groupName }}</td>
                            <td class="center" rowspan="{{ max(1, count($rows) + 1) }}">{{ $groupJenis }}</td>
                        @endif
                        <td class="center">{{ $tebalInt !== null ? $tebalInt : '' }}</td>
                        @foreach ($mejaColumns as $meja)
                            <td class="number">
                                {{ $tebalInt === $meja ? number_format($ton, 4, '.', '') : '' }}
                            </td>
                        @endforeach
                        <td class="number">{{ number_format($ton, 4, '.', '') }}</td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td class="center">Jumlah</td>
                    @foreach ($mejaColumns as $meja)
                        <td class="number">{{ $groupByMeja[$meja] > 0 ? number_format($groupByMeja[$meja], 4, '.', '') : '' }}</td>
                    @endforeach
                    <td class="number">{{ number_format((float) ($group['total_ton'] ?? 0.0), 4, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="{{ 4 + max(1, count($mejaColumns)) }}">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (count($groups) > 0)
                <tr class="totals-row">
                    <td colspan="3" class="center">Total</td>
                    @foreach ($mejaColumns as $meja)
                        <td class="number">{{ $grandByMeja[$meja] > 0 ? number_format($grandByMeja[$meja], 4, '.', '') : '' }}</td>
                    @endforeach
                    <td class="number">{{ number_format($grandTotalTon, 4, '.', '') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
