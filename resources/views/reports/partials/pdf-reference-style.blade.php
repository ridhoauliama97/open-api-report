@php
    $pageMargin = $pageMargin ?? '24mm 10mm 18mm 10mm';
    $bodyFontSize = $bodyFontSize ?? '10px';
    $titleFontSize = $titleFontSize ?? '16px';
    $subtitleMargin = $subtitleMargin ?? '2px 0 20px 0';
    $tableMarginBottom = $tableMarginBottom ?? '6px';
@endphp
<style>
    * {
        box-sizing: border-box;
    }

    @page {
        margin:
            {{ $pageMargin }}
        ;
        footer: html_reportFooter;
    }

    body {
        margin: 0;
        font-family: "Noto Serif", serif;
        font-size:
            {{ $bodyFontSize }}
        ;
        line-height: 1.2;
        color: #000;
    }

    .report-title {
        margin: 0;
        text-align: center;
        font-size:
            {{ $titleFontSize }}
        ;
        font-weight: bold;
    }

    .report-subtitle {
        margin:
            {{ $subtitleMargin }}
        ;
        text-align: center;
        font-size: 12px;
        color: #636466;
    }

    .section-title {
        margin: 10px 0 4px;
        font-size: 12px;
        font-weight: bold;
    }

    .section-rangkuman-title {
        margin: 10px 0 4px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: underline;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom:
            {{ $tableMarginBottom }}
        ;
        page-break-inside: auto;
        table-layout: fixed;
    }

    .report-table {
        border-collapse: collapse;
        border-spacing: 0;
        border: 1px solid #000;
    }

    .total-report-table {
        border-collapse: collapse;
        border-spacing: 0;
        border: 0px;
    }

    .total-report-table th,
    .total-report-table td {
        border: none !important;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 3px 4px;
        vertical-align: middle;
    }

    th {
        text-align: center;
        font-weight: bold;
        font-size: 11px;
    }

    td.center {
        text-align: center;
    }

    td.number {
        text-align: right;
        white-space: nowrap;
        font-family: "Calibri", "DejaVu Sans", sans-serif;
    }

    .headers-row th {
        font-size: 11px;
        border-top: 0;
        border-bottom: 1px solid #000;
    }

    .row-odd td {
        background: #c9d1df;
    }

    .row-even td {
        background: #eef2f8;
    }

    .report-table tbody tr.data-row td.data-cell {
        border-top: none !important;
        border-bottom: none !important;
        border-left: 1px solid #000 !important;
        border-right: 1px solid #000 !important;
    }

    .report-table tbody tr.row-last td.data-cell {
        border-bottom: 1px solid #000 !important;
    }

    .totals-row td {
        font-size: 11px;
        font-weight: bold;
        border: 1px solid #000;
    }

    .report-table tbody tr.totals-row:last-child td {
        border-bottom: 0 !important;
    }

    .table-end-line td {
        border-top: 1px solid #000 !important;
        border-right: 0 !important;
        border-bottom: 0 !important;
        border-left: 0 !important;
        padding: 0 !important;
        height: 0 !important;
        line-height: 0 !important;
        background: #fff !important;
    }

    .summary-page {
        page-break-before: always;
        margin-top: 8px;
    }

    .summary-title,
    .notes-title {
        margin: 0 0 10px;
        font-size: 11px;
        font-weight: bold;
    }

    .summary-list,
    .notes-list {
        margin: 0;
        padding-left: 18px;
        font-size: 10px;
        line-height: 1.2;
    }

    .summary-list li,
    .notes-list li {
        margin: 0 0 2px;
    }

    .notes {
        margin-top: 10px;
    }

    .notes-line {
        margin: 0 0 2px;
        font-size: 10px;
    }

    .notes-indent {
        padding-left: 28px;
    }

    @include('reports.partials.pdf-footer-table-style')
</style>
