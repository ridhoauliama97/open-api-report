# Dokumentasi Hit Endpoint API Ascend Shared Inventory Analysis

Dokumen ini berisi endpoint internal untuk test/render laporan Analysis Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Analysis

Template shared Analysis dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint Shared Adjustment By Item

- Adjustment By Item - Laporan Penyesuaian Persediaan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/adjustment-by-item/penyesuaian-persediaan/pdf`
- Adjustment By Item - Laporan Adjustment Selisih Kursi: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/adjustment-by-item/adjustment/khusus-kursi/pdf`
- Adjustment By Item - Laporan Adjustment Selisih Lemari: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/adjustment-by-item/adjustment/khusus-lemari/pdf`
- Adjustment By Item - Laporan Adjustment Lemari: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/adjustment-by-item/adjustment/adjustment-lemari/pdf`


## Endpoint Shared Goods Delivery Note

- Goods Delivery Note - Laporan Rekapan Value Surat Jalan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/adjustment-by-item/goods-delivery-note/rekapan-value-surat-jalan/pdf`
- Goods Delivery Note - Laporan Pengiriman Lemari: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/adjustment-by-item/goods-delivery-note/pengiriman-lemari/pdf`

## Endpoint Shared Outstanding Undelivery Goods

- Outstanding Undelivery Goods - Laporan List DO Belum Terkirim: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/outstanding-undelivery-goods/list-do-belum-terkirim/pdf`
- Outstanding Undelivery Goods - Laporan DO Customer Belum Terkirim: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/outstanding-undelivery-goods/do-customer-belum-terkirim/pdf`
- Outstanding Undelivery Goods - Laporan DO Lemari Belum Terkirim: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/outstanding-undelivery-goods/do-lemari-belum-terkirim/pdf`
- Outstanding Undelivery Goods - Laporan DO Per Kategori Belum Terkirim: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/outstanding-undelivery-goods/do-per-kategori-belum-terkirim/pdf`

## Endpoint Shared Stock Activities Summary

- Stock Activities Summary - Laporan HPP Dan Stock: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-activities-summary/laporan-hpp-dan-stock/pdf`
- Stock Activities Summary - Laporan Khusus Plastik Kabinet: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-activities-summary/khusus-plastik-kabinet/pdf`
- Stock Activities Summary - Ringkasan Valuasi Persediaan (Aktifitas Stock GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-activities-summary/aktifitas-stock-gsu/pdf`
- Stock Activities Summary - Laporan Ringkasan Valuasi Persediaan (Aktifitas Stock RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-activities-summary/aktifitas-stock-ru/pdf`
- Stock Activities Summary - Ringkasan Valuasi Persediaan Per Gudang (Aktifitas Stock GSU Per Gudang): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-activities-summary/aktifitas-stock-gsu-per-gudang/pdf`

## Endpoint Shared Stock Balance

- Stock Balance - Laporan Pendukung Stock Opname (UC): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-blanace/saldo-stok-barang-per-gudang-uc/pdf`
- Stock Balance - Laporan Pendukung Stock Opname (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-blanace/saldo-stok-barang-per-gudang-ru/pdf`
- Stock Balance - Laporan Pendukung Stock Opname (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/stock-blanace/saldo-stok-barang-per-gudang-gsu/pdf`

## Endpoint Shared Purchase By Item

- Purchase By Item - Laporan Ringkasan Pembelian (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/purchase-by-item/ringkasan-pembelian-ru/pdf`

## Endpoint Shared Sales By Item

- Sales By Item - Laporan Penjualan Per Item Family: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/sales-by-item/penjualan-per-group-bulanan-ru/pdf`
- Sales By Item - Laporan Persentase HPP Penjualan Per Item Family: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/sales-by-item/persentase-hpp-penjualan-per-item-family-ru/pdf`

## Endpoint Shared Production

- Production - Laporan Harian Hasil Broker: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-broker-per-hari/pdf`
- Production - Laporan Hasil Broker Per Kategori: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-broker-per-kategori/pdf`
- Production - Laporan Hasil Broker Per Mesin: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-broker-per-mesin/pdf`
- Production - Laporan Harian Hasil Cuci: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-cuci-per-hari/pdf`
- Production - Laporan Hasil Cuci Per Mesin: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-cuci-per-mesin/pdf`
- Production - Laporan Hasil Cuci Per Supplier: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-cuci-per-supplier/pdf`
- Production - Laporan Hasil Produksi Per Mesin: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production/hasil-produksi-per-mesin/pdf`

## Endpoint Shared Purchase Request By Item

- Purchase Request By Item - Laporan Jangka Waktu Approved P.Request Dan P.Order: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/purchase-request-by-item/jangka-waktu-approve-pr-po-detail/pdf`

## Endpoint Shared Production By Item

- Production By Item - Laporan Produksi: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production_by_item/produksi/pdf`
- Production By Item - Laporan Produksi Per Minggu: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/production_by_item/produksi-per-minggu/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Analysis:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Ridho`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Catatan: `DB_CompanyName` dipakai lebih dulu dibanding field form `company`.

Input tambahan khusus `penyesuaian-persediaan`, `khusus-kursi`, `khusus-lemari`, `adjustment-lemari`, `rekapan-value-surat-jalan`, `pengiriman-lemari`, `list-do-belum-terkirim`, `do-customer-belum-terkirim`, `do-lemari-belum-terkirim`, `do-per-kategori-belum-terkirim`, `laporan-hpp-dan-stock`, `khusus-plastik-kabinet`, `aktifitas-stock-gsu`, `aktifitas-stock-ru`, `aktifitas-stock-gsu-per-gudang`, `ringkasan-pembelian-ru`, `penjualan-per-group-bulanan-ru`, `persentase-hpp-penjualan-per-item-family-ru`, `hasil-broker-per-hari`, `hasil-broker-per-kategori`, `hasil-broker-per-mesin`, `hasil-cuci-per-hari`, `hasil-cuci-per-mesin`, `hasil-cuci-per-supplier`, `hasil-produksi-per-mesin`, `jangka-waktu-approve-pr-po-detail` (dan Stock Activities Summary lainnya):

- `AdjustmentDate.StartDate` + `AdjustmentDate.EndDate`: periode filter data adjustment, contoh `2026-05-10` sampai `2026-05-31`.
- `DateRange.StartDate` + `DateRange.EndDate`: tanggal range label untuk laporan Stock Activities Summary (HPP Dan Stock, Khusus Plastik Kabinet, Ringkasan Valuasi Persediaan, Laporan Ringkasan Valuasi Persediaan, Ringkasan Valuasi Persediaan Per Gudang), contoh `2026-06-01` sampai `2026-06-23`.
- `PurchaseDate.StartDate` + `PurchaseDate.EndDate`: periode filter untuk laporan Purchase By Item (Ringkasan Pembelian RU), contoh `2026-06-01` sampai `2026-06-30`.
- `SalesDate.StartDate` + `SalesDate.EndDate`: periode filter untuk laporan Sales By Item (Penjualan Per Item Family), contoh `2026-06-01` sampai `2026-06-30`.
- `ProductionDate.StartDate` + `ProductionDate.EndDate`: periode filter data production broker, contoh `2026-05-10` sampai `2026-05-31`.
- `PurchaseOrderDate.StartDate` + `PurchaseOrderDate.EndDate`: periode filter PR Date untuk laporan Purchase Request By Item, contoh `2026-06-01` sampai `2026-06-30`.
- Alias tanggal yang diterima: `start_date` + `end_date`, `StartDate` + `EndDate`, `TglAwal` + `TglAkhir`, `date_start` + `date_end`, `dari_tanggal` + `sampai_tanggal`, dan `AdjustmentDate.StartDatee` (typo variant).
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di data XML.

Input tambahan khusus `list-do-belum-terkirim`, `do-customer-belum-terkirim`, `do-lemari-belum-terkirim`, `do-per-kategori-belum-terkirim`, `saldo-stok-barang-per-gudang-uc`, `saldo-stok-barang-per-gudang-ru`, dan `saldo-stok-barang-per-gudang-gsu`:

- `PerDate`: tanggal filter untuk menentukan data stock per tanggal tertentu, contoh `2026-06-29`.
- Alias yang diterima: `per_date`, `PerDate`, `Per Date`, `Per_x0020_Date`, `perDate`, `tanggal`, `Tanggal`, `date`, `Date`, `report_date`, `ReportDate`, `Report Date`, `tgl_per`, `TglPer`.

Contoh `multipart/form-data` (Adjustment By Item):

```text
DB_CompanyName=GSU
Sys_Username=Ridho
AdjustmentDate.StartDate=2026-05-10
AdjustmentDate.EndDate=2026-05-31
xml_file=AnlReports.Inventory.AdjustmentByItem.xml
```

Contoh `multipart/form-data` (Stock Activities Summary):

```text
DB_CompanyName=GSU
Sys_Username=Ridho
DateRange.StartDate=2026-06-01
DateRange.EndDate=2026-06-23
xml_file=AnlReports.Inventory.StockActivitiesSummary.xml
```

Contoh tanpa parameter tanggal (periode otomatis dari data):

```text
DB_CompanyName=GSU
Sys_Username=Ridho
xml_file=AnlReports.Inventory.AdjustmentByItem.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment` (semua endpoint kecuali production)
- `Content-Disposition: attachment` (khusus `hasil-broker-per-hari`, `hasil-broker-per-kategori`, `hasil-broker-per-mesin`, `hasil-cuci-per-hari`, `hasil-cuci-per-mesin`, `hasil-cuci-per-supplier`, `hasil-produksi-per-mesin`, `produksi`, `saldo-stok-barang-per-gudang-uc`, `saldo-stok-barang-per-gudang-ru`, dan `saldo-stok-barang-per-gudang-gsu`)

Title yang tampil di halaman PDF tetap memakai nama laporan tanpa prefix kategori. Nilai `{company}` berasal dari parameter `DB_CompanyName`, atau fallback field form `company` jika parameter tersebut tidak ada:

```text
{Nama Laporan} ({company})
```

Filename PDF memakai prefix section folder:

```text
{Section} - {Nama Laporan} ({company}).pdf
```

Contoh:

- `Adjustment By Item - Laporan Penyesuaian Persediaan (GSU).pdf`
- `Adjustment By Item - Laporan Penyesuaian Persediaan (RU).pdf`
- `Adjustment By Item - Laporan Adjustment Selisih Kursi (GSU).pdf`
- `Adjustment By Item - Laporan Adjustment Selisih Lemari (GSU).pdf`
- `Adjustment By Item - Laporan Adjustment Lemari (GSU).pdf`
- `Goods Delivery Note - Laporan Rekapan Value Surat Jalan (GSU).pdf`
- `Laporan Pengiriman Lemari (GSU).pdf`
- `Laporan List DO Belum Terkirim (GSU).pdf`
- `Outstanding Undelivery Goods - Laporan DO Customer Belum Terkirim (GSU).pdf`
- `Outstanding Undelivery Goods - Laporan DO Lemari Belum Terkirim (GSU).pdf`
- `Outstanding Undelivery Goods - Laporan DO Per Kategori Belum Terkirim (GSU).pdf`
- `Stock Activities Summary - Laporan HPP Dan Stock (GSU).pdf`
- `Stock Activities Summary - Laporan Khusus Plastik Kabinet (GSU).pdf`
- `Stock Activities Summary - Ringkasan Valuasi Persediaan (GSU).pdf`
- `Stock Activities Summary - Laporan Ringkasan Valuasi Persediaan (RU).pdf`
- `Stock Activities Summary - Ringkasan Valuasi Persediaan Per Gudang (GSU).pdf`
- `Purchase By Item - Laporan Ringkasan Pembelian (RU).pdf`
- `Sales By Item - Laporan Penjualan Per Item Family (RU).pdf`
- `Sales By Item - Laporan Persentase HPP Penjualan Per Item Family (RU).pdf`
- `Production - Laporan Harian Hasil Broker (GSU).pdf`
- `Production - Laporan Hasil Broker Per Kategori (GSU).pdf`
- `Production - Laporan Hasil Broker Per Mesin (GSU).pdf`
- `Production - Laporan Harian Hasil Cuci (GSU).pdf`
- `Production - Laporan Hasil Cuci Per Mesin (GSU).pdf`
- `Production - Laporan Hasil Cuci Per Supplier (GSU).pdf`
- `Production - Laporan Hasil Produksi Per Mesin (GSU).pdf`
- `Production By Item - Laporan Produksi (GSU).pdf`
- `Production By Item - Laporan Produksi Per Minggu (GSU).pdf`
- `Stock Balance - Laporan Pendukung Stock Opname (UC).pdf`
- `Stock Balance - Laporan Pendukung Stock Opname (RU).pdf`
- `Stock Balance - Laporan Pendukung Stock Opname (GSU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared Analysis Tersedia

Template Blade shared Adjustment By Item berada di `resources/views/ascends/shared/inventory_analysis/adjustment_by_item`.

- `adjustment_by_item/penyesuaian_persediaan`
- `adjustment_by_item/adjustment/khusus_kursi`
- `adjustment_by_item/adjustment/khusus_lemari`
- `adjustment_by_item/adjustment/adjustment_lemari`
- `goods_delivery_note/rekapan_value_surat_jalan`
- `goods_delivery_note/pengiriman_lemari`
- `outstanding_undelivery_goods/list_do_belum_terkirim`
- `outstanding_undelivery_goods/do_customer_belum_terkirim`
- `outstanding_undelivery_goods/do_lemari_belum_terkirim`
- `outstanding_undelivery_goods/do_per_kategori_belum_terkirim`
- `stock_activities_summary/laporan_hpp_dan_stock`
- `stock_activities_summary/khusus_plastik_kabinet`
- `stock_activities_summary/aktifitas_stock_gsu`
- `stock_activities_summary/aktifitas_stock_ru`
- `stock_activities_summary/aktifitas_stock_gsu_per_gudang`
- `purchase_by_item/ringkasan_pembelian_ru`
- `sales_by_item/penjualan_per_group_bulanan_ru`
- `sales_by_item/persentase_hpp_pernjualan_per_item_family_ru`
- `production/hasil_broker_per_hari`
- `production/hasil_broker_per_kategori`
- `production/hasil_broker_per_mesin`
- `production/hasil_cuci_per_hari`
- `production/hasil_cuci_per_mesin`
- `production/hasil_cuci_per_supplier`
- `production/hasil_produksi_per_mesin`
- `production_by_item/produksi`
- `production_by_item/produksi_per_minggu`
- `stock_blanace/saldo_stok_barang_per_gudang_uc`
- `stock_blanace/saldo_stok_barang_per_gudang_ru`
- `stock_blanace/saldo_stok_barang_per_gudang_gsu`
- `purchase_request_by_item/jangka_waktu_approve_pr_po_detail`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
