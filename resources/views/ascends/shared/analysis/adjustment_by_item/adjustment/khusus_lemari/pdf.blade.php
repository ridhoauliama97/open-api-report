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
            line-height: 1.2;
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
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 3px 4px;
            color: #9c111d;
        }

        .subgroup-header {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            padding: 3px 4px;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
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
            font-size: 11px;
            color: #9c111d;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .pintu-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 10px;
        }

        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 12px;
        }
    </style>
</head>

<body>
    @php
        $pintuGroups = $reportData['groups']['pintu_groups'] ?? [];
        $grandTotal = (float) ($reportData['groups']['grand_total'] ?? 0);
        $printedAt = $reportData['printed_at'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $rowNumber = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%">No</th>
                <th style="width: 36%">Nama Barang</th>
                <th style="width: 8%">Unit</th>
                <th style="width: 17%">Masuk (Kredit)</th>
                <th style="width: 17%">Keluar (Debit)</th>
                <th style="width: 18%">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pintuGroups as $pintuIdx => $pintuGroup)
                <tr class="group-row">
                    <td colspan="6">
                        {{ $pintuGroup['pintu'] }}
                    </td>
                </tr>
                @foreach ($pintuGroup['name_groups'] as $ngIdx => $nameGroup)
                    <tr class="subgroup-header">
                        <td colspan="6">
                            {{ $nameGroup['name_group'] }}
                        </td>
                    </tr>
                    @foreach ($nameGroup['pairs'] as $pair)
                        @php $rowNumber++; @endphp
                        <tr class="{{ $rowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $rowNumber }}</td>
                            <td>{{ $pair['nama_barang'] ?? '' }}</td>
                            <td class="center">{{ $pair['unit'] ?? '' }}</td>
                            <td class="number nowrap">
                                {{ number_format((float) ($pair['masuk'] ?? 0), 2, '.', ',') }}
                            </td>
                            <td class="number nowrap">
                                {{ number_format((float) ($pair['keluar'] ?? 0), 2, '.', ',') }}
                            </td>
                            <td class="number nowrap">
                                {{ number_format((float) ($pair['selisih'] ?? 0), 2, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="5" class="center">
                            Sub Total {{ $nameGroup['name_group'] }}
                        </td>
                        <td class="number nowrap">
                            {{ number_format((float) ($nameGroup['subtotal_selisih'] ?? 0), 2, '.', ',') }}
                        </td>
                    </tr>
                @endforeach
                <tr class="pintu-total-row">
                    <td colspan="5" class="center">
                        Total {{ $pintuGroup['pintu'] }}
                    </td>
                    <td class="number nowrap">
                        {{ number_format((float) ($pintuGroup['total_selisih'] ?? 0), 2, '.', ',') }}
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (!empty($pintuGroups))
                <tr class="grand-total-row">
                    <td colspan="5" class="center">
                        Grand Total
                    </td>
                    <td class="number nowrap">
                        {{ number_format($grandTotal, 2, '.', ',') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <p style="margin-top: 10px; font-size: 9px; font-style: italic;">
        Note: (-) Beban, (+) Pendapatan
    </p>

    @include('ascends.shared.partials.report-footer')
</body>

</html>