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
        }

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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            page-break-inside: auto;
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
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            border: 1px solid #000;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .group-section-title {
            font-weight: bold;
            font-size: 11px;
            margin: 12px 0 5px 0;
        }

        .group-table {
            width: 260px;
            margin-left: 12px;
        }



        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $startText = \Carbon\Carbon::parse((string) ($startDate ?? now()))->locale('id')->translatedFormat('d-M-y');
        $endText = \Carbon\Carbon::parse((string) ($endDate ?? now()))->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $grandTotalTon = (float) ($summary['grand_total_ton'] ?? 0.0);

        $toFloat = static function (mixed $value): ?float {
            if ($value === null) {
                return null;
            }
            if (is_int($value) || is_float($value)) {
                return (float) $value;
            }
            if (is_string($value)) {
                $t = trim($value);
                if ($t === '') {
                    return null;
                }
                $t = str_replace(',', '', $t);
                return is_numeric($t) ? (float) $t : null;
            }
            return null;
        };

    @endphp

    <h1 class="report-title">Laporan ST Masuk Per-Group</h1>
    <p class="report-subtitle">Periode {{ $startText }} s/d {{ $endText }}</p>

    @forelse ($groups as $gidx => $group)
        @php
            $groupName = trim((string) ($group['name'] ?? ''));
            $groupName = $groupName !== '' ? $groupName : 'Tanpa Group';
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];

            // Aggregate by Tebal to avoid duplicates from SP output.
            $byTebal = [];
            foreach ($rows as $r) {
                $tebal = $toFloat($r['Tebal'] ?? null);
                if ($tebal === null) {
                    continue;
                }
                $key = number_format($tebal, 2, '.', ''); // stable key
                $byTebal[$key] = ($byTebal[$key] ?? 0.0) + (float) ($toFloat($r['STTon'] ?? null) ?? 0.0);
            }
            ksort($byTebal, SORT_NATURAL);

            $items = [];
            $sumTon = 0.0;
            foreach ($byTebal as $tebalKey => $tonSum) {
                $items[] = ['tebal' => (float) $tebalKey, 'ton' => (float) $tonSum];
                $sumTon += (float) $tonSum;
            }
        @endphp

        <div class="group-section-title">{{ $gidx + 1 }}. {{ $groupName }}</div>

        <table class="group-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 36px;">No</th>
                    <th style="width: 70px;">Tebal</th>
                    <th>ST (Ton)</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $idx => $it)
                    <tr class="{{ ($idx + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $idx + 1 }}</td>
                        <td class="number">{{ number_format((float) $it['tebal'], 2, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) $it['ton'], 4, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center" colspan="3">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if (count($items) > 0)
                    <tr class="totals-row">
                        <td colspan="2" class="center">Total Per-Group</td>
                        <td class="number">{{ number_format($sumTon, 4, '.', ',') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
