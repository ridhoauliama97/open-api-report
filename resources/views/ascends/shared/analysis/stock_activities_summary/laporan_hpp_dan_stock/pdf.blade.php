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

        .center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .family-header td {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            font-style: italic;
            padding: 3px 4px;
            color: #9c111d;
            /* border-top: 1px solid #000;   */
            border-bottom: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $familyGroups = $reportData['family_groups'] ?? [];
        $printedAt = $reportData['printed_at'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $grandStockValue = $reportData['grand_stock_value'] ?? 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @php $globalRowNumber = 0; @endphp

    @if (count($familyGroups) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 15%">Kode Barang</th>
                    <th style="width: 28%">Nama Barang</th>
                    <th style="width: 15%">HPP/Pcs</th>
                    <th style="width: 17%">Stok Saldo</th>
                    <th style="width: 20%">Nilai Stok</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($familyGroups as $group)
                    <tr class="family-header">
                        <td colspan="6">{{ $group['family_name'] }}</td>
                    </tr>

                    @foreach ($group['items'] as $detail)
                        @php $globalRowNumber++; @endphp
                        <tr class="{{ $globalRowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $globalRowNumber }}</td>
                            <td class="center nowrap">{{ $detail['item_code'] ?? '' }}</td>
                            <td>{{ $detail['item_name'] ?? '' }}</td>
                            <td class="number nowrap">{{ number_format((float) ($detail['cog'] ?? 0), 2, ',', '.') }}</td>
                            <td class="number nowrap">
                                {{ number_format((float) ($detail['ending'] ?? 0), 2, ',', '.') }}
                                {{ $detail['uom'] ?? '' }}
                            </td>
                            <td class="number nowrap">{{ number_format((float) ($detail['stock_value'] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    <tr class="subtotal-row">
                        <td colspan="5" class="center">Total Value {{ $group['family_name'] }}</td>
                        <td class="number nowrap">
                            {{ number_format((float) ($group['subtotal_stock_value'] ?? 0), 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
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
