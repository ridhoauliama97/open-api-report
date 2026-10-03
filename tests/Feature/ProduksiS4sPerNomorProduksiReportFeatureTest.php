<?php

namespace Tests\Feature;

use App\Services\ProduksiS4sPerNomorProduksiReportService;

class ProduksiS4sPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiS4sPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-s4s-per-nomor-produksi-pdf';
    }
}
