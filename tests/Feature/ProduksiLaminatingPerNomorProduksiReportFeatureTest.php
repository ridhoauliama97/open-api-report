<?php

namespace Tests\Feature;

use App\Services\ProduksiLaminatingPerNomorProduksiReportService;

class ProduksiLaminatingPerNomorProduksiReportFeatureTest extends ProduksiPerNomorProduksiReportTestCase
{
    protected function service(): object
    {
        return new ProduksiLaminatingPerNomorProduksiReportService;
    }

    protected function pdfView(): string
    {
        return 'reports.proses-produksi.produksi-laminating-per-nomor-produksi-pdf';
    }
}
