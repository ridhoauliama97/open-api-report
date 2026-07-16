# Dokumentasi Hit Endpoint API Ascend Shared Inventory Analysis - Purchase Request By Item

Dokumen ini berisi endpoint internal untuk test/render laporan Purchase Request By Item.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Endpoint Shared Purchase Request By Item

### Laporan Jangka Waktu Approved P.Request Dan P.Order

- **Endpoint:** `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/purchase-request-by-item/jangka-waktu-approve-pr-po-detail/pdf`
- **Controller method:** `AscendXmlTestController::apiSharedAnalysisJangkaWaktuApprovePrPoDetailPdf`
- **Service:** `JangkaWaktuApprovePrPoDetailReportService`
- **Blade:** `resources/views/ascends/shared/inventory_analysis/purchase_request_by_item/jangka_waktu_approve_pr_po_detail/pdf.blade.php`

#### Input Parameters

| Parameter | Wajib | Deskripsi |
|---|---|---|
| `xml_file` | Ya | File XML dari Ascend (`AnlReports.Inventory.PurchaseRequestByItem.xml`) |
| `DB_CompanyName` | Ya | Nama/kode perusahaan, contoh `GSU`, `RU`, `UC` |
| `Sys_Username` | Ya | Nama user print untuk footer |
| `PurchaseOrderDate.StartDate` | Tidak | Tanggal awal filter PR Date |
| `PurchaseOrderDate.EndDate` | Tidak | Tanggal akhir filter PR Date |

#### Filter / Selection Formula

Report ini menerapkan filter Crystal Reports `{@Tampil} startswith "Tampil"`:
- Record ditampilkan jika `PR-PO > 2` ATAU `EstimasiAprvPR > 1`

#### Formula Fields yang Dihitung

| Formula | Logika |
|---|---|
| `EstimasiAprvPR` | Hari antara Approved Date/Time - PR Date |
| `EstimasiAprvPR-PO` | Hari antara PO Approved Date/Time - PO Date |
| `NameDay` | Nama hari Indonesia dari Create Date |
| `NameDay2` | Nama hari Indonesia dari Approved Date/Time |
| `NameDay3` | Nama hari Indonesia dari PR Date |
| `OnPO` | Jika Status='Pending' maka Qty Requested - Qty On Order |
| `PoCreate` | Hari antara PO Date - Approved Date/Time |
| `PR-PO` | Hari antara PO Date - Approved Date/Time |
| `Status` | Closed / '-' / 'Need PO' / 'Pending' |
| `Tampil` | 'Tampil' jika PR-PO > 2 atau EstimasiAprvPR > 1 |
| `TesPO` | PO Number atau ' - ' jika null |
| `TotalApproveTime` | Hari antara PO Approved Date/Time - PR Date |

#### Struktur Response

**Summary by Approved By:**
- Group by `Approved By`
- Total PR By Qty
- Lama Approve PR: 0-1 Hari (count + %), Diatas 1 Hari (count + %)
- Lama PR Ke PO: 0-2 Hari (count + %), Diatas 2 Hari (count + %)

**Detail:**
- No, Item Name, Qty PR, Qty PO, On Order, PO Number, PO Create (hari), PO Approv (hari), Total Time (hari), Status, Sisa, Keterangan

#### Contoh `multipart/form-data`

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PurchaseOrderDate.StartDate=2026-06-01
PurchaseOrderDate.EndDate=2026-06-30
xml_file=AnlReports.Inventory.PurchaseRequestByItem.xml
```

#### Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment; filename="Purchase Request By Item - Laporan Jangka Waktu Approved P.Request Dan P.Order (GSU).pdf"`

#### Response Gagal

- `422 application/json` jika XML kosong, tidak valid, data tidak ditemukan, atau data tidak ditemukan di periode tertentu.

## Sumber Data

XML dari Ascend: `AnlReports.Inventory.PurchaseRequestByItem.xml`
