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

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
            page-break-inside: auto;
            margin-bottom: 4px;
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
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tfoot {
            display: table-row-group;
        }


        .report-table tbody tr.data-row td.data-cell {
            border-top: 0;
            border-bottom: 0;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $generatedByName = $generatedBy->name ?? 'sistem';
    @endphp
    <h1 class="report-title">Laporan Produksi Harian Washing</h1>
    <p class="report-subtitle">Per Tanggal : {{ \Carbon\Carbon::parse($endDate)->format('d-M-y') }}</p>
    @php
        $sections = [
            'Output' => ['Output'],
            'Input' => ['Input'],
            'Waste' => ['Waste'],
            'Bongkar Susun Output' => ['BSU Output', 'BSusun Output'],
            'Bongkar Susun Input' => ['BSU Input', 'BSusun Input'],
        ];
        $normalizeDimType = static function ($value): string {
            return strtolower((string) preg_replace('/\s+/', ' ', trim((string) $value)));
        };
        $dimTypeToSection = [];
        foreach ($sections as $title => $aliases) {
            foreach ($aliases as $alias) {
                $dimTypeToSection[$normalizeDimType($alias)] = $title;
            }
        }
        $groupedRows = [];
        foreach (array_keys($sections) as $title) {
            $groupedRows[$title] = [];
        }
        foreach ($rowsData as $row) {
            $dimType = $normalizeDimType($row['DimType'] ?? '');
            $targetSection = $dimTypeToSection[$dimType] ?? null;
            if ($targetSection !== null) {
                $groupedRows[$targetSection][] = $row;
            }
        }
        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (!is_string($value)) {
                return null;
            }
            $normalized = str_replace([' ', ','], ['', '.'], trim($value));
            return is_numeric($normalized) ? (float) $normalized : null;
        };
    @endphp
    @foreach ($groupedRows as $sectionTitle => $sectionRows)
        <div class="section-title">{{ $sectionTitle }}</div>
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 120px;">Kode Barang</th>
                    <th>Jenis</th>
                    <th style="width: 70px;">Pcs</th>
                    <th style="width: 80px;">Berat</th>
                    <th style="width: 70px;">Warehouse</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sectionRows as $row)
                    @php
                        $pcs = $toFloat($row['Pcs'] ?? null);
                        $berat = $toFloat($row['Berat'] ?? null);
                        $rowClass = $loop->odd ? 'row-odd' : 'row-even';
                    @endphp
                    <tr class="data-row {{ $rowClass }}">
                        <td class="data-cell">{{ (string) ($row['ItemCode'] ?? '') }}</td>
                        <td class="data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td class="data-cell number">{{ $pcs === null ? '' : number_format($pcs, 0, '.', ',') }}</td>
                        <td class="data-cell number">{{ $berat === null ? '' : number_format($berat, 2, '.', ',') }}
                        </td>
                        <td class="data-cell number" style="text-align: center">
                            {{ (string) ($row['IdWarehouse'] ?? '') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"
                            style="text-align: center; background: #c9d1df; font-weight: bold; font-size: 10px; font-style: italic;">
                            Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh {{ $generatedByName }} pada
                {{ $generatedAt->copy()->format('d-M-y H:i') }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
