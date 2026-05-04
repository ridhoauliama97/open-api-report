# Panduan Menambah Company / Modul XML Report

## Struktur Folder

```
config/
└── xml_reports/
    ├── RU/                   ← company code (huruf kapital)
    │   ├── hrm.php           ← modul HRM
    │   └── finance.php       ← modul lain (contoh)
    ├── GSUT/                 ← company lain
    │   └── hrm.php
    └── ...

storage/app/xml_sources/
    ├── RU/
    │   └── hrm/
    │       └── AnlReports_HRM_EmployeeList.xml   ← file XML dari HRM
    ├── GSUT/
    │   └── hrm/
    │       └── AnlReports_HRM_EmployeeList.xml
    └── ...
```

---

## Cara Menambah Company Baru (contoh: GSUT)

### 1. Buat folder config
```
config/xml_reports/GSUT/
```

### 2. Buat file config modul
Salin dari `config/xml_reports/RU/hrm.php`, lalu sesuaikan `xml_source`:

```php
// config/xml_reports/GSUT/hrm.php
return [
    'label'      => 'HRM — GSUT',
    'xml_source' => 'GSUT/hrm/AnlReports_HRM_EmployeeList.xml',  // ← sesuaikan
    'record_tag' => 'Employees',
    'sub_reports' => [
        // ... sama seperti RU, atau beda jika kebutuhan kolom berbeda
    ],
];
```

### 3. Buat folder storage XML
```
storage/app/xml_sources/GSUT/hrm/
```

### 4. Letakkan file XML
Export dari sistem HRM GSUT, simpan di:
```
storage/app/xml_sources/GSUT/hrm/AnlReports_HRM_EmployeeList.xml
```

---

## Cara Pakai di Controller / Job

```php
$xmlService = app(\App\Services\XmlDataSourceService::class);

// Load sub-report spesifik
$data = $xmlService->loadSubReport('RU', 'hrm', 'employee_list');
$data = $xmlService->loadSubReport('GSUT', 'hrm', 'employee_biodata');

// Cek modul apa saja yang tersedia untuk sebuah company
$modules = $xmlService->availableModules('RU');
// → ['hrm', 'finance', ...]

// Cek sub-report apa saja dalam satu modul
$subReports = $xmlService->availableSubReports('RU', 'hrm');
// → ['employee_list' => 'Daftar Karyawan', 'employee_biodata' => 'Biodata Karyawan', ...]
```

---

## Data yang Dikembalikan `loadSubReport()`

```php
[
    'printed_at'  => '04 Mei 2026 10:30',   // waktu cetak — selalu NOW
    'company'     => 'RU',
    'module'      => 'hrm',
    'sub_report'  => 'employee_list',
    'label'       => 'Daftar Karyawan',
    'headers'     => ['Kode Karyawan', 'Nama Lengkap', ...],  // header tabel
    'rows'        => [
        ['Kode Karyawan' => 'EMP001', 'Nama Lengkap' => 'John Doe', ...],
        ...
    ],
    'total_rows'  => 120,
]
```

---

## Cara Update Data (Up-to-date per Tanggal Cetak)

File XML **tidak otomatis refresh** — harus di-export manual dari HRM setiap kali ingin data terbaru:

1. Login ke sistem HRM
2. Buka AnlReports → HRM → Employee List
3. Export ke XML
4. Timpa file di `storage/app/xml_sources/{COMPANY}/hrm/AnlReports_HRM_EmployeeList.xml`
5. Cetak PDF — data akan up-to-date sesuai export terakhir, dengan `printed_at` = waktu cetak sekarang
