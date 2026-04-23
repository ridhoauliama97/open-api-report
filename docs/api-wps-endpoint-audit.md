# Audit Endpoint API Laporan WPS

Dokumen ini merangkum hasil audit endpoint API untuk seluruh laporan **WPS** yang terdaftar di [routes/api.php](../routes/api.php), dengan membandingkannya terhadap dokumentasi route yang sudah tersedia di [docs/api/routes/README.md](./api/routes/README.md).

## Ruang Lingkup

- Cakupan audit: seluruh route `api.reports.*` non-PPS.
- Sumber kebenaran route: `routes/api.php`.
- Sumber kebenaran dokumentasi route: `docs/api/routes/*.md` dan `docs/api/routes/README.md`.
- Audit ini fokus pada pola endpoint inti laporan:
  - `POST {base-path}` untuk preview
  - `GET|POST {base-path}/pdf` untuk download PDF
  - `POST {base-path}/health` untuk health check

## Ringkasan Audit

| Metrik | Nilai |
| --- | ---: |
| Total report WPS terdaftar | 139 |
| Total report WPS yang sudah terdokumentasi | 58 |
| Total report WPS yang belum terdokumentasi | 81 |
| Coverage dokumentasi report WPS | 41.73% |

## Pola Endpoint Standar

Seluruh report WPS yang diregistrasikan melalui helper `$registerReportRoutes` di `routes/api.php` memakai pola berikut:

```text
POST      /api/reports/{report-path}
GET|POST  /api/reports/{report-path}/pdf
POST      /api/reports/{report-path}/health
```

Seluruh route report tersebut juga berada di dalam middleware group:

```text
report.jwt.claims
```

## Endpoint Utilitas Global

Di luar trio endpoint inti tiap laporan, terdapat endpoint utilitas PDF async yang berlaku global:

- `GET /api/reports/jobs/{jobId}/status`
- `GET /api/reports/jobs/{jobId}/download`
- `POST /api/reports/{reportPath}/pdf/async`

## Coverage Per Grup

| Grup | Total Report | Terdokumentasi | Belum | Status |
| --- | ---: | ---: | ---: | --- |
| `barang-jadi` | 6 | 0 | 6 | belum terdokumentasi |
| `cross-cut-akhir` | 5 | 0 | 5 | belum terdokumentasi |
| `dashboard` | 9 | 8 | 1 | hampir lengkap |
| `finger-joint` | 3 | 0 | 3 | belum terdokumentasi |
| `kayu-bulat` | 23 | 23 | 0 | lengkap |
| `lainnya` | 4 | 4 | 0 | lengkap |
| `laminating` | 5 | 0 | 5 | belum terdokumentasi |
| `management` | 12 | 0 | 12 | belum terdokumentasi |
| `moulding` | 5 | 0 | 5 | belum terdokumentasi |
| `mutasi` | 14 | 14 | 0 | lengkap |
| `penjualan-kayu` | 6 | 0 | 6 | belum terdokumentasi |
| `rendemen-kayu` | 4 | 0 | 4 | belum terdokumentasi |
| `reproses` | 3 | 0 | 3 | belum terdokumentasi |
| `s4s` | 6 | 0 | 6 | belum terdokumentasi |
| `sanding` | 5 | 0 | 5 | belum terdokumentasi |
| `sawn-timber` | 26 | 9 | 17 | parsial |
| `verifikasi` | 3 | 0 | 3 | belum terdokumentasi |

## Grup Yang Sudah Lengkap

Grup berikut sudah memiliki dokumentasi route untuk seluruh report yang terdaftar:

- `kayu-bulat`
- `mutasi`
- `lainnya`

Isi grup `lainnya`:

- `bahan-terpakai`
- `hasil-output-racip-harian`
- `label-nyangkut`
- `rangkuman-label-input`

## Temuan Utama

1. Dokumentasi route WPS saat ini baru mencakup sebagian kecil report yang aktif di API.
2. Semua report pada grup `barang-jadi`, `cross-cut-akhir`, `finger-joint`, `laminating`, `management`, `moulding`, `penjualan-kayu`, `rendemen-kayu`, `reproses`, `s4s`, `sanding`, dan `verifikasi` belum punya file dokumentasi route sama sekali.
3. Grup `dashboard` hampir lengkap, tetapi masih kurang `dashboard-reproses`.
4. Grup `sawn-timber` baru terdokumentasi 9 dari 26 report.
5. Route `sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-v2` sudah aktif di API, tetapi belum masuk dokumentasi route.

## Daftar Report WPS Yang Belum Terdokumentasi

### `barang-jadi`

- `barang-jadi.barang-jadi-hidup-detail`
- `barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran`
- `barang-jadi.rekap-produksi-barang-jadi-consolidated`
- `barang-jadi.rekap-produksi-packing-per-jenis-per-grade`
- `barang-jadi.saldo-barang-jadi-hidup-per-jenis-per-produk`
- `barang-jadi.umur-barang-jadi-detail`

### `cross-cut-akhir`

- `cross-cut-akhir.cc-akhir-hidup-detail`
- `cross-cut-akhir.ketahanan-barang-cc-akhir`
- `cross-cut-akhir.rekap-produksi-cc-akhir-consolidated`
- `cross-cut-akhir.rekap-produksi-cc-akhir-per-jenis-per-grade`
- `cross-cut-akhir.umur-cc-akhir-detail`

### `dashboard`

- `dashboard-reproses`

### `finger-joint`

- `finger-joint.ketahanan-barang-finger-joint`
- `finger-joint.rekap-produksi-finger-joint-consolidated`
- `finger-joint.umur-finger-joint-detail`

### `laminating`

- `laminating.ketahanan-barang-laminating`
- `laminating.laminating-hidup-detail`
- `laminating.rekap-produksi-laminating-consolidated`
- `laminating.rekap-produksi-laminating-per-jenis-per-grade`
- `laminating.umur-laminating-detail`

### `management`

- `management.dashboard-ru`
- `management.discrepancy-rekap-mutasi`
- `management.flow-produksi-per-periode`
- `management.hasil-produksi-mesin-lembur-dan-non-lembur`
- `management.label-perhari`
- `management.produksi-hulu-hilir`
- `management.produksi-semua-mesin`
- `management.rekap-mutasi`
- `management.rekap-mutasi-cross-tab`
- `management.rekap-stock-on-hand`
- `management.stock-hidup-per-nospk`
- `management.stock-hidup-per-nospk-discrepancy`

### `moulding`

- `moulding.ketahanan-barang-moulding`
- `moulding.moulding-hidup-detail`
- `moulding.rekap-produksi-moulding-consolidated`
- `moulding.rekap-produksi-moulding-per-jenis-per-grade`
- `moulding.umur-moulding-detail`

### `penjualan-kayu`

- `penjualan-kayu.koordinat-tanah`
- `penjualan-kayu.penjualan-lokal`
- `penjualan-kayu.rekap-penjualan-ekspor-per-buyer-per-produk`
- `penjualan-kayu.rekap-penjualan-ekspor-per-produk-per-buyer`
- `penjualan-kayu.rekap-penjualan-per-produk`
- `penjualan-kayu.timeline-rekap-penjualan-per-produk`

### `rendemen-kayu`

- `rendemen-kayu.produksi-per-spk`
- `rendemen-kayu.rekap-rendemen-non-rambung`
- `rendemen-kayu.rekap-rendemen-rambung`
- `rendemen-kayu.rendemen-semua-proses`

### `reproses`

- `reproses.ketahanan-barang-reproses`
- `reproses.reproses-hidup-detail`
- `reproses.umur-reproses-detail`

### `s4s`

- `s4s.grade-abc-harian`
- `s4s.ketahanan-barang-s4s`
- `s4s.output-produksi-s4s-per-grade`
- `s4s.rekap-produksi-rambung-per-grade`
- `s4s.rekap-produksi-s4s-consolidated`
- `s4s.umur-s4s-detail`

### `sanding`

- `sanding.ketahanan-barang-sanding`
- `sanding.rekap-produksi-sanding-consolidated`
- `sanding.rekap-produksi-sanding-per-jenis-per-grade`
- `sanding.sanding-hidup-detail`
- `sanding.umur-sanding-detail`

### `sawn-timber`

- `sawn-timber.kd-keluar-masuk`
- `sawn-timber.ketahanan-barang-st`
- `sawn-timber.label-st-hidup-detail`
- `sawn-timber.mutasi-kd`
- `sawn-timber.pemakaian-obat-vacuum`
- `sawn-timber.pembelian-st-per-supplier-ton`
- `sawn-timber.pembelian-st-timeline-ton`
- `sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-v2`
- `sawn-timber.rekap-kamar-kd`
- `sawn-timber.rekap-penerimaan-st-dari-sawmill-non-rambung`
- `sawn-timber.rekap-produktivitas-sawmill`
- `sawn-timber.rekap-st-penjualan`
- `sawn-timber.st-basah-hidup-per-umur-kayu-ton`
- `sawn-timber.st-hidup-kering`
- `sawn-timber.st-rambung-mc1-mc2-detail`
- `sawn-timber.st-rambung-mc1-mc2-rangkuman`
- `sawn-timber.st-sawmill-masuk-per-group-meja`

### `verifikasi`

- `verifikasi.bahan-yang-dihasilkan`
- `verifikasi.kapasitas-racip-kayu-bulat-hidup`
- `verifikasi.rangkuman-bongkar-susun`

## Rekomendasi Prioritas

1. Lengkapi dokumentasi untuk grup `dashboard` terlebih dahulu karena tinggal 1 report.
2. Lengkapi dokumentasi `sawn-timber` karena sudah parsial dan paling dekat untuk dirapikan menjadi satu grup utuh.
3. Setelah itu lanjut ke grup yang belum punya dokumentasi sama sekali, dimulai dari `management`, `barang-jadi`, dan `cross-cut-akhir`.
4. Jika ingin konsisten dengan pola dokumentasi saat ini, setiap base report perlu memiliki 3 file:
   - `{name}.preview.md`
   - `{name}.pdf.md`
   - `{name}.health.md`

## Catatan

- Audit ini tidak memvalidasi isi request/response payload per controller.
- Audit ini tidak menguji eksekusi endpoint secara runtime.
- Audit ini hanya memeriksa kesesuaian antara route aktif dan file dokumentasi yang tersedia.
