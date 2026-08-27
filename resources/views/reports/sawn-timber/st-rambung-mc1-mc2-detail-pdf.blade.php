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
        $summaryTables = is_array($data['summary_tables'] ?? null) ? $data['summary_tables'] : [];

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

        $fmt4 = static function ($v): string {
            $n = (float) ($v ?? 0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 4, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan ST Hidup Rambung MC1 dan MC2 (Detail)</h1>
    <p class="report-subtitle">Per {{ $generatedDate }}</p>

    @forelse ($groups as $group)
        @php
            $jenis = (string) ($group['jenis'] ?? '-');
            $subgroups = is_array($group['subgroups'] ?? null) ? $group['subgroups'] : [];
        @endphp

        <div class="group-title">{{ $jenis }}</div>

        @foreach ($subgroups as $sg)
            @php
                $label = (string) ($sg['label'] ?? '');
                $rows = is_array($sg['rows'] ?? null) ? $sg['rows'] : [];
                $totals = is_array($sg['totals'] ?? null) ? $sg['totals'] : [];
            @endphp

            @if ($label !== '')
                <div class="sub-title">{{ $label }}</div>
            @endif

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 70px">No ST</th>
                        <th style="width: 50px">Tebal (mm)</th>
                        <th style="width: 50px">Lebar (mm)</th>
                        <th style="width: 50px">Panjang (ft)</th>
                        <th style="width: 70px">Jumlah Batang (pcs)</th>
                        <th style="width: 70px">Ton</th>
                        <th style="width: 70px">Kubik</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $rowIndex }}</td>
                            <td class="center data-cell">{{ (string) ($r['NoST'] ?? '') }}</td>
                            <td class="center data-cell">{{ $fmtDimInt($r['Tebal'] ?? 0) }}</td>
                            <td class="center data-cell">{{ $fmtDimInt($r['Lebar'] ?? 0) }}</td>
                            <td class="center data-cell">{{ $fmtDimInt($r['Panjang'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmtInt($r['Pcs'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($r['Ton'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($r['Kubik'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if ($rows !== [])
                        <tr class="totals-row">
                            <td colspan="5" class="center" style="text-align: center;">Total {{ $label }}
                            </td>
                            <td class="number">{{ $fmtInt($totals['pcs'] ?? 0) }}</td>
                            <td class="number">{{ $fmt4($totals['ton'] ?? 0) }}</td>
                            <td class="number">{{ $fmt4($totals['kubik'] ?? 0) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
        @empty
            <div class="center">Tidak ada data.</div>
        @endforelse

        @php
            $tableSummaryRows = is_array($summaryTables['tables'] ?? null) ? $summaryTables['tables'] : [];
            $groupSummaryRows = is_array($summaryTables['groups'] ?? null) ? $summaryTables['groups'] : [];
            $grand = is_array($summaryTables['grand'] ?? null) ? $summaryTables['grand'] : [];
        @endphp

        @if ($tableSummaryRows !== [] || $groupSummaryRows !== [])
            <div class="section-title">Rangkuman</div>
        @endif

        @if ($tableSummaryRows !== [])
            <div class="sub-title">Total Masing-masing Jenis Stock</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th>Jenis Stock</th>
                        <th>Jumlah Batang (Pcs)</th>
                        <th>Ton</th>
                        <th>Kubik</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tableSummaryRows as $sr)
                        @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $rowIndex }}</td>
                            <td class="data-cell">{{ (string) ($sr['tabel'] ?? '') }}</td>
                            <td class="number data-cell">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($groupSummaryRows !== [])
            <div class="sub-title">Grand Total Seluruh Group Stock</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th>Group Stock</th>
                        <th>Jumlah Batang (Pcs)</th>
                        <th>Ton</th>
                        <th>Kubik</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupSummaryRows as $sr)
                        @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $rowIndex }}</td>
                            <td class="data-cell">{{ (string) ($sr['jenis'] ?? '') }}</td>
                            <td class="number data-cell">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="2" class="center">Grand Total</td>
                        <td class="number">{{ $fmtInt($grand['pcs'] ?? 0) }}</td>
                        <td class="number">{{ $fmt4($grand['ton'] ?? 0) }}</td>
                        <td class="number">{{ $fmt4($grand['kubik'] ?? 0) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        </body>

    </html>
