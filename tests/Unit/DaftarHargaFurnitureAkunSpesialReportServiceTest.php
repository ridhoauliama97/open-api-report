<?php

namespace Tests\Unit;

use App\Services\Ascends\Shared\CustomReport\CheckPriceGroupA\DaftarHargaFurnitureAkunSpesialReportService;
use PHPUnit\Framework\TestCase;

class DaftarHargaFurnitureAkunSpesialReportServiceTest extends TestCase
{
    public function test_build_report_data_filters_groups_sorts_and_uses_akun_spesial_prices(): void
    {
        $service = new DaftarHargaFurnitureAkunSpesialReportService;

        $data = $service->buildReportDataFromXml($this->priceGroupXml(), 'Custom2084.xml', [
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Ridho',
        ]);

        $this->assertSame('DAFTAR HARGA FURNITURE', $data['title']);
        $this->assertSame('RU', $data['company']);
        $this->assertSame('Ridho', $data['printed_by']);

        $this->assertSame([
            'MERONA KURSI MAKAN KM 2401 PREMIUM',
            'MERONA KURSI MAKAN KM 2401 A',
            'MORE MEJA SANTAI MS 2801 PREMIUM',
            'MODELUX KURSI CAFE KC 2601',
            'GRANDE PLASTIK KABINET PK 3003 3TX6P',
            'HANA PLASTIK KABINET PK 3101 1TX2P',
        ], array_column($data['items'], 'description'));

        $this->assertSame(120000.0, $data['items'][0]['harga_konsumen']);
        $this->assertSame(106800.0, $data['items'][0]['akun_spesial']);
        $this->assertSame(15.0, $data['items'][0]['per_dus']);
        $this->assertSame('Isi Per Bal (Pcs)', $data['items'][0]['ket']);
        $this->assertSame('Isi Per Dus (Unit)', $data['items'][4]['ket']);
    }

    private function priceGroupXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
  <Table>
    <PriceGroupName>MERONA KURSI MAKAN KM 2401 PREMIUM</PriceGroupName>
    <FamilyName>PLASTIK FURNITURE 1</FamilyName>
    <PriceGroupDescription>MERONA KURSI MAKAN KM 2401 PREMIUM</PriceGroupDescription>
    <PriceLevelName>01. Harga Retail</PriceLevelName>
    <Price>111300.0000</Price>
    <PriceAfterDisc>105735.0000</PriceAfterDisc>
    <PerDus>15.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>MERONA KURSI MAKAN KM 2401 PREMIUM</PriceGroupName>
    <FamilyName>PLASTIK FURNITURE 1</FamilyName>
    <PriceGroupDescription>MERONA KURSI MAKAN KM 2401 PREMIUM</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>120000.0000</Price>
    <PriceAfterDisc>106800.0000</PriceAfterDisc>
    <PerDus>15.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>MERONA KURSI MAKAN KM 2401 A</PriceGroupName>
    <FamilyName>PLASTIK FURNITURE 1</FamilyName>
    <PriceGroupDescription>MERONA KURSI MAKAN KM 2401 A</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>86000.0000</Price>
    <PriceAfterDisc>76540.0000</PriceAfterDisc>
    <PerDus>15.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>MORE MEJA SANTAI MS 2801 PREMIUM</PriceGroupName>
    <FamilyName>FURNITURE LIPAT</FamilyName>
    <PriceGroupDescription>MORE MEJA SANTAI MS 2801 PREMIUM</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>102000.0000</Price>
    <PriceAfterDisc>90780.0000</PriceAfterDisc>
    <PerDus>12.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>MODELUX KURSI CAFE KC 2601</PriceGroupName>
    <FamilyName>PLASTIK FURNITURE 2</FamilyName>
    <PriceGroupDescription>MODELUX KURSI CAFE KC 2601</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>162000.0000</Price>
    <PriceAfterDisc>144180.0000</PriceAfterDisc>
    <PerDus>5.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>GRANDE PLASTIK KABINET PK 53003 3TX6P</PriceGroupName>
    <FamilyName>PLASTIK KABINET 1</FamilyName>
    <PriceGroupDescription>GRANDE PLASTIK KABINET PK 3003 3TX6P</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>579000.0000</Price>
    <PriceAfterDisc>515310.0000</PriceAfterDisc>
    <PerDus>1.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>HANA PLASTIK KABINET PK 3101 1TX2P</PriceGroupName>
    <FamilyName>PLASTIK KABINET 2</FamilyName>
    <PriceGroupDescription>HANA PLASTIK KABINET PK 3101 1TX2P</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>109000.0000</Price>
    <PriceAfterDisc>97010.0000</PriceAfterDisc>
    <PerDus>1.0000</PerDus>
  </Table>
  <Table>
    <PriceGroupName>ENAMEL PANCI</PriceGroupName>
    <FamilyName>ENAMEL</FamilyName>
    <PriceGroupDescription>ENAMEL PANCI</PriceGroupDescription>
    <PriceLevelName>04. Harga Akun Special</PriceLevelName>
    <Price>999999.0000</Price>
    <PriceAfterDisc>999999.0000</PriceAfterDisc>
    <PerDus>1.0000</PerDus>
  </Table>
</NewDataSet>
XML;
    }
}
