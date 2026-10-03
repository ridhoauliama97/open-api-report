<?php

namespace Tests\Feature;

use App\Services\ProduksiMouldingPerNomorProduksiReportService;

class ProduksiMouldingPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiMouldingPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-moulding-per-nomor-produksi-pdf';
    }
}
