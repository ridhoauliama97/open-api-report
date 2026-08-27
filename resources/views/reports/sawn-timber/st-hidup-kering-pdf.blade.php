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
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $jenisGroups = is_array($data['jenis_groups'] ?? null) ? $data['jenis_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $hariValue = (int) ($summary['hari'] ?? ($hari ?? 90));
        $includeValue = (bool) ($summary['include'] ?? ($include ?? false));
        $excludeValue = (bool) ($summary['exclude'] ?? ($exclude ?? false));
        $filterText = trim(
            implode(', ', array_filter([$includeValue ? 'Include' : null, $excludeValue ? 'Exclude' : null])),
        );
        if ($filterText === '') {
            $filterText = 'Tidak ada data dipilih';
        }

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $generatedDate = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y');

        $fmtDimInt = static function ($v): string {
            $n = (float) ($v ?? 0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return (string) ((int) round($n));
        };

        $fmtInt = static function ($v): string {
            $n = (int) ($v ?? 0);
            return $n === 0 ? '' : (string) number_format($n, 0, '.', ',');
        };

        if ($jenisGroups === [] && $rows !== []) {
            $grouped = [];
            foreach ($rows as $row) {
                $jenis = trim((string) ($row['Jenis'] ?? ''));
                $jenis = $jenis !== '' ? $jenis : 'Tanpa Jenis';
                $key = strtoupper($jenis);
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'name' => $jenis,
                        'rows' => [],
                    ];
                }
                $grouped[$key]['rows'][] = $row;
            }
            $jenisGroups = array_values($grouped);
        }
    @endphp

    <h1 class="report-title">Laporan Label ST Hidup (Kering)</h1>
    <p class="report-subtitle"> Per {{ $generatedDate }} </p>

    @forelse ($jenisGroups as $group)
        @php
            $jenisName = trim((string) ($group['name'] ?? ''));
            $jenisName = $jenisName !== '' ? $jenisName : 'Tanpa Jenis';
            $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
        @endphp

        <div class="jenis-title">Jenis : {{ $jenisName }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 15%">No ST</th>
                    <th style="width: 15%">Tebal (mm) </th>
                    <th style="width: 15%">Lebar (mm)</th>
                    <th style="width: 20%">Jumlah Batang (Pcs)</th>
                    <th style="width: 20%">Lokasi</th>
                    <th style="width: 15%">Usia (Hari)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groupRows as $r)
                    @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                    <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ $rowIndex }}</td>
                        <td class="center data-cell">{{ (string) ($r['NoST'] ?? '') }}</td>
                        <td class="center data-cell">{{ $fmtDimInt($r['Tebal'] ?? 0) }}</td>
                        <td class="center data-cell">{{ $fmtDimInt($r['Lebar'] ?? 0) }}</td>
                        <td class="center data-cell">{{ $fmtInt($r['JmlhBatang'] ?? 0) }}</td>
                        <td class="center data-cell">{{ (string) ($r['IdLokasi'] ?? '') }}</td>
                        <td class="center data-cell">{{ $fmtInt($r['UsiaHari'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <table class="data-table">
            <tbody>
                <tr>
                    <td colspan="7" class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    </body>

</html>
