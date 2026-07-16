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
            margin: 0 0 12px 0;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 9px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .summary-table th,
        .summary-table td {
            text-align: center;
        }

        .summary-table .left {
            text-align: left;
        }

        .detail-table {
            margin-bottom: 14px;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .nowrap {
            white-space: nowrap;
        }

        .section-title {
            margin: 16px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
        }

        .po-title {
            margin: 14px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
            color: #9c111d;
            text-align: left;
        }

        .po-meta {
            width: 100%;
            margin: 0 0 8px 0;
            border-collapse: collapse;
            font-size: 10px;
        }

        .po-meta td {
            padding: 1px 3px;
            vertical-align: top;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $summaryGroups = $reportData['summary_groups'] ?? [];
        $summaryTotals = $reportData['summary_totals'] ?? [];
        $approvePoGroups = $reportData['approve_po_groups'] ?? [];
        $poPiGroups = $reportData['po_pi_groups'] ?? [];
        $prPiGroups = $reportData['pr_pi_groups'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="data-table summary-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 11%">Approve by</th>
                <th rowspan="2" colspan="2" style="width: 17%">Kategori</th>
                <th colspan="4" style="width: 24%">Lama Approve PO</th>
                <th colspan="4" style="width: 24%">PO Ke PI</th>
                <th colspan="4" style="width: 24%">PR Ke PI</th>
            </tr>
            <tr>
                <th>0 - 2</th>
                <th>%</th>
                <th>&gt; 2</th>
                <th>%</th>
                <th>0 - 5</th>
                <th>%</th>
                <th>&gt; 5</th>
                <th>%</th>
                <th>0 - 7</th>
                <th>%</th>
                <th>&gt; 7</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summaryGroups as $group)
                @foreach ($group['categories'] as $category)
                    <tr class="{{ $loop->parent->iteration % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        @if ($loop->first)
                            <td rowspan="{{ $group['rowspan'] }}">{{ $group['approved_by'] }}</td>
                        @endif
                        <td colspan="2" class="left">{{ $category['category'] }}</td>
                        <td>{{ $category['apr_0_2'] }}</td>
                        <td>{{ $category['apr_0_2_pct'] }}</td>
                        <td>{{ $category['apr_gt2'] }}</td>
                        <td>{{ $category['apr_gt2_pct'] }}</td>
                        <td>{{ $category['popi_0_5'] }}</td>
                        <td>{{ $category['popi_0_5_pct'] }}</td>
                        <td>{{ $category['popi_gt5'] }}</td>
                        <td>{{ $category['popi_gt5_pct'] }}</td>
                        <td>{{ $category['prpi_0_7'] }}</td>
                        <td>{{ $category['prpi_0_7_pct'] }}</td>
                        <td>{{ $category['prpi_gt7'] }}</td>
                        <td>{{ $category['prpi_gt7_pct'] }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="empty-row">
                    <td colspan="15">&nbsp;</td>
                </tr>
            @endforelse
            <tr class="subtotal-row">
                <td colspan="3">Total</td>
                <td>{{ $summaryTotals['apr_0_2'] ?? 0 }}</td>
                <td></td>
                <td>{{ $summaryTotals['apr_gt2'] ?? 0 }}</td>
                <td></td>
                <td>{{ $summaryTotals['popi_0_5'] ?? 0 }}</td>
                <td></td>
                <td>{{ $summaryTotals['popi_gt5'] ?? 0 }}</td>
                <td></td>
                <td>{{ $summaryTotals['prpi_0_7'] ?? 0 }}</td>
                <td></td>
                <td>{{ $summaryTotals['prpi_gt7'] ?? 0 }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('ascends.shared.inventory_analysis.purchase_order_by_item.jangka_waktu_po_pi.partials.detail-section', [
        'sectionTitle' => '1. PO yang di Approve di atas 2 hari',
        'groups' => $approvePoGroups,
        'showEmptyRow' => true,
    ])

    @include('ascends.shared.inventory_analysis.purchase_order_by_item.jangka_waktu_po_pi.partials.detail-section', [
        'sectionTitle' => '2. Jangka waktu PO ke PI yang di atas 5 hari',
        'groups' => $poPiGroups,
        'showEmptyRow' => false,
    ])

    @include('ascends.shared.inventory_analysis.purchase_order_by_item.jangka_waktu_po_pi.partials.detail-section', [
        'sectionTitle' => '3. Jangka waktu PR ke PI yang diatas 7 hari',
        'groups' => $prPiGroups,
        'showEmptyRow' => false,
    ])

    @include('ascends.shared.partials.report-footer')
</body>

</html>
