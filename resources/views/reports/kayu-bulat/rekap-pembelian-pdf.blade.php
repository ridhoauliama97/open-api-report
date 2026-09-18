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
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $yearRows = is_array($data['year_rows'] ?? null) ? $data['year_rows'] : [];
        $monthLabels = is_array($data['month_labels'] ?? null) ? $data['month_labels'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmt = static fn(float $value): string => abs($value) < 0.0000001 ? '' : number_format($value, 4, '.', ',');

        $startYear = (int) ($summary['start_year'] ?? 0);
        $endYear = (int) ($summary['end_year'] ?? 0);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
    @endphp

    <h1 class="report-title">Laporan Rekap Pembelian Kayu Bulat (Ton)</h1>
    <p class="report-subtitle">
        @if ($startYear > 0 && $endYear > 0)
            Periode {{ $startYear }} s/d {{ $endYear }}
        @endif
    </p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 48px;">Tahun</th>
                        @foreach ($monthLabels as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th style="width: 74px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($yearRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell" style="font-weight:bold; font-size: 11px;">
                                {{ $row['tahun'] ?? '' }}
                            </td>
                            @foreach ($monthLabels as $month => $label)
                                <td class="number data-cell">{{ $fmt((float) ($row['months'][$month] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number data-cell" style="font-weight: bold; font-size:11px;">
                                {{ $fmt((float) ($row['total'] ?? 0.0)) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count($monthLabels) }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>