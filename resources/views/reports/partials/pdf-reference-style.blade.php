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
        margin: 0;
        padding: 0;
    }

    body {
        font-family: "Noto Serif", serif;
        font-size: 10px;
        line-height: 1.2;
        color: #000;
        margin: 0;
        padding: 0;
    }

    .report-companyTitle {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin: 0 0 4px;
    }

    .report-title {
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        margin: 0;
    }

    .report-subtitle {
        font-size: 12px;
        color: #636466;
        text-align: center;
        margin: 2px 0 20px;
    }

    .section-title {
        margin: 14px 0 6px 0;
        font-size: 12px;
        font-weight: bold;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        line-height: inherit;
    }

    .headers-row th {
        font-weight: bold;
    }

    .totals-row td {
        font-weight: bold;
    }

    .data-table {
        border: 1px solid #000;
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
    }

    .data-table th,
    .data-table td {
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        padding: 1px 2px;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .data-table th {
        font-weight: bold;
    }

    .section-header td {
        font-weight: bold;
        font-style: italic;
        color: #9c111d;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        padding-left: 4px;
    }

    .sub-section-header td {
        font-weight: bold;
        color: #9c111d;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        padding-left: 4px;
    }

    .item-row td {
        padding-left: 4px;
    }

    .row-odd td {
        background-color: #c9d1df;
    }

    .row-even td {
        background-color: #eef2f8;
    }

    .subtotal-row td {
        font-weight: bold;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
    }

    .grand-total-row td {
        font-weight: bold;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
    }

    .empty-row td {
        font-style: italic;
        font-weight: bold;
        color: #9c111d;
        background-color: #c9d1df;
    }

    .number {
        text-align: right;
        white-space: nowrap;
    }

    .nowrap {
        white-space: nowrap;
    }

    .number-negative {
        color: #9c111d;
    }
</style>