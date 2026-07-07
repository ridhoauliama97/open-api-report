<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .data-table td {
            font-size: 10px;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .grand-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 8px 4px;
        }

        .col-no {
            width: 5%;
        }

        .col-biaya,
        .col-akum,
        .col-penyusutan,
        .col-nilai {
            width: 18%;
        }
    </style>
</head>

<body>
    @php
        /*
        |----------------------------------------------------------------------
        | Laporan Daftar Penyusutan Aktiva Tetap
        |----------------------------------------------------------------------
        |
        | Struktur data yang diharapkan:
        |
        | reportData = [
        |     'company'       => string|null,       // nama perusahaan header
        |     'title'         => string|null,       // judul laporan (fallbackTitle jika kosong)
        |     'period_label'  => string|null,       // subtitle periode
        |     'start_date'    => string|null,       // tanggal mulai (format bebas)
        |     'end_date'      => string|null,       // tanggal akhir
        |     'printed_by'    => string|null,       // username pencetak
        |
        |     // One row per category — values from category_subtotals
        |     'grouped_rows' => [
        |         'Bangunan Pabrik' => [...],
        |         ...
        |     ],
        |
        |     // Aggregated totals per category
        |     'category_subtotals' => [
        |         'Bangunan Pabrik' => [
        |             'acquisition_cost'  => 0,
        |             'accum_depreciation'=> 0,
        |             'depreciation'      => 0,
        |             'ending_value'      => 0,
        |         ],
        |         ...
        |     ],
        |
        |     // Grand totals across all categories
        |     'grand_totals' => [
        |         'acquisition_cost'   => 0,
        |         'accum_depreciation' => 0,
        |         'depreciation'       => 0,
        |         'ending_value'       => 0,
        |     ],
        | ]
        |
        | Filter referensi:
        | - Hanya tampilkan WHERE Acquisition Cost > 0
        | - Kategori 'MESIN & PERLATAN PABRIK' disaring
        | - Kode Asset yang diawali KP-004, MS-1360, MSP-141, TN-1010 disaring
        |
        | Urutan kolom:
        | No | Kategori Asset | Biaya Akuisisi | Akumulasi Penyusutan | Penyusutan | Nilai Akhir
        |----------------------------------------------------------------------
        */

        $groupedRows = $reportData['grouped_rows'] ?? [];
        $categorySubtotals = $reportData['category_subtotals'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];

        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ($reportData['period']['label'] ?? '')));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($groupedRows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th>Kategori Asset</th>
                    <th class="col-biaya">Biaya Akuisisi</th>
                    <th class="col-akum">Akumulasi Penyusutan</th>
                    <th class="col-penyusutan">Penyusutan</th>
                    <th class="col-nilai">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRowNumber = 0; @endphp
                @foreach ($groupedRows as $categoryName => $rows)
                    @php
                        $globalRowNumber++;
                        $sub = $categorySubtotals[$categoryName] ?? [];
                    @endphp
                    <tr class="{{ $globalRowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $globalRowNumber }}</td>
                        <td>{{ $categoryName }}</td>
                        <td class="number nowrap">
                            {{ number_format((float) ($sub['acquisition_cost'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="number nowrap">
                            {{ number_format((float) ($sub['accum_depreciation'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="number nowrap">
                            {{ number_format((float) ($sub['depreciation'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="number nowrap">
                            {{ number_format((float) ($sub['ending_value'] ?? 0), 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach

                @php
                    $gtAcq = (float) ($grandTotals['acquisition_cost'] ?? 0);
                    $gtAccum = (float) ($grandTotals['accum_depreciation'] ?? 0);
                    $gtDep = (float) ($grandTotals['depreciation'] ?? 0);
                    $gtEnd = (float) ($grandTotals['ending_value'] ?? 0);
                @endphp
                <tr class="grand-row">
                    <td colspan="2" class="center">Grand Total</td>
                    <td class="number">{{ number_format($gtAcq, 2, ',', '.') }}</td>
                    <td class="number">{{ number_format($gtAccum, 2, ',', '.') }}</td>
                    <td class="number">{{ number_format($gtDep, 2, ',', '.') }}</td>
                    <td class="number">{{ number_format($gtEnd, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
