<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Sales\SalesInvoiceReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsSalesInvoiceReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: sales-invoice.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.sales.sales_invoice.panjang-pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_invoices'] ?? null) === 1
                && ($data['reportData']['printed_by'] ?? null) === 'indah'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/sales/sales-invoice/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('sales-invoice.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoice (RU)');
    }

    public function test_ascend_test_upload_form_can_preview_sales_invoice_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: sales-invoice.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.sales.sales_invoice.panjang-pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['title'] ?? null) === 'Sales Invoice (RU)'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_module' => 'sales',
            'report_type' => 'sales_invoice_panjang',
            'xml_file' => UploadedFile::fake()->createWithContent('sales-invoice.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoice (RU) - Panjang');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
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

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/sales/sales-invoice/pdf',
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

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoice (RU)');
    }

    public function test_internal_ascend_api_can_render_normal_sales_invoice_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: sales-invoice.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.sales.sales_invoice.normal-pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_invoices'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/sales/sales-invoice/normal/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('sales-invoice.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoice (RU) - Normal');
    }

    public function test_internal_ascend_api_can_render_gsu_panjang_sales_invoice_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: sales-invoice-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.sales.sales_invoice.panjang-pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_invoices'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/gsu/sales/sales-invoice/panjang/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('sales-invoice-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoices (GSU) - Panjang');
    }

    public function test_internal_ascend_api_can_render_gsu_normal_sales_invoice_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: sales-invoice-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.sales.sales_invoice.normal-pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_invoices'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/gsu/sales/sales-invoice/normal/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('sales-invoice-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoices (GSU) - Normal');
    }

    public function test_ascend_test_upload_form_can_preview_gsu_sales_invoice_pdf(): void
    {
        $xml = $this->salesInvoiceXml();

        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: sales-invoice-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.sales.sales_invoice.panjang-pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_invoices'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesInvoiceReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'GSU',
            'report_module' => 'sales',
            'report_type' => 'gsu_sales_invoice_panjang',
            'xml_file' => UploadedFile::fake()->createWithContent('sales-invoice-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Sales Invoices (GSU) - Panjang');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(SalesInvoiceReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(SalesInvoiceReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/sales/sales-invoice/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_sales_invoice_parser_groups_invoice_rows_into_items(): void
    {
        $reportData = app(SalesInvoiceReportService::class)
            ->buildReportDataFromXml($this->salesInvoiceXml(), 'test xml');

        $this->assertSame('Sales Invoice (RU)', $reportData['title']);
        $this->assertSame(3, $reportData['total_rows']);
        $this->assertSame(1, $reportData['total_invoices']);
        $this->assertSame([
            'No',
            'Kode Barang',
            'Nama Barang',
            'QTY',
            '@Harga (Rp)',
            'Disc %',
            'Nilai (Rp)',
        ], $reportData['headers']);

        $invoice = $reportData['invoices'][0];
        $this->assertSame('SI/07/18/0126', $invoice['invoice_number']);
        $this->assertSame('29-Mei-26', $invoice['invoice_date']);
        $this->assertSame('28-Mei-26', $invoice['delivery_date']);
        $this->assertSame('29-Mei-26', $invoice['due_date']);
        $this->assertSame('DO/07/18/0126', $invoice['do_number']);
        $this->assertSame('JTR', $invoice['shipper']);
        $this->assertSame('Jumroh', $invoice['shipping_name']);
        $this->assertSame(
            'Jalan Pamarayan Tambak, RT.014/RW.003, KpKedaung, Desa Blokang, Bandung,BANDUNG, KAB. SERANG, BANTEN',
            $invoice['shipping_address']
        );
        $this->assertSame(3, $invoice['item_count']);
        $this->assertSame(15.0, $invoice['total_quantity']);
        $this->assertSame(15055000.0, $invoice['subtotal']);
        $this->assertSame('2.1.5.1.05.01', $invoice['items'][0]['item_code']);
        $this->assertSame('1,476,000', $invoice['items'][0]['price']);
        $this->assertSame('7,380,000', $invoice['items'][0]['line_total']);
    }

    public function test_sales_invoice_pdf_renders_expected_sections(): void
    {
        $reportData = app(SalesInvoiceReportService::class)
            ->buildReportDataFromXml($this->salesInvoiceXml(), 'test xml');

        $html = view('ascends.ru.sales.sales_invoice.panjang-pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Sales Invoice (RU)', $html);
        $this->assertStringContainsString('Tagih Ke', $html);
        $this->assertStringContainsString('Kirim Ke', $html);
        $this->assertStringContainsString('Kode Barang', $html);
        $this->assertStringContainsString('KOBOKAN 12CM W/MH (18 LSN)', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Terbilang', $html);
        $this->assertStringContainsString('Dicetak oleh: indah', $html);
        $this->assertStringContainsString('INV.2605-164', $html);
        $this->assertStringContainsString('29-Mei-26', $html);
        $this->assertStringContainsString('Penjualan Abu Sekam Sawmil Basah<br />', $html);
        $this->assertStringContainsString('(L-300)<br />', $html);
        $this->assertStringContainsString('By.Timbangan 15rb+ By.Admin 5 rb &amp; By.Kebersihan 5 rb.', $html);
        $this->assertStringContainsString('Lima Belas Juta Lima Puluh Lima Ribu Rupiah', $html);
    }

    public function test_ascend_test_upload_form_lists_sales_module_and_report(): void
    {
        $this->get('/ascend-test')
            ->assertOk()
            ->assertSee('Perusahaan')
            ->assertSee('RU')
            ->assertSee('GSU')
            ->assertSee('UC')
            ->assertSee('Sales')
            ->assertSee('Sales Invoice (RU) - Panjang')
            ->assertSee('Sales Invoice (RU) - Normal')
            ->assertSee('Sales Invoices (GSU) - Panjang')
            ->assertSee('Sales Invoices (GSU) - Normal');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'title' => 'Sales Invoice (RU)',
            'printed_at' => now()->toDateTimeString(),
            'printed_by' => 'indah',
            'headers' => [
                'No',
                'Kode Barang',
                'Nama Barang',
                'QTY',
                '@Harga (Rp)',
                'Disc %',
                'Nilai (Rp)',
            ],
            'rows' => [['No SI' => 'SI/07/18/0126']],
            'invoices' => [
                [
                    'invoice_number' => 'SI/07/18/0126',
                    'items' => [['item_code' => '2.1.5.1.05.01']],
                ],
            ],
            'total_rows' => 1,
            'total_invoices' => 1,
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
    <PaymentTerm>60</PaymentTerm>
    <AmountDiscount>0</AmountDiscount>
    <NetTotal>15055000.0000</NetTotal>
    <Remarks>Penjualan Abu Sekam Sawmil Basah (L-300) By.Timbangan 15 rb+ By.Admin 5 rb &amp; By.Kebersihan 5 rb</Remarks>
    <Createdby>indah</Createdby>
    <CustomerName>KEDAUNG</CustomerName>
    <AddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</AddressLine1>
    <City>PADANG</City>
    <BillingAddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</BillingAddressLine1>
    <BillingCity>PADANG</BillingCity>
    <DropShipAddress>Jumroh
Jalan Pamarayan Tambak, RT.014/RW.003, KpKedaung, Desa Blokang, Bandung,BANDUNG, KAB. SERANG, BANTEN</DropShipAddress>
    <DONo>DO/07/18/0126</DONo>
    <InvoiceDueDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDueDate>
    <POSDeliveryDateTime>2026-05-28T14:46:57.5+07:00</POSDeliveryDateTime>
    <SalesPersonName>SIMSON</SalesPersonName>
    <VehicleNo>INV.2605-164</VehicleNo>
    <ShipperName>JTR</ShipperName>
    <ItemCode>2.1.5.1.05.01</ItemCode>
    <ItemName>KOBOKAN 12CM W/MH (18 LSN)</ItemName>
    <Price>1476000.0000</Price>
    <LineTotal>7380000.0000</LineTotal>
    <ItemDiscount />
    <Quantity>5.0000</Quantity>
    <UOMCode>DUS</UOMCode>
    <NetTotalInWords>Lima Belas Juta Lima Puluh Lima Ribu</NetTotalInWords>
  </Invoice>
  <Invoice>
    <InvoiceNumber>SI/07/18/0126</InvoiceNumber>
    <InvoiceID>4443</InvoiceID>
    <InvoiceDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDate>
    <PaymentTerm>60</PaymentTerm>
    <AmountDiscount>0</AmountDiscount>
    <NetTotal>15055000.0000</NetTotal>
    <Createdby>indah</Createdby>
    <CustomerName>KEDAUNG</CustomerName>
    <BillingAddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</BillingAddressLine1>
    <BillingCity>PADANG</BillingCity>
    <DONo>DO/07/18/0126</DONo>
    <InvoiceDueDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDueDate>
    <SalesPersonName>SIMSON</SalesPersonName>
    <ItemCode>2.1.5.1.01.03</ItemCode>
    <ItemName>BASKOM BIASA 40 CM M/MH GSU (2 LSN)</ItemName>
    <Price>560000.0000</Price>
    <LineTotal>2800000.0000</LineTotal>
    <Quantity>5.0000</Quantity>
    <UOMCode>DUS</UOMCode>
    <NetTotalInWords>Lima Belas Juta Lima Puluh Lima Ribu</NetTotalInWords>
  </Invoice>
  <Invoice>
    <InvoiceNumber>SI/07/18/0126</InvoiceNumber>
    <InvoiceID>4443</InvoiceID>
    <InvoiceDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDate>
    <PaymentTerm>60</PaymentTerm>
    <AmountDiscount>0</AmountDiscount>
    <NetTotal>15055000.0000</NetTotal>
    <Createdby>indah</Createdby>
    <CustomerName>KEDAUNG</CustomerName>
    <BillingAddressLine1>JL.AHMAD YANI NO. 11-13 KOMP. CINA BUKIT TINGGI</BillingAddressLine1>
    <BillingCity>PADANG</BillingCity>
    <DONo>DO/07/18/0126</DONo>
    <InvoiceDueDate>2026-05-29T00:00:00.0000000+07:00</InvoiceDueDate>
    <SalesPersonName>SIMSON</SalesPersonName>
    <ItemCode>2.1.5.1.07.07</ItemCode>
    <ItemName>NAMPAN 60 CM DECO (1 1/2 LSN)</ItemName>
    <Price>975000.0000</Price>
    <LineTotal>4875000.0000</LineTotal>
    <Quantity>5.0000</Quantity>
    <UOMCode>DUS</UOMCode>
    <NetTotalInWords>Lima Belas Juta Lima Puluh Lima Ribu</NetTotalInWords>
  </Invoice>
</NewDataSet>
XML;
    }
}
