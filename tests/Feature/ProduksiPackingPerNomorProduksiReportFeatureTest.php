<?php

namespace Tests\Feature;

use App\Services\ProduksiPackingPerNomorProduksiReportService;

class ProduksiPackingPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiPackingPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-packing-per-nomor-produksi-pdf';
    }
}
