<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Sales\SuratJalanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsSuratJalanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: surat-jalan.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.sales.surat_jalan.panjang-pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_documents'] ?? null) === 1
                    && ($data['reportData']['printed_by'] ?? null) === 'indah'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/sales/surat-jalan/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('surat-jalan.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (RU) - Panjang');
    }

    public function test_ascend_test_upload_form_can_preview_surat_jalan_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: surat-jalan.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.sales.surat_jalan.panjang-pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Surat Jalan (RU)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_module' => 'sales',
            'report_type' => 'surat_jalan_panjang',
            'xml_file' => UploadedFile::fake()->createWithContent('surat-jalan.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (RU) - Panjang');
    }

    public function test_internal_ascend_api_can_render_normal_surat_jalan_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: surat-jalan.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.sales.surat_jalan.normal-pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_documents'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/sales/surat-jalan/normal/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('surat-jalan.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (RU) - Normal');
    }

    public function test_internal_ascend_api_can_render_gsu_panjang_surat_jalan_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: surat-jalan-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.sales.surat_jalan.panjang-pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_documents'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/gsu/sales/surat-jalan/panjang/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('surat-jalan-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (GSU) - Panjang');
    }

    public function test_internal_ascend_api_can_render_gsu_normal_surat_jalan_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: surat-jalan-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.sales.surat_jalan.normal-pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_documents'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/gsu/sales/surat-jalan/normal/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('surat-jalan-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (GSU) - Normal');
    }

    public function test_ascend_test_upload_form_can_preview_gsu_surat_jalan_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: surat-jalan-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.sales.surat_jalan.panjang-pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_documents'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'GSU',
            'report_module' => 'sales',
            'report_type' => 'gsu_surat_jalan_panjang',
            'xml_file' => UploadedFile::fake()->createWithContent('surat-jalan-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (GSU) - Panjang');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/sales/surat-jalan/pdf',
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/xml',
                    'HTTP_ACCEPT' => 'application/pdf',
                ],
                $xml
            )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan (RU)');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(SuratJalanReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(SuratJalanReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/sales/surat-jalan/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_surat_jalan_parser_groups_invoice_rows_into_items(): void
    {
        $reportData = app(SuratJalanReportService::class)
            ->buildReportDataFromXml($this->salesInvoiceXml(), 'test xml');

        $this->assertSame('Surat Jalan (RU)', $reportData['title']);
        $this->assertSame(3, $reportData['total_rows']);
        $this->assertSame(1, $reportData['total_documents']);
        $this->assertSame([
            'No',
            'Kode Barang',
            'Nama Barang',
            'Qty',
            'Satuan',
        ], $reportData['headers']);

        $document = $reportData['documents'][0];
        $this->assertSame('DO/07/18/0126', $document['document_number']);
        $this->assertSame('SI/07/18/0126', $document['invoice_number']);
        $this->assertSame('KEDAUNG', $document['customer_name']);
        $this->assertSame('INV.2605-164', $document['vehicle_no']);
        $this->assertSame('28-Mei-26', $document['delivery_date']);
        $this->assertSame('28-05-26', $document['delivery_date_numeric']);
        $this->assertSame('SO/07/18/0126', $document['sales_order_number']);
        $this->assertSame('JTR', $document['shipper']);
        $this->assertSame('Jumroh', $document['shipping_name']);
        $this->assertSame(
            'Jalan Pamarayan Tambak, RT.014/RW.003, KpKedaung, Desa Blokang, Bandung,BANDUNG, KAB. SERANG, BANTEN',
            $document['shipping_address']
        );
        $this->assertSame(3, $document['item_count']);
        $this->assertSame(15.0, $document['total_quantity']);
        $this->assertSame('2.1.5.1.05.01', $document['items'][0]['item_code']);
        $this->assertSame('KOBOKAN 12CM W/MH (18 LSN)', $document['items'][0]['item_name']);
        $this->assertSame('5', $document['items'][0]['qty']);
        $this->assertSame('DUS', $document['items'][0]['uom']);
    }

    public function test_surat_jalan_pdf_renders_expected_sections(): void
    {
        $reportData = app(SuratJalanReportService::class)
            ->buildReportDataFromXml($this->salesInvoiceXml(), 'test xml');

        $html = view('ascends.ru.sales.surat_jalan.panjang-pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Surat Jalan (RU)', $html);
        $this->assertStringContainsString('Tagih Ke', $html);
        $this->assertStringContainsString('Kirim Ke', $html);
        $this->assertStringContainsString('Pengirim', $html);
        $this->assertStringContainsString('No DO', $html);
        $this->assertStringContainsString('Tgl Surat Jalan', $html);
        $this->assertStringContainsString('No SO', $html);
        $this->assertStringContainsString('No Kendaraan', $html);
        $this->assertStringContainsString('Salesman', $html);
        $this->assertStringContainsString('Qty Besar', $html);
        $this->assertStringContainsString('Jmlh Qty Kecil', $html);
        $this->assertStringContainsString('Jumlah Item', $html);
        $this->assertStringContainsString('Jumlah Item = 3', $html);
        $this->assertStringContainsString('Penjualan Kayu BJ Rambung FJLB GRADE C/C', $html);
        $this->assertStringContainsString('(L-300)', $html);
        $this->assertStringNotContainsString('By.Admin 5 rb', $html);
        $this->assertStringContainsString('Note : “Jika ada kerusakan sewaktu menerima barang', $html);
        $this->assertStringContainsString('KEDAUNG', $html);
        $this->assertStringContainsString('KOBOKAN 12CM W/MH (18 LSN)', $html);
        $this->assertStringContainsString('Petugas Gudang', $html);
        $this->assertStringContainsString('Adm. Penjualan', $html);
        $this->assertStringContainsString('Ka. Supplier Service', $html);
        $this->assertStringContainsString('Diantar oleh', $html);
        $this->assertStringContainsString('Supir', $html);
        $this->assertStringNotContainsString('Kernet', $html);
        $this->assertStringContainsString('Diterima Oleh', $html);
        $this->assertStringContainsString('Dicetak oleh: indah', $html);
        $this->assertStringContainsString('DO/07/18/0126', $html);
    }

    public function test_ascend_test_upload_form_lists_surat_jalan_report(): void
    {
        $this->get('/ascend-test')
            ->assertOk()
            ->assertSee('Sales')
            ->assertSee('Surat Jalan (RU) - Panjang')
            ->assertSee('Surat Jalan (RU) - Normal')
            ->assertSee('Surat Jalan (GSU) - Panjang')
            ->assertSee('Surat Jalan (GSU) - Normal');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'title' => 'Surat Jalan (RU)',
            'printed_at' => now()->toDateTimeString(),
            'printed_by' => 'indah',
            'headers' => [
                'No',
                'Kode Barang',
                'Nama Barang',
                'Qty',
                'Satuan',
            ],
            'rows' => [['No Surat Jalan' => 'DO/07/18/0126']],
            'documents' => [
                [
                    'document_number' => 'DO/07/18/0126',
                    'invoice_number' => 'SI/07/18/0126',
                    'items' => [['item_code' => '2.1.5.1.05.01']],
                ],
            ],
            'total_rows' => 1,
            'total_documents' => 1,
        ];
    }

    private function salesInvoiceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Invoice>
    <InvoiceNumber>SI/07/18/0126</InvoiceNumber>
    <InvoiceID>4443</InvoiceID>
    <InvoiceDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDate>
    <Remarks>Penjualan Kayu BJ Rambung FJLB GRADE C/C (L-300) By.Admin 5 rb + By.Kebersihan 5 rb</Remarks>
    <Createdby>indah</Createdby>
    <CustomerName>KEDAUNG</CustomerName>
    <AddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</AddressLine1>
    <City>PADANG</City>
    <BillingAddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</BillingAddressLine1>
    <BillingCity>PADANG</BillingCity>
    <DONo>DO/07/18/0126</DONo>
    <SONumber>SO/07/18/0126</SONumber>
    <InvoiceDueDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDueDate>
    <POSDeliveryDateTime>2026-05-28T14:46:57.5+07:00</POSDeliveryDateTime>
    <SalesPersonName>SIMSON</SalesPersonName>
    <VehicleNo>INV.2605-164</VehicleNo>
    <ShipperName>JTR</ShipperName>
    <DropShipAddress>Jumroh
Jalan Pamarayan Tambak, RT.014/RW.003, KpKedaung, Desa Blokang, Bandung,BANDUNG, KAB. SERANG, BANTEN</DropShipAddress>
    <ItemCode>2.1.5.1.05.01</ItemCode>
    <ItemName>KOBOKAN 12CM W/MH (18 LSN)</ItemName>
    <Quantity>5.0000</Quantity>
    <UOMCode>DUS</UOMCode>
  </Invoice>
  <Invoice>
    <InvoiceNumber>SI/07/18/0126</InvoiceNumber>
    <InvoiceID>4443</InvoiceID>
    <InvoiceDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDate>
    <Createdby>indah</Createdby>
    <CustomerName>KEDAUNG</CustomerName>
    <BillingAddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</BillingAddressLine1>
    <BillingCity>PADANG</BillingCity>
    <DONo>DO/07/18/0126</DONo>
    <InvoiceDueDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDueDate>
    <SalesPersonName>SIMSON</SalesPersonName>
    <VehicleNo>INV.2605-164</VehicleNo>
    <ItemCode>2.1.5.1.01.03</ItemCode>
    <ItemName>BASKOM BIASA 40 CM M/MH GSU (2 LSN)</ItemName>
    <Quantity>5.0000</Quantity>
    <UOMCode>DUS</UOMCode>
  </Invoice>
  <Invoice>
    <InvoiceNumber>SI/07/18/0126</InvoiceNumber>
    <InvoiceID>4443</InvoiceID>
    <InvoiceDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDate>
    <Createdby>indah</Createdby>
    <CustomerName>KEDAUNG</CustomerName>
    <BillingAddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</BillingAddressLine1>
    <BillingCity>PADANG</BillingCity>
    <DONo>DO/07/18/0126</DONo>
    <InvoiceDueDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDueDate>
    <SalesPersonName>SIMSON</SalesPersonName>
    <VehicleNo>INV.2605-164</VehicleNo>
    <ItemCode>2.1.5.1.07.07</ItemCode>
    <ItemName>NAMPAN 60 CM DECO (1 1/2 LSN)</ItemName>
    <Quantity>5.0000</Quantity>
    <UOMCode>DUS</UOMCode>
  </Invoice>
</NewDataSet>
XML;
    }
}
