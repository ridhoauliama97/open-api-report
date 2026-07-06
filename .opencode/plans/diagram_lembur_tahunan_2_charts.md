# Plan: Diagram Lembur Tahunan — 2 Charts (ST + KK/KT) + Combined Cost Table

## Ringkasan
- Ubah report Diagram Lembur Tahunan agar menerima **2 file XML** (ST dan KK/KT)
- Menghasilkan **2 chart terpisah** + **combined cost table**
- **6 bulan** (via StartDate/EndDate)

## Files

### 1. Service: `app/Services/Ascends/Shared/Hrm/CustomReports/DiagramLemburTahunanReportService.php`

**Ubah `buildReportDataFromXml()` signature:**
```php
public function buildReportDataFromXml(
    ?string $xmlContentsSt = null,
    ?string $xmlContentsKkKt = null,
    string $sourceLabel = 'request xml payload',
    array $filters = [],
): array
```

**Flow baru:**
1. Parse ST XML → filter by date → groupByMonthAndDepartment → `$monthlyDataSt`
2. Parse KK/KT XML → filter by date → groupByMonthAndDepartment → `$monthlyDataKkKt`
3. `buildCombinedCostTable($stRows, $kkKtRows, $stChartData, $kkKtChartData)`:
   - Hitung `calculateDepartmentCostsArray()` per set
   - Gabung ordering dari ST + KK/KT (sorted by total hours descending)
   - Return array `['department', 'staff_cost', 'kk_kt_cost']`
4. `resolvePeriod()` menggunakan `array_merge` dari kedua rows

**Hapus method yang tidak dipakai:**
- `calculateDepartmentCosts()` (lama)
- `resolveType()` (tidak perlu lagi)

**Return data baru:**
```php
[
    'has_st' => true/false,
    'has_kk_kt' => true/false,
    'type_label_st' => 'ST',
    'type_label_kk_kt' => 'KK/KT',
    'monthly_chart_data_st' => [...],
    'monthly_chart_data_kk_kt' => [...],
    'cost_table' => [
        ['department' => 'PRODUKSI', 'staff_cost' => 10000000, 'kk_kt_cost' => 5000000],
    ],
    'period' => [...],
    ...
]
```

### 2. Blade: `resources/views/ascends/shared/hrm/custom_reports/diagram_lembur_tahunan/pdf.blade.php`

**Layout (Landscape):**
```
┌────────────────────────────────────────┐
│  Chart ST (only if has_st)              │
│  [SVG bar - 6 bulan, ST only]          │
│  Kategori : ST                         │
├────────────────────────────────────────┤
│  Chart KK/KT (only if has_kk_kt)       │
│  [SVG bar - 6 bulan, KK/KT only]       │
│  Kategori : KK/KT                      │
├────────────────────────────────────────┤
│  Cost Summary                          │
│  No │ Departemen │ Staff │ KK/KT       │
├────────────────────────────────────────┤
│  Footer                                │
└────────────────────────────────────────┘
```

**Konsistensi warna:**
- Kumpulkan semua department name dari `monthly_chart_data_st` + `monthly_chart_data_kk_kt`
- Assign warna ke semua department sekaligus (1 palet) sebelum render chart
- Warna yang sama untuk department yang sama di kedua chart

**Perubahan blade detail:**
1. Chart ST: copy SVG logic dari chart saat ini, tapi gunakan `$reportData['monthly_chart_data_st']`
2. Chart KK/KT: copy SVG logic, gunakan `$reportData['monthly_chart_data_kk_kt']`
3. Jika hanya ST yang ada, KK/KT tidak dirender (dan sebaliknya)
4. Cost table: loop `$reportData['cost_table']`, render baris dengan:
   - No (iteration)
   - ⬛ Nama Departemen (colored square prefix)
   - Staff cost (Rp format)
   - KK/KT cost (Rp format)

### 3. Controller: `AscendXmlTestController.php`

**Ubah `apiSharedHrmDiagramLemburTahunanPdf()`:**

```php
$fileSt = $request->file('xml_file_st');
$fileKkKt = $request->file('xml_file_kk_kt');

if (($fileSt === null || !$fileSt->isValid()) && ($fileKkKt === null || !$fileKkKt->isValid())) {
    throw new RuntimeException('Minimal satu file XML (xml_file_st atau xml_file_kk_kt) wajib dikirim.');
}

$xmlSt = $fileSt && $fileSt->isValid() ? file_get_contents($fileSt->getRealPath()) : null;
$xmlKkKt = $fileKkKt && $fileKkKt->isValid() ? file_get_contents($fileKkKt->getRealPath()) : null;

$sourceLabel = 'request upload';
if ($fileSt) $sourceLabel .= ' ST:'.$fileSt->getClientOriginalName();
if ($fileKkKt) $sourceLabel .= ' KK/KT:'.$fileKkKt->getClientOriginalName();

$reportData = $reportService->buildReportDataFromXml(
    $xmlSt,
    $xmlKkKt,
    $sourceLabel,
    ['company' => $company] + $this->diagramLemburTahunanFilters($request)
);
```

**Title logic (setelah service):**
```php
$reportData['title'] = 'Laporan Diagram Persentase Jam Lembur Tahunan Per Departemen';
```

**Filename:** `Custom Reports - Laporan Diagram Lembur Tahunan Per Departemen {company}.pdf`

### 4. Route: TIDAK BERUBAH
```
POST /internal/ascends/shared/hrm/custom-reports/diagram-lembur-tahunan/pdf
```

### 5. Filter: TIDAK BERUBAH
- `StartDate` / `start_date`
- `EndDate` / `end_date`

### Parameter POST baru
| Key | Value |
|---|---|
| `xml_file_st` | File XML ST (optional) |
| `xml_file_kk_kt` | File XML KK/KT (optional) |
| `DB_CompanyName` | Nama perusahaan |
| `Sys_Username` | Username |
| `StartDate` | Awal periode (YYYY-MM-DD) |
| `EndDate` | Akhir periode (YYYY-MM-DD) |

Minimal 1 file wajib dikirim.
