<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $periods = is_array($data['periods'] ?? null) ? $data['periods'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmt = static fn(float $value): string => number_format($value, 2, '.', ',');
        $fmtBlankZero = static fn(float $value): string => abs($value) < 0.0000001
            ? ''
            : number_format($value, 2, '.', ',');
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $dateKeys = [];
        foreach ($periods as $period) {
            $raw = (string) ($period['key'] ?? ($period['label'] ?? ''));
            if ($raw !== '') {
                $dateKeys[] = $raw;
            }
        }
        $dateKeys = array_values(array_unique($dateKeys));
        sort($dateKeys);

        $dateLabels = [];
        foreach ($dateKeys as $dateKey) {
            try {
                $dateLabels[$dateKey] = \Carbon\Carbon::parse($dateKey)->locale('id')->translatedFormat('d-M');
            } catch (\Throwable $e) {
                $dateLabels[$dateKey] = $dateKey;
            }
        }

        $matrix = [];
        foreach ($periods as $period) {
            $dateKey = (string) ($period['key'] ?? ($period['label'] ?? ''));
            foreach ($period['rows'] ?? [] as $row) {
                $supplier = trim((string) ($row['NmSupplier'] ?? 'Tanpa Supplier'));
                $ton = (float) ($row['TonBerat'] ?? 0.0);
                if (!isset($matrix[$supplier])) {
                    $matrix[$supplier] = [
                        'supplier' => $supplier,
                        'by_date' => [],
                        'total' => 0.0,
                    ];
                }
                $matrix[$supplier]['by_date'][$dateKey] = ($matrix[$supplier]['by_date'][$dateKey] ?? 0.0) + $ton;
                $matrix[$supplier]['total'] += $ton;
            }
        }

        $supplierRows = array_values($matrix);
        usort($supplierRows, static function (array $a, array $b): int {
            $cmp = ($b['total'] ?? 0.0) <=> ($a['total'] ?? 0.0);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strnatcasecmp((string) ($a['supplier'] ?? ''), (string) ($b['supplier'] ?? ''));
        });

        $columnTotals = array_fill_keys($dateKeys, 0.0);
        foreach ($supplierRows as $row) {
            foreach ($dateKeys as $dateKey) {
                $columnTotals[$dateKey] += (float) ($row['by_date'][$dateKey] ?? 0.0);
            }
        }
        $grandTotal = array_sum($columnTotals);
    @endphp

    <h1 class="report-title">Laporan Timeline KB - Harian (Rambung)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th class="col-left" style="width: 36px;">No</th>
                        <th class="col-left" style="width: 190px; text-align: left;">Nama Supplier</th>
                        @foreach ($dateKeys as $dateKey)
                            <th>{{ $dateLabels[$dateKey] ?? $dateKey }}</th>
                        @endforeach
                        <th class="col-total" style="width: 72px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplierRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center col-left">{{ $loop->iteration }}</td>
                            <td class="col-left" style="text-align: left;">{{ $row['supplier'] ?? '' }}</td>
                            @foreach ($dateKeys as $dateKey)
                                <td class="number">{{ $fmtBlankZero((float) ($row['by_date'][$dateKey] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number col-total" style="font-weight: bold;">
                                {{ $fmtBlankZero((float) ($row['total'] ?? 0.0)) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($dateKeys) }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse

                    @if ($supplierRows !== [])
                        <tr class="totals-row">
                            <td colspan="2" class="center col-left">Total</td>
                            @foreach ($dateKeys as $dateKey)
                                <td class="number">{{ $fmtBlankZero((float) ($columnTotals[$dateKey] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number col-total">{{ $fmtBlankZero((float) $grandTotal) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    </body>

</html>
