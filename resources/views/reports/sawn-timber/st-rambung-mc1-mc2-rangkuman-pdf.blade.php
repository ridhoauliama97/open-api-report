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
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $summaryTables = is_array($data['summary_tables'] ?? null) ? $data['summary_tables'] : [];
        $tableSummaryRows = is_array($summaryTables['tables'] ?? null) ? $summaryTables['tables'] : [];
        $groupSummaryRows = is_array($summaryTables['groups'] ?? null) ? $summaryTables['groups'] : [];
        $grand = is_array($summaryTables['grand'] ?? null) ? $summaryTables['grand'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
        $generatedDate = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY');

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

    <h1 class="report-title">Laporan ST Hidup Rambung MC1 dan MC2 (Rangkuman)</h1>
    <p class="report-subtitle"> Per {{ $generatedDate }}</p>

    <div class="sub-title">Total Masing-masing Jenis Stock</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 55%">Jenis Stock</th>
                <th style="width: 17%">Jumlah Batang (Pcs)</th>
                <th style="width: 12%">Ton</th>
                <th style="width: 12%">Kubik (m3)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableSummaryRows as $sr)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $rowIndex }}</td>
                    <td class="data-cell">{{ (string) ($sr['tabel'] ?? '') }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sub-title">Grand Total Seluruh Group Stock</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 55%">Group Stock</th>
                <th style="width: 17%">Jumlah Batang (Pcs)</th>
                <th style="width: 12%">Ton</th>
                <th style="width: 12%">Kubik (m3)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupSummaryRows as $sr)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $rowIndex }}</td>
                    <td class="data-cell">{{ (string) ($sr['jenis'] ?? '') }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="center">Grand Total</td>
                <td class="number">{{ $fmtInt($grand['pcs'] ?? 0) }}</td>
                <td class="number">{{ $fmt4($grand['ton'] ?? 0) }}</td>
                <td class="number">{{ $fmt4($grand['kubik'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    </body>

</html>
