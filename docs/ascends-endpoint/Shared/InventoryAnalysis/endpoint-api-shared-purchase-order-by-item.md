# Dokumentasi Hit Endpoint API Ascend Shared Inventory Analysis - Purchase Order By Item

Dokumen ini berisi endpoint internal untuk test/render laporan Purchase Order By Item.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Endpoint Shared Purchase Order By Item

### Laporan Jangka Waktu P.Order Ke P.Invoice

- **Endpoint:** `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/purchase-order-by-item/jangka-waktu-po-pi/pdf`
- **Controller method:** `AscendXmlTestController::apiSharedAnalysisJangkaWaktuPoPiPdf`
- **Service:** `JangkaWaktuPoPiReportService`
- **Blade:** `resources/views/ascends/shared/inventory_analysis/purchase_order_by_item/jangka_waktu_po_pi/pdf.blade.php`

#### Input Parameters

| Parameter | Wajib | Deskripsi |
|---|---|---|
| `xml_file` | Ya | File XML dari Ascend (`AnlReports.Inventory.PurchaseOrderByItem.xml`) |
| `DB_CompanyName` | Ya | Nama/kode perusahaan, contoh `GSU`, `RU`, `UC` |
| `Sys_Username` | Ya | Nama user print untuk footer |
| `PurchaseOrderDate.StartDate` | Tidak | Tanggal awal filter Order Date |
| `PurchaseOrderDate.EndDate` | Tidak | Tanggal akhir filter Order Date |

#### Filter / Selection Formula

Report ini menerapkan filter Crystal Reports `{@Tampil} startswith "Tampil"`:

- Record ditampilkan jika `JangkaWaktuAprovePO > 2` ATAU `JangkaWaktuPOPI > 5` ATAU `PR -PI > 7`.

#### Formula Fields yang Dihitung

| Formula | Logika |
|---|---|
| `Tampil` | `Tampil` jika `JangkaWaktuAprovePO > 2` atau `JangkaWaktuPOPI > 5` atau `PR -PI > 7`, selain itu `NOT` |
| `Status` | Jika `Status='Active'` maka `-`, selain itu `Closed Reason` |
| `PR -PI` | Hari antara `Last Purchase Date` dan `PR Date`; `0` jika salah satu kosong |
| `PO-PI` | Hari antara `Last Purchase Date` dan `Order Date` |
| `JangkaWaktuPOPI` | Selisih tanggal `Last Purchase Date` - `Order Date` |
| `JangkaWaktuAprovePO` | Selisih tanggal `Approved Date/Time` - `Order Date` |
| `JangkaWaktuPRPI` | Selisih tanggal `Last Purchase Date` - `PR Date` |
| `NumBr` | Gabungan `Last Purchase Number` dan `Last Purchase Date` |
| `Persen0-2` | `RT0-2 / RTALL * 100` |
| `Persen2++` | `RT2++ / RTALL * 100` |
| `PersenPI0-5` | `RTPOPI0-5 / RTPOPIALL * 100`, `0` jika pembilang atau penyebut `0` |
| `PersenPI5++` | `RTPOPI5++ / RTPOPIALL * 100`, `0` jika pembilang atau penyebut `0` |
| `PersenPRPI0-7` | `RTPRPI0-7 / RTPRPIALL * 100`, `0` jika pembilang atau penyebut `0` |
| `PersenPRPI5++` | `RTPRPI7++ / RTPRPIALL * 100`, `0` jika pembilang atau penyebut `0` |

#### Struktur Response

**Summary by Approved By dan Kategori:**

- Group by `Approved By` dan `Kategori`
- Lama Approve PO: 0-2 Hari (count + %), Diatas 2 Hari (count + %)
- Lama PO Ke PI: 0-5 Hari (count + %), Diatas 5 Hari (count + %)
- Lama PR Ke PI: 0-7 Hari (count + %), Diatas 7 Hari (count + %)
- Count Lama Approve PO dihitung distinct PO, count PO Ke PI dihitung distinct PI, dan count PR Ke PI dihitung distinct PR mengikuti running total Crystal report.

**Detail:**

- Section 1: `PO yang di Approve di atas 2 hari`
- Section 2: `Jangka waktu PO ke PI yang di atas 5 hari`
- Section 3: `Jangka waktu PR ke PI yang diatas 7 hari`
- Per PO: PO Number, Po Date, Po Create, Po Aprove Date
- Detail table: No, Item Name, Qty PO, PR Number, PI Number, Qty PI, PO-PI (Day), PR-PI (Day), Status

#### Contoh `multipart/form-data`

```text
DB_CompanyName=UC
Sys_Username=Ridho
PurchaseOrderDate.StartDate=2026-06-01
PurchaseOrderDate.EndDate=2026-06-30
xml_file=AnlReports.Inventory.PurchaseOrderByItem.xml
```

#### Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment; filename="Purchase Order By Item - Laporan Jangka Waktu P.Order Ke P.Invoice (UC).pdf"`

#### Response Gagal

- `422 application/json` jika XML kosong, tidak valid, data tidak ditemukan, data tidak ditemukan di periode tertentu, atau data tidak memenuhi selection formula.

## Sumber Data

XML dari Ascend: `AnlReports.Inventory.PurchaseOrderByItem.xml`

### Laporan History Harga Purchase Order

- **Endpoint:** `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/purchase-order-by-item/history-harga-po/pdf`
- **Controller method:** `AscendXmlTestController::apiSharedAnalysisHistoryHargaPoPdf`
- **Service:** `HistoryHargaPoReportService`
- **Blade:** `resources/views/ascends/shared/inventory_analysis/purchase_order_by_item/history_harga_po/pdf.blade.php`

#### Input Parameters

| Parameter | Wajib | Deskripsi |
|---|---|---|
| `xml_file` | Ya | File XML dari Ascend (`AnlReports.Inventory.PurchaseOrderByItem.xml`) |
| `DB_CompanyName` | Ya | Nama/kode perusahaan, contoh `GSU`, `RU`, `UC` |
| `Sys_Username` | Ya | Nama user print untuk footer |
| `PurchaseOrderDate.StartDate` | Tidak | Tanggal awal filter Order Date |
| `PurchaseOrderDate.EndDate` | Tidak | Tanggal akhir filter Order Date |

#### Filter / Selection Formula

Report ini menerapkan filter Crystal Reports `{@Sudah Ada} startswith "Ada"`:

- Record ditampilkan jika ada kenaikan harga terhadap history harga sebelumnya sesuai formula `Sudah Ada`.
- Baris didedup mengikuti formula `Grp33`: `Order Date + Item Name + Item Unit Cost + Last Price-1 + Last Price-2 + Last Price-3`.

#### Formula Fields yang Dihitung

| Formula | Logika |
|---|---|
| `Category` | Jika `Item Category` mengandung `PERS. LAINNYA` maka `Persediaan lainnya`, selain itu `Non Persediaan Lain` |
| `Grp33` | Gabungan `Order Date`, `Item Name`, `Item Unit Cost`, `Last Price-1`, `Last Price-2`, dan `Last Price-3` |
| `New` | Jika `Last Price-1`, `Last Price-2`, dan `Last Price-3` bernilai `0` maka `New Item`, selain itu `Existing Item` |
| `Sudah Ada` | `Ada` jika ada price history yang lebih rendah dari harga setelahnya/current PO, selain itu `Tidak Ada` |

#### Struktur Response

- Group by `Category`
- Subgroup by `New` (`Existing Item` / `New Item`)
- Detail table: No, Tgl PO, No PO, Item Name, Supplier, Qty, Harga PO, Last Price 1, Tgl 1, Last Price 2, Tgl 2, Last Price 3, Tgl 3

#### Contoh `multipart/form-data`

```text
DB_CompanyName=UC
Sys_Username=Ridho
PurchaseOrderDate.StartDate=2026-06-01
PurchaseOrderDate.EndDate=2026-06-30
xml_file=AnlReports.Inventory.PurchaseOrderByItem.xml
```

#### Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment; filename="Purchase Order By Item - Laporan History Harga Purchase Order (UC).pdf"`

#### Response Gagal

- `422 application/json` jika XML kosong, tidak valid, data tidak ditemukan, data tidak ditemukan di periode tertentu, atau data tidak memenuhi selection formula.
