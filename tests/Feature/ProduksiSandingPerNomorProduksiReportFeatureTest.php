<?php

namespace Tests\Feature;

use App\Services\ProduksiSandingPerNomorProduksiReportService;

class ProduksiSandingPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiSandingPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-sanding-per-nomor-produksi-pdf';
    }
}
