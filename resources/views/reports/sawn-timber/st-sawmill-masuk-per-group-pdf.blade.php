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

        .center {
            text-align: center;
        }

        .group-section-title {
            font-weight: bold;
            font-size: 11px;
            margin: 12px 0 5px 0;
        }

        .group-table {
            width: 260px;
            margin-left: 12px;
            margin-bottom: 8px;
        }
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $startText = \Carbon\Carbon::parse((string) ($startDate ?? now()))->locale('id')->isoFormat('DD-MMM-YY');
        $endText = \Carbon\Carbon::parse((string) ($endDate ?? now()))->locale('id')->isoFormat('DD-MMM-YY');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
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
</body>

</html>