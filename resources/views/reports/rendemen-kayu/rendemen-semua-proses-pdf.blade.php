<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $grandTotals = is_array($summary['grand_totals'] ?? null) ? $summary['grand_totals'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $fmtDate = static fn(string $v): string => $v === '' ? '' : \Carbon\Carbon::parse($v)->format('d-M-y');
        $fmt = static fn(?float $v): string => $v === null || abs($v) < 0.0000001 ? '' : number_format($v, 2, '.', ',');
        $fmtPercent = static fn(?float $v): string => $v === null || abs($v) < 0.0000001
            ? ''
            : number_format($v, 1, '.', ',') . '%';
    @endphp
    <h1 class="report-title">Laporan Rendemen Semua Proses</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @php
        $preferredGroupOrder = ['S4S', 'FJ', 'MLD', 'LMT', 'CCAKHIR', 'SAND', 'PACK'];
        $groupMap = [];
        foreach ($groups as $group) {
            $groupName = (string) ($group['name'] ?? 'LAINNYA');
            $groupMap[$groupName] = $group;
        }

        $orderedGroups = [];
        foreach ($preferredGroupOrder as $preferredName) {
            if (isset($groupMap[$preferredName])) {
                $orderedGroups[] = $groupMap[$preferredName];
                unset($groupMap[$preferredName]);
            }
        }

        foreach ($groupMap as $remainingGroup) {
            $orderedGroups[] = $remainingGroup;
        }

        $groups = $orderedGroups;
        $groupNames = array_map(static fn($group): string => (string) ($group['name'] ?? 'LAINNYA'), $groups);
        $pivotRows = [];
        $dateKeys = [];

        foreach ($groups as $group) {
            $groupName = (string) ($group['name'] ?? 'LAINNYA');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            foreach ($rows as $row) {
                $date = (string) ($row['Tanggal'] ?? '');
                if ($date === '') {
                    continue;
                }

                if (!isset($pivotRows[$date])) {
                    $pivotRows[$date] = [];
                    $dateKeys[] = $date;
                }

                $pivotRows[$date][$groupName] = [
                    'Input' => $row['Input'] ?? null,
                    'Output' => $row['Output'] ?? null,
                    'Rendemen' => $row['Rendemen'] ?? null,
                ];
            }
        }

        usort($dateKeys, static fn($a, $b): int => strcmp((string) $a, (string) $b));
    @endphp

    @if (empty($groupNames) || empty($dateKeys))
        <div class="center">Tidak ada data.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 44px;" rowspan="2">No</th>
                    <th style="width: 90px;" rowspan="2">Tanggal</th>
                    @foreach ($groupNames as $groupName)
                        <th colspan="3">{{ $groupName }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($groupNames as $groupName)
                        <th style="width: 70px;">Input</th>
                        <th style="width: 70px;">Output</th>
                        <th style="width: 50px;">%</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dateKeys as $idx => $date)
                    <tr class="{{ ($idx + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $idx + 1 }}</td>
                        <td class="center">{{ $fmtDate($date) }}</td>
                        @foreach ($groupNames as $groupName)
                            @php $value = $pivotRows[$date][$groupName] ?? null; @endphp
                            <td class="number">{{ $fmt($value['Input'] ?? null) }}</td>
                            <td class="number">{{ $fmt($value['Output'] ?? null) }}</td>
                            <td class="number" style="font-weight: bold;">{{ $fmtPercent($value['Rendemen'] ?? null) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="2" class="center">Total</td>
                    @foreach ($groups as $group)
                        <td class="number">{{ $fmt($group['totals']['Input'] ?? null) }}</td>
                        <td class="number">{{ $fmt($group['totals']['Output'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercent($group['totals']['Rendemen'] ?? null) }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif

    @if ($groups !== [])
        <div style="margin-top: 10px;">
            <div class="group-title" style="margin-bottom: 6px;">Rangkuman</div>
            <ul style="margin: 0; padding-left: 18px;">
                <li>Total Group :
                    <strong>{{ number_format((int) ($summary['total_groups'] ?? 0), 0, '.', ',') }}</strong>
                </li>
                <li>Total Baris :
                    <strong>{{ number_format((int) ($summary['total_rows'] ?? 0), 0, '.', ',') }}</strong>
                </li>
                <li>Total Input : <strong>{{ $fmt($grandTotals['Input'] ?? null) }}</strong></li>
                <li>Total Output : <strong>{{ $fmt($grandTotals['Output'] ?? null) }}</strong></li>
                <li>Rendemen : <strong>{{ $fmtPercent($grandTotals['Rendemen'] ?? null) }}</strong></li>
            </ul>
        </div>
    @endif

    </body>

</html>
