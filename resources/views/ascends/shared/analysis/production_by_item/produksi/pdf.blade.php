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

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .group-header td {
            padding: 4px;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-style: italic;
            color: #9c111d;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
        }

        .total-row td.label {
            text-align: center;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .grand-total-row td.label {
            text-align: center;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $groups = $reportData['groups'] ?? [];
        $grandTotal = $reportData['grand_total'] ?? 0;
        $printedAt = $reportData['printed_at'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50%">Nama Barang</th>
                <th style="width: 20%">UOM</th>
                <th style="width: 30%">Quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $groupIndex => $group)
                <tr class="group-header">
                    <td colspan="3">
                        {{ $group['group_name'] }}
                    </td>
                </tr>

                @foreach ($group['items'] as $itemIndex => $item)
                    <tr class="{{ $itemIndex % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td>{{ $item['item_name'] }}</td>
                        <td class="center">{{ $item['uom'] }}</td>
                        <td class="number nowrap">
                            {{ number_format($item['quantity'], 0, '.', ',') }}
                        </td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="2" class="label">Total :</td>
                    <td class="number nowrap">
                        {{ number_format($group['total_quantity'], 0, '.', ',') }}
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="3">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (!empty($groups))
                <tr class="grand-total-row">
                    <td colspan="2" class="label">Grand Total :</td>
                    <td class="number nowrap">
                        {{ number_format($grandTotal, 0, '.', ',') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>