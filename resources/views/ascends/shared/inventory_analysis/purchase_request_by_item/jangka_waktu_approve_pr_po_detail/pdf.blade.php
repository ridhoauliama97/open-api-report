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

        .section-title {
            font-weight: bold;
            font-style: italic;
            font-size: 11px;
            color: #9c111d;
            margin: 16px 0 6px 0;
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
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
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

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .family-header td {
            font-weight: bold;
            font-style: italic;
            font-size: 11px;
            color: #9c111d;
            padding: 3px 4px;
            border-bottom: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 11px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
        }

        .info-box {
            width: 100%;
            margin: 12px 0 16px 0;
            font-size: 10px;
        }

        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-box td {
            padding: 1px 4px;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $summaryGroups = $reportData['summary_groups'] ?? [];
        $summaryTotals = $reportData['summary_totals'] ?? [];
        $summaryInfo = $reportData['summary_info'] ?? [];
        $detailGroups = $reportData['detail_groups'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <div class="section-title">Summary By Approved By</div>

    @if (count($summaryGroups) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 12%">Approved By</th>
                    <th rowspan="2" style="width: 8%">Total PR<br>By Qty</th>
                    <th colspan="4" style="width: 40%">Lama Approve PR</th>
                    <th colspan="4" style="width: 40%">Lama PR Ke PO</th>
                </tr>
                <tr>
                    <th style="width: 10%">0 - 1 Hari</th>
                    <th style="width: 10%">%</th>
                    <th style="width: 10%">Diatas 1 Hari</th>
                    <th style="width: 10%">%</th>
                    <th style="width: 10%">0 - 2 Hari</th>
                    <th style="width: 10%">%</th>
                    <th style="width: 10%">Diatas 2 Hari</th>
                    <th style="width: 10%">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryGroups as $index => $group)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ $group['approved_by'] }}</td>
                        <td class="number">{{ $group['total_pr'] }}</td>
                        <td class="number">{{ $group['apr_0_1'] }}</td>
                        <td class="number">{{ $group['apr_0_1_pct'] }}%</td>
                        <td class="number">{{ $group['apr_gt1'] }}</td>
                        <td class="number">{{ $group['apr_gt1_pct'] }}%</td>
                        <td class="number">{{ $group['prpo_0_2'] }}</td>
                        <td class="number">{{ $group['prpo_0_2_pct'] }}%</td>
                        <td class="number">{{ $group['prpo_gt2'] }}</td>
                        <td class="number">{{ $group['prpo_gt2_pct'] }}%</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td class="center">Total</td>
                    <td class="number">{{ $summaryTotals['total_pr'] ?? 0 }}</td>
                    <td class="number">{{ $summaryTotals['apr_0_1'] ?? 0 }}</td>
                    <td class="number">{{ $summaryTotals['apr_0_1_pct'] ?? 0 }}%</td>
                    <td class="number">{{ $summaryTotals['apr_gt1'] ?? 0 }}</td>
                    <td class="number">{{ $summaryTotals['apr_gt1_pct'] ?? 0 }}%</td>
                    <td class="number">{{ $summaryTotals['prpo_0_2'] ?? 0 }}</td>
                    <td class="number">{{ $summaryTotals['prpo_0_2_pct'] ?? 0 }}%</td>
                    <td class="number">{{ $summaryTotals['prpo_gt2'] ?? 0 }}</td>
                    <td class="number">{{ $summaryTotals['prpo_gt2_pct'] ?? 0 }}%</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="10">Tidak ada data summary.</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="info-box">
        <table>
            <tr>
                <td class="info-label" style="width: 16%">PR Create Date :</td>
                <td style="width: 34%">{{ $summaryInfo['pr_create_date'] ?? '' }}</td>
                <td class="info-label" style="width: 16%">PR Approve By :</td>
                <td style="width: 34%">{{ $summaryInfo['pr_approve_by'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="info-label">PR Approve :</td>
                <td>{{ $summaryInfo['pr_approve_date'] ?? '' }}</td>
                <td class="info-label">PR Approval Time :</td>
                <td>{{ ($summaryInfo['pr_approval_time_days'] ?? 0) !== 0 ? ($summaryInfo['pr_approval_time_days'] ?? 0) . ' Days' : '0 Days' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Detail</div>

    @forelse ($detailGroups as $group)
        <div style="font-weight: bold; font-size: 11px; margin: 14px 0 4px 0; color: #9c111d;">
            PR Number : {{ $group['pr_number'] }}
        </div>

        <div class="info-box">
            <table>
                <tr>
                    <td class="info-label" style="width: 14%">PR Create Date :</td>
                    <td style="width: 36%">{{ $group['pr_create_date_display'] }}</td>
                    <td class="info-label" style="width: 14%">PR Approve By :</td>
                    <td style="width: 36%">{{ $group['pr_approve_by'] }}</td>
                </tr>
                <tr>
                    <td class="info-label">PR Approve :</td>
                    <td>{{ $group['pr_approve_date_display'] }}</td>
                    <td class="info-label">PR Approval Time :</td>
                    <td>{{ $group['pr_approval_time'] }}</td>
                </tr>
            </table>
        </div>

        @if (count($group['items']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%">No</th>
                        <th style="width: 22%">Item Name</th>
                        <th style="width: 6%">Qty<br>PR</th>
                        <th style="width: 6%">Qty<br>PO</th>
                        <th style="width: 6%">On<br>Order</th>
                        <th style="width: 14%">PO Number</th>
                        <th style="width: 7%">PO<br>Create</th>
                        <th style="width: 7%">PO<br>Approv</th>
                        <th style="width: 7%">Total<br>Time</th>
                        <th style="width: 7%">Status</th>
                        <th style="width: 6%">Sisa</th>
                        <th style="width: 9%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['items'] as $row)
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $row['no'] }}</td>
                            <td>{{ $row['item_name'] ?? '' }}</td>
                            <td class="number nowrap">{{ $row['qty_pr'] ?? '-' }}</td>
                            <td class="number nowrap">{{ $row['qty_po'] ?? '-' }}</td>
                            <td class="number nowrap">{{ $row['on_order'] ?? '-' }}</td>
                            <td class="center nowrap">{{ $row['po_number'] ?? ' - ' }}</td>
                            <td class="number nowrap">{{ $row['po_create'] ?? '-' }}</td>
                            <td class="number nowrap">{{ $row['po_approv'] ?? '-' }}</td>
                            <td class="number nowrap">{{ $row['total_time'] ?? '-' }}</td>
                            <td class="center nowrap">{{ $row['status'] ?? '-' }}</td>
                            <td class="number nowrap">{{ $row['sisa'] ?? '-' }}</td>
                            <td>{{ $row['keterangan'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <table class="data-table">
                <tbody>
                    <tr class="empty-row">
                        <td colspan="12">Tidak ada item.</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="12">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('ascends.shared.partials.report-footer')
</body>

</html>
