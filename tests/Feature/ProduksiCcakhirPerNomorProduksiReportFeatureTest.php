<?php

namespace Tests\Feature;

use App\Services\ProduksiPerNomorProduksiReportService;

class ProduksiCcakhirPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-per-nomor-produksi-pdf';
    }
}
