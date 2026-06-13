<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
            color: #000;
        }

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
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

        .data-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td,
        .summary-table th,
        .summary-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th,
        .summary-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .grand-row td {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
        }

        .group-row td {
            text-align: center;
            font-weight: bold;
            color: #9c111d;
            font-size: 10px;
            font-style: italic;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
        }

        .subtotal-row td {
            text-align: left;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
        }

        .grand-row td {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 4px 5px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 10px;
        }

        .summary-wrapper {
            margin-top: 12px;
            width: 46%;
            margin-left: auto;
            margin-right: auto;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $generationSummary = $reportData['generation_summary'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())->locale('id')
            ->translatedFormat('d-M-y');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $generationDisplayLabel = static function (string $label): string {
            return trim((string) preg_replace('/^Generasi\s+/i', '', $label));
        };
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => 'Per Tanggal : ' . $printedAt])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 25%;">Nama</th>
                <th style="width: 31%;">Jabatan</th>
                <th style="width: 21%;">Departemen</th>
                <th style="width: 7%;">Usia</th>
                <th style="width: 12%;">Masa Kerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php $groupRows = $group['rows'] ?? []; @endphp
                <tr class="group-row">
                    <td colspan="6" class="center">Generasi :
                        {{ $generationDisplayLabel((string) ($group['label'] ?? '')) }}
                    </td>
                </tr>
                @foreach ($groupRows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td>{{ (string) ($row['Departemen'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Usia'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Masa Kerja'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="6">
                        Jumlah {{ (string) ($group['label'] ?? '') }} :
                        {{ (int) ($group['subtotal'] ?? 0) }}
                        ({{ (string) ($group['percent'] ?? '0.0%') }})
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-wrapper">
        <table class="summary-table">
            <thead>
                <tr>
                    <th style="width: 58%;">Generasi</th>
                    <th style="width: 20%;">Total</th>
                    <th style="width: 22%;">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($generationSummary as $summary)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($summary['label'] ?? '') }}</td>
                        <td class="center">{{ (int) ($summary['count'] ?? 0) }}</td>
                        <td class="center">{{ (string) ($summary['percent'] ?? '0.0%') }}</td>
                    </tr>
                @endforeach
                <tr class="grand-row">
                    <td>Grand Total</td>
                    <td class="center">{{ $totalRows }}</td>
                    <td class="center">{{ $totalRows > 0 ? '100.0%' : '0.0%' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
