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
            margin: 10mm 10mm 10mm 10mm;
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

        .invoice-page.page-break-after {
            page-break-after: always;
        }

        .top-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 6px;
        }

        .top-grid td {
            vertical-align: top;
            border: none;
        }

        .top-block-left {
            width: 44%;
        }

        .top-block-middle {
            width: 28%;
        }

        .top-block-right {
            width: 28%;
        }

        .address-table,
        .invoice-info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .address-table td,
        .invoice-info-table td {
            padding: 1px 2px;
            vertical-align: top;
            border: 0;
        }

        .address-label,
        .invoice-info-label {
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
        }

        .top-grid .address-separator,
        .top-grid .invoice-info-separator {
            padding-left: 0;
            padding-right: 0;
            text-align: center;
            font-weight: bold;
            white-space: nowrap;
        }

        .top-grid .address-value {
            border: 0;
            min-height: 34px;
            padding-left: 0;
            line-height: 1.15;
            white-space: normal;
            word-wrap: break-word;
        }

        .customer-name {
            display: block;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.15;
            white-space: normal;
            word-wrap: break-word;
        }

        .shipper-name {
            font-weight: bold;
            font-size: 12px;
        }

        .top-grid .address-value.empty {
            min-height: 24px;
        }

        .address-name {
            font-weight: bold;
            font-size: 10px;
        }

        .invoice-info-value {
            white-space: nowrap;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            margin: 10px 0 10px 0;
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
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .total-row td {
            border-top: 1px solid #000;
            font-size: 11px;
            font-weight: bold;
            height: auto;
        }

        .bottom-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 6px;
        }

        .keterangan-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
        }

        .keterangan-table td {
            height: 62px;
            min-height: 62px;
            padding: 4px 6px;
            border: none;
            font-size: 10px;
            font-style: italic;
            font-weight: normal;
            line-height: 1.25;
            vertical-align: top;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .keterangan-label {
            display: block;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .keterangan-value td {
            font-size: 10px;
            white-space: pre-wrap;
            font-style: italic;
            margin: 0;
            padding: 0;
        }

        .sig-block {
            width: 60%;
            max-width: 220px;
            border-collapse: collapse;
            table-layout: fixed;
            border: 0;
        }

        .sig-block td {
            border: 0;
            padding: 0;
            text-align: left;
        }

        .sig-hormat {
            font-size: 11px;
            height: 18px;
            vertical-align: top;
        }

        .sig-space {
            height: 64px;
            line-height: 64px;
            font-size: 1px;
        }

        .sig-line {
            border-top: 1px solid #000;
            height: 1px;
            line-height: 1px;
            font-size: 1px;
        }

        /* .sig-tanggal {
            font-size: 11px;
            padding-top: 4px;
        } */

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .summary-table td {
            padding: 3px 4px;
            border: none;
            font-weight: 600;
            font-size: 11px;
        }

        .summary-table td:last-child {
            text-align: right;
            font-family: 'Calibri', 'Dejavu Sans', sans-serif;
        }

        .grand-total-row td {
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .bottom-block-left {
            width: 45%;
        }

        .bottom-block-middle {
            width: 10%;
        }

        .bottom-block-right {
            width: 45%;
        }

        .terbilang-wrap {
            width: 100%;
            text-align: left;
        }

        .terbilang-label-t {
            font-size: 11px;
            white-space: nowrap;
            padding-right: 6px;
            vertical-align: bottom;
        }

        .terbilang-value {
            font-size: 10px;
            font-style: italic;
            white-space: nowrap;
            vertical-align: bottom;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            font-family: 'Calibri', 'Dejavu Sans', sans-serif;
            white-space: nowrap;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .muted {
            color: #333;
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
        }

        .footer-laporan {
            width: 100%;
            font-size: 9px;
            font-style: italic;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .footer-laporan td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
            background: transparent;
        }

        .middle-page-footer {
            position: fixed;
            top: 138.5mm;
            left: 0;
            right: 0;
            width: 100%;
        }
    </style>
</head>

<body>
    @php
        $invoices = $reportData["invoices"] ?? [];
        $company = strtoupper(trim((string) ($reportData["company"] ?? "RU")));
        $footerInvoiceNumber = (string) ($invoices[0]["invoice_number"] ?? "");
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale("id")
            ->translatedFormat("d-M-y H:i");
        $generatedByName = trim((string) ($reportData["printed_by"] ?? ""));
        $money = static fn($value): string => number_format((float) $value, 0, ".", ",");
        $quantity = static function ($value): string {
            $formatted = number_format((float) $value, 4, ".", ",");

            return rtrim(rtrim($formatted, "0"), ".");
        };
    @endphp

    @forelse ($invoices as $invoice)
        @php
            $items = $invoice["items"] ?? [];
            $tagihAddress =
                (string) ($invoice["billing_address"] ?? "" ?: $invoice["customer_address"] ?? "");
            $kirimAddress = (string) ($invoice["shipping_address"] ?? "" ?: $tagihAddress);
            $terbilang = trim((string) ($invoice["net_total_words"] ?? ""));
            $itemCount = (int) ($invoice["item_count"] ?? count($items));
            $remarksText = trim((string) ($invoice["remarks"] ?? ""));
            if ($remarksText !== "") {
                $remarksText = preg_replace("/\s+/", " ", $remarksText) ?? $remarksText;
                $remarksText = preg_replace("/\s*(\(L-300\))\s*/", "\n$1\n", $remarksText) ?? $remarksText;
                $remarksText = preg_replace("/(\d+)\s+rb\+/", "$1rb+", $remarksText) ?? $remarksText;
                $remarksText = rtrim($remarksText, ". \t\n\r\0\x0B");
            }
            if ($terbilang !== "" && stripos($terbilang, "rupiah") === false) {
                $terbilang .= " Rupiah";
            }
        @endphp

        <div class="invoice-page {{ $loop->last ? "" : "page-break-after" }}">
            <h1 class="report-title">Sales Invoices ({{ $company }})</h1>
            <p class="report-subtitle"></p>

            <table class="top-grid">
                <colgroup>
                    <col style="width: 54%;">
                    <col style="width: 23%;">
                    <col style="width: 23%;">
                </colgroup>
                <tr>
                    <td class="top-block-left">
                        <table class="address-table">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 3%;">
                                <col style="width: 77%;">
                            </colgroup>
                            <tr>
                                <td class="address-label">Tagih Ke</td>
                                <td class="address-separator">:</td>
                                <td class="address-value">
                                    <span
                                        class="customer-name">&nbsp;{{ (string) ($invoice["customer_name"] ?? "") }}</span>
                                    - {{ $tagihAddress }}
                                </td>
                            </tr>
                            <tr>
                                <td class="address-label">Kirim Ke</td>
                                <td class="address-separator">:</td>
                                <td class="address-value">
                                    <span class="customer-name">
                                        &nbsp;{{ (string) ($invoice["customer_name"] ?? "") }}
                                    </span>
                                    - {{ $kirimAddress }}
                                </td>
                            </tr>
                            <tr>
                                <td class="address-label">Pengirim</td>
                                <td class="address-separator">:</td>
                                <td class="address-value empty">
                                    <span class="shipper-name">{{ (string) ($invoice["shipper"] ?? "") }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="top-block-middle">
                        <table class="invoice-info-table">
                            <colgroup>
                                <col style="width: 55%;">
                                <col style="width: 5%;">
                                <col style="width: 40%;">
                            </colgroup>
                            <tr>
                                <td class="invoice-info-label">No SI</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value">{{ (string) ($invoice["invoice_number"] ?? "") }}
                                </td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">No Kendaraan</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value">{{ (string) ($invoice["vehicle_no"] ?? "") }}</td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">Salesman</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value">{{ (string) ($invoice["salesman"] ?? "") }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="top-block-right">
                        <table class="invoice-info-table">
                            <colgroup>
                                <col style="width: 50%;">
                                <col style="width: 5%;">
                                <col style="width: 45%;">
                            </colgroup>
                            <tr>
                                <td class="invoice-info-label">Tanggal Faktur</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">
                                    {{ (string) ($invoice["invoice_date"] ?? "") }}
                                </td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">Tanggal Kirim</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">
                                    {{ (string) ($invoice["delivery_date"] ?? "") }}
                                </td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">No DO</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">{{ (string) ($invoice["do_number"] ?? "") }}
                                </td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">Jatuh Tempo</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">
                                    {{ (string) ($invoice["delivery_date"] ?? "") }}
                                    (<strong>{{ (string) ($invoice["payment_term"] ?? "") }} Hari</strong>)
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 11%;">Kode Barang</th>
                        <th style="width: 37%;">Nama Barang</th>
                        <th style="width: 12%;">Qty</th>
                        <th style="width: 14%;">Harga (@)</th>
                        <th style="width: 8%;">Diskon (%)</th>
                        <th style="width: 14%;">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="item-row {{ $loop->odd ? "row-odd" : "row-even" }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            <td class="center">{{ (string) ($item["item_code"] ?? "") }}</td>
                            <td>{{ (string) ($item["item_name"] ?? "") }}</td>
                            <td class="right">
                                {{ (string) ($item["qty"] ?? "") }} {{ (string) ($item["uom"] ?? "") }}
                            </td>
                            <td class="right">Rp. {{ (string) ($item["price"] ?? "") }}</td>
                            <td class="center">{{ (string) ($item["discount"] ?? "") }}</td>
                            <td class="right">Rp. {{ (string) ($item["line_total"] ?? "") }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="3" class="center">Total</td>
                        <td class="right">
                            {{ $quantity((float) ($invoice["total_quantity"] ?? 0)) }}
                            {{ (string) ($items[0]["uom"] ?? "" ?: "") }}
                        </td>
                        <td></td>
                        <td></td>
                        <td class="right">Rp. {{ $money((float) ($invoice["subtotal"] ?? 0)) }}</td>
                    </tr>
                </tbody>
            </table>


            <table class="bottom-grid">
                {{-- Row 1: Keterangan | Summary --}}
                <tr>
                    <td class="bottom-block-left">
                        <p class="keterangan-label">Keterangan : </p>
                        <table class="keterangan-table">
                            <tr>
                                <td class="keterangan-value">
                                    @if ($remarksText !== "")
                                        “{!! nl2br(e($remarksText)) !!}.”
                                    @endif
                                </td>
                            </tr>
                        </table>
                        {{-- <p class="keterangan-value">“{{ (string) ($invoice["remarks"] ?? "") }}.”</p> --}}
                    </td>
                    <td class="bottom-block-middle"></td>
                    <td class="bottom-block-right">
                        <table class="summary-table">
                            <tr>
                                <td>Jmlh Item :</td>
                                <td class="right">{{ $itemCount }} Item</td>
                            </tr>
                            <tr>
                                <td>Total :</td>
                                <td class="right">Rp. {{ $money((float) ($invoice["subtotal"] ?? 0)) }}</td>
                            </tr>
                            <tr>
                                <td>Diskon :</td>
                                <td class="right">Rp. {{ $money((float) ($invoice["discount"] ?? 0)) }}</td>
                            </tr>
                            <tr class="grand-total-row">
                                <td>Grand Total :</td>
                                <td class="right">Rp. {{ $money((float) ($invoice["net_total"] ?? 0)) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                {{-- Row 2: Signature (kiri) | Terbilang (kanan) — sejajar via vertical-align: bottom --}}
                <tr>
                    <td class="bottom-block-left">
                        <table class="sig-block">
                            <tr>
                                <td class="sig-hormat" style="text-align: center">Hormat Kami,</td>
                            </tr>
                            <tr>
                                <td class="sig-space">&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: bottom; border-top: 1px solid #000; text-align: center;">
                                    {{ $generatedByName }}
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="bottom-block-middle"></td>
                    <td class="bottom-block-right" style="vertical-align: bottom;">
                        @if ($terbilang !== "")
                            <table class="terbilang-wrap">
                                <tr>
                                    <td class="terbilang-label-t">Terbilang : <br>
                                        <p class="terbilang-value">“{{ $terbilang }}.”</p>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    <div class="middle-page-footer">
        <table class="footer-laporan">
            <tr>
                <td width="33%">
                    Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}
                </td>
                <td width="33%" align="center">{{ $footerInvoiceNumber }}</td>
                <td width="33%" style="text-align: right;">Halaman {PAGENO} dari {nbpg}</td>
            </tr>
        </table>
    </div>
</body>

</html>
