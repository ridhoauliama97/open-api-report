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

        .document-page.page-break-after {
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

        .data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .item-row td {
            vertical-align: top;
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
            height: 48px;
            min-height: 48px;
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

        .keterangan-value {
            white-space: normal;
        }

        .keterangan-label {
            display: block;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.2;
            margin-bottom: 6px;
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

        .item-count-line {
            margin-bottom: 4px;
            font-size: 10px;
            text-align: left;
        }

        .warning {
            margin-top: 6px;
            border: 0;
            padding: 3px 0;
            font-size: 10px;
            font-style: italic;
            font-weight: bold;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 8px;
        }

        .signature-table td {
            border: 0;
            text-align: center;
            vertical-align: top;
            padding: 0 4px;
            line-height: 1.15;
        }

        .signature-space td {
            height: 46px;
            line-height: 46px;
            font-size: 1px;
        }

        .signature-line {
            width: 82%;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-line td {
            height: 1px;
            padding: 0;
            border-top: 1px solid #000;
            font-size: 1px;
            line-height: 1px;
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
        }
    </style>
</head>

<body>
    @php
        $documents = $reportData['documents'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $quantity = static function ($value): string {
            $formatted = number_format((float) $value, 4, '.', ',');

            return rtrim(rtrim($formatted, '0'), '.');
        };
    @endphp

    @forelse ($documents as $document)
        @php
            $items = $document['items'] ?? [];
            $tagihAddress = (string) ($document['billing_address'] ?? '' ?: $document['customer_address'] ?? '');
            $kirimAddress = (string) ($document['shipping_address'] ?? '' ?: $tagihAddress);
            $itemCount = (int) ($document['item_count'] ?? count($items));
            $customerName = strtoupper(trim((string) ($document['customer_name'] ?? '')));
            $warehouseHeadLabel = in_array($customerName, ['GSU', 'UTAMA CORP', 'RATIMDO UTAMA'], true)
                ? 'Ka. Div Gudang'
                : 'Ka. Supplier Service';
            $suratJalanRemarks = trim((string) ($document['remarks'] ?? ''));
            if ($suratJalanRemarks !== '') {
                $suratJalanRemarks = preg_replace('/\s+/', ' ', $suratJalanRemarks) ?? $suratJalanRemarks;
                $suratJalanRemarks = preg_replace('/\s*(\(L-300\))\s*/', "\n$1", $suratJalanRemarks) ?? $suratJalanRemarks;
                $suratJalanRemarks = preg_replace('/(\(L-300\)).*$/s', '$1', $suratJalanRemarks) ?? $suratJalanRemarks;
                $suratJalanRemarks = rtrim($suratJalanRemarks, ". \t\n\r\0\x0B");
            }
        @endphp

        <div class="document-page {{ $loop->last ? '' : 'page-break-after' }}">
            <h1 class="report-title">{{ $reportData['title'] ?? 'Surat Jalan (RU)' }}</h1>
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
                                        class="customer-name">&nbsp;{{ (string) ($document['customer_name'] ?? '') }}</span>
                                    - {{ $tagihAddress }}
                                </td>
                            </tr>
                            <tr>
                                <td class="address-label">Kirim Ke</td>
                                <td class="address-separator">:</td>
                                <td class="address-value">
                                    <span
                                        class="customer-name">&nbsp;{{ (string) ($document['customer_name'] ?? '') }}</span>
                                    - {{ $kirimAddress }}
                                </td>
                            </tr>
                            <tr>
                                <td class="address-label">Pengirim</td>
                                <td class="address-separator">:</td>
                                <td class="address-value empty">
                                    <span class="shipper-name">{{ (string) ($document['shipper'] ?? '') }}</span>
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
                                <td class="invoice-info-label">No DO</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value">{{ (string) ($document['document_number'] ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">No SO</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value">{{ (string) ($document['sales_order_number'] ?? '') }}
                                </td>
                            </tr>

                        </table>
                    </td>
                    <td class="top-block-right">
                        <table class="invoice-info-table">
                            <colgroup>
                                <col style="width: 52%;">
                                <col style="width: 5%;">
                                <col style="width: 43%;">
                            </colgroup>
                            <tr>
                                <td class="invoice-info-label">Tgl Surat Jalan</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">{{ (string) ($document['delivery_date'] ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">No Kendaraan</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">{{ (string) ($document['vehicle_no'] ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="invoice-info-label">Salesman</td>
                                <td class="invoice-info-separator">:</td>
                                <td class="invoice-info-value right">{{ (string) ($document['salesman'] ?? '') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 13%;">Kode Barang</th>
                        <th style="width: 47%;">Nama Barang</th>
                        <th style="width: 15%;">Qty Besar</th>
                        <th style="width: 17%;">Jmlh Qty Kecil</th>
                        <th style="width: 4%;">*</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="item-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            <td class="center nowrap">{{ (string) ($item['item_code'] ?? '') }}</td>
                            <td>{{ (string) ($item['item_name'] ?? '') }}</td>
                            <td class="right">{{ (string) ($item['qty'] ?? '') }} {{ (string) ($item['uom'] ?? '') }}</td>
                            <td class="right">{{ (string) ($item['qty'] ?? '') }} {{ (string) ($item['uom'] ?? '') }}</td>
                            <td class="center">*</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="6" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="bottom-grid">
                <tr>
                    <td class="bottom-block-left">
                        <div class="item-count-line">Jumlah Item = {{ $itemCount }}</div>
                        <p class="keterangan-label">Keterangan : </p>
                        <table class="keterangan-table">
                            <tr>
                                <td class="keterangan-value">“{!! nl2br(e($suratJalanRemarks)) !!}”</td>
                            </tr>
                        </table>
                    </td>
                    <td class="bottom-block-middle"></td>
                    <td class="bottom-block-right"></td>
                </tr>
            </table>

            <div class="warning">
                Note : “Jika ada kerusakan sewaktu menerima barang, mohon segera konfirmasi ke bagian marketing
                selambat-lambatnya 3 hari.”
            </div>

            <table class="signature-table">
                <tr>
                    <td colspan="2" style="width: 50%;">Petugas Gudang</td>
                    <td style="width: 25%;">Diantar oleh</td>
                    <td style="width: 25%;">Diterima Oleh</td>
                </tr>
                <tr>
                    <td style="width: 25%;">Adm. Penjualan</td>
                    <td style="width: 25%;">{{ $warehouseHeadLabel }}</td>
                    <td style="width: 25%;">Supir</td>
                    <td style="width: 25%;"></td>
                </tr>
                <tr class="signature-space">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    <htmlpagefooter name="reportFooter">
        <table class="footer-laporan">
            <tr>
                <td width="50%">
                    Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}
                </td>
                <td width="50%" style="text-align: right;">Halaman {PAGENO} dari {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
</body>

</html>
