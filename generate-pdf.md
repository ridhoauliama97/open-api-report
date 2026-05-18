# PRD Generate PDF Background

## Tujuan

Mempercepat pengalaman user saat membuka laporan PDF besar, khususnya `Laporan Label ST (Hidup) Detail`, dengan cara membuat PDF di background lalu membuka file PDF yang sudah jadi tanpa render ulang.

## Masalah

Endpoint PDF lama:

```text
/api/reports/sawn-timber/label-st-hidup-detail/pdf
```

melakukan render PDF langsung saat request masuk. Untuk data besar, proses ini bisa memakan beberapa menit dan berisiko timeout di aplikasi desktop atau browser.

## Solusi

PDF dibuat sebagai background process dan statusnya disimpan di file JSON, bukan tabel database. File PDF hasil render disimpan di storage Laravel.

Untuk laporan tanpa parameter seperti `Laporan Label ST (Hidup) Detail`, sistem juga melakukan refresh PDF shared berdasarkan perubahan data. Worker membaca fingerprint data dari stored procedure, lalu hanya render ulang jika fingerprint berubah. PDF shared ini dibuat oleh user sistem `system` dan dapat dipakai oleh banyak user.

## Storage

Status job disimpan di:

```text
storage/app/pdf-job-statuses/{jobId}.json
```

PDF hasil render disimpan di:

```text
storage/app/private/pdf_reports
```

Contoh file PDF:

```text
storage/app/private/pdf_reports/label-st-hidup-detail-20260518_150316-c24027c0.pdf
```

## Flow API Untuk Frontend Modern

Gunakan flow ini jika frontend bisa melakukan polling status.

Jika PDF shared dari worker refresh sudah tersedia, endpoint generate akan langsung mengembalikan status `done` dan tidak membuat job baru.

### 1. Generate PDF

```http
POST /api/reports/sawn-timber/label-st-hidup-detail/pdf/async
Authorization: Bearer <token>
```

Response:

```json
{
  "job_id": "c24027c0-209d-4ed4-b409-e6287a23ce45",
  "status": "queued",
  "status_url": "http://open-api-report.test/api/reports/sawn-timber/label-st-hidup-detail/jobs/c24027c0-209d-4ed4-b409-e6287a23ce45/status",
  "message": "PDF sedang diproses di background."
}
```

### 2. Cek Status

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/jobs/{jobId}/status
Authorization: Bearer <token>
```

Jika masih diproses:

```json
{
  "job_id": "c24027c0-209d-4ed4-b409-e6287a23ce45",
  "status": "processing",
  "created_at": "2026-05-18T15:03:15+07:00"
}
```

Jika selesai:

```json
{
  "job_id": "c24027c0-209d-4ed4-b409-e6287a23ce45",
  "status": "done",
  "download_url": "http://open-api-report.test/api/reports/sawn-timber/label-st-hidup-detail/jobs/c24027c0-209d-4ed4-b409-e6287a23ce45/download",
  "pdf_url": "http://open-api-report.test/api/reports/sawn-timber/label-st-hidup-detail/pdf?job_id=c24027c0-209d-4ed4-b409-e6287a23ce45",
  "expires_at": "2026-05-19T15:03:15+07:00"
}
```

### 3. Buka PDF

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/pdf?job_id={jobId}
Authorization: Bearer <token>
```

Endpoint ini tidak render ulang. Endpoint hanya membaca file PDF yang sudah selesai dibuat.

## Flow API Satu Tombol Untuk Desktop

Gunakan flow ini jika aplikasi desktop hanya bisa memanggil satu URL dan berharap response langsung berupa PDF.

Jika PDF shared sudah tersedia, endpoint ini langsung mengembalikan PDF tanpa render ulang.

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/pdf/async-wait
Authorization: Bearer <token>
```

atau:

```http
POST /api/reports/sawn-timber/label-st-hidup-detail/pdf/async-wait
Authorization: Bearer <token>
```

Cara kerja:

1. Request masuk.
2. Server membuat job background.
3. Server menunggu sampai PDF selesai.
4. Jika selesai sebelum timeout, response langsung `application/pdf`.
5. Jika belum selesai sampai batas waktu, response `202` berisi `job_id` dan `status_url`.

Parameter optional:

```text
wait_timeout=600
```

Nilai minimum 30 detik dan maksimum 1800 detik.

Contoh:

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/pdf/async-wait?wait_timeout=900
Authorization: Bearer <token>
```

## Flow Web

Halaman web:

```text
/reports/sawn-timber/label-st-hidup-detail
```

Tombol `Generate PDF di Background` memakai:

```text
POST /reports/sawn-timber/label-st-hidup-detail/pdf/async
```

Lalu halaman melakukan polling ke:

```text
GET /reports/sawn-timber/label-st-hidup-detail/jobs/{jobId}/status
```

Tombol `Preview PDF` memakai mode satu request:

```text
POST /reports/sawn-timber/label-st-hidup-detail/preview-pdf-wait
```

Browser membuka tab baru, request menunggu sampai PDF selesai, lalu PDF tampil inline.

## Command Background

Command yang dipakai untuk render PDF:

```bash
php artisan reports:generate-label-st-hidup-detail-pdf {jobId} --requested-by={username}
```

Command ini:

1. Membaca status job dari file JSON.
2. Mengubah status menjadi `processing`.
3. Mengambil data report dari service.
4. Render PDF ke storage.
5. Mengubah status menjadi `done` atau `failed`.

## Worker Refresh Berdasarkan Perubahan Data

Command refresh:

```bash
php artisan reports:refresh-label-st-hidup-detail-pdf-if-changed
```

Jadwal Laravel:

```text
*/5 * * * * php artisan reports:refresh-label-st-hidup-detail-pdf-if-changed
```

Command ini:

1. Membaca data dari stored procedure.
2. Membuat fingerprint/hash data.
3. Membandingkan fingerprint dengan fingerprint sebelumnya.
4. Jika data belum berubah, render PDF dilewati.
5. Jika data berubah, command menjalankan warmup PDF shared.

PDF shared dibuat dengan `requested_by=system`.

PDF shared tersebut akan dipakai lintas user. Jadi ketika user membuka laporan, sistem tidak perlu render ulang selama file PDF shared masih tersedia dan data belum berubah.

Untuk menjalankan scheduler di development:

```bash
php artisan schedule:work
```

Catatan penting:

```text
php artisan serve
```

dan Herd hanya menjalankan web server. Scheduler tidak otomatis ikut berjalan.

Jika memakai Herd di development, tetap jalankan terminal terpisah:

```bash
php artisan schedule:work
```

Biarkan terminal tersebut tetap aktif selama ingin worker refresh PDF berjalan.

Untuk production, scheduler Laravel harus dipanggil oleh cron/server scheduler:

```text
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Jika server production menggunakan Windows, gunakan Windows Task Scheduler untuk menjalankan perintah berikut setiap menit:

```bat
cd /d D:\path\to\project && php artisan schedule:run
```

## Status Job

Status yang digunakan:

```text
queued
processing
done
failed
```

## Estimasi Performa

Hasil test lokal pada data besar:

```text
Render background API : sekitar 245 detik
Buka PDF selesai      : sekitar 0.25 detik
Ukuran PDF            : sekitar 4.4 MB
```

## Cara Test Manual

### Test Web

1. Buka:

```text
http://open-api-report.test/reports/sawn-timber/label-st-hidup-detail
```

2. Klik `Generate PDF di Background`.
3. Tunggu status berubah menjadi selesai.
4. Klik download.
5. Cek file status:

```text
storage/app/pdf-job-statuses
```

6. Cek file PDF:

```text
storage/app/private/pdf_reports
```

### Test API Polling

1. Generate:

```http
POST /api/reports/sawn-timber/label-st-hidup-detail/pdf/async
Authorization: Bearer <token>
```

2. Polling status:

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/jobs/{jobId}/status
Authorization: Bearer <token>
```

3. Buka PDF:

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/pdf?job_id={jobId}
Authorization: Bearer <token>
```

### Test API Satu Tombol

```http
GET /api/reports/sawn-timber/label-st-hidup-detail/pdf/async-wait
Authorization: Bearer <token>
```

Jika selesai, response langsung PDF.

## Catatan Keamanan

Setiap job memiliki `job_id` UUID dan menyimpan `requested_by`. Untuk production, akses status dan download sebaiknya divalidasi agar user hanya bisa membuka job miliknya sendiri.

## Catatan Operasional

Jika status stuck di `queued`, kemungkinan background process tidak berjalan. Jalankan manual:

```bash
php artisan reports:generate-label-st-hidup-detail-pdf {jobId} --requested-by=tester
```

Untuk memaksa membuat ulang PDF shared:

```bash
php artisan reports:warm-label-st-hidup-detail-pdf --force
```

Untuk memaksa cek fingerprint dan render ulang bila perlu:

```bash
php artisan reports:refresh-label-st-hidup-detail-pdf-if-changed --force
```

Jika status `failed`, cek:

```text
storage/logs/laravel.log
```
