<?php

namespace Tests\Feature;

use App\Services\ProduksiFjPerNomorProduksiReportService;

class ProduksiFjPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiFjPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-fj-per-nomor-produksi-pdf';
    }
}
