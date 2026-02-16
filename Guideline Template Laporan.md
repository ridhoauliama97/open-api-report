# Guideline Template Laporan

## Struktur Dokumen
- Margin halaman: `24mm 12mm 20mm 12mm`.
- Header laporan:
  - Judul: `Laporan Mutasi Cross Cut`.
  - Subtitle: periode tanggal laporan.
  - Font header: `Noto Serif`.
- Footer halaman:
  - Menampilkan user pencetak dan timestamp.
  - Nomor halaman di sisi kanan: `Halaman {PAGENO} dari {nbpg}`.
  - Font footer: `Noto Serif`, ukuran kecil.

## Konfigurasi Tabel
- Class utama: `table table-striped`.
- Border tabel: garis solid tipis (`#9ca3af`) untuk header dan body.
- Header tabel:
  - Background: `#306DD0`.
  - Mendukung header bertingkat (`Masuk`, `Keluar`) jika kolom tersedia.
- Zebra rows:
  - Baris ganjil (`row-odd`): `#c9d1df`.
  - Baris genap (`row-even`): `#eef2f8`.
  - Implementasi zebra menggunakan class baris Blade (`$loop->odd`/`$loop->even`) agar konsisten di mPDF.

## Aturan Kolom dan Urutan
- Kolom sistem yang disembunyikan: `created_at`, `updated_at`.
- Label dan urutan utama:
  - `No` (dari `id`, ditampilkan sebagai nomor urut 1..n).
  - `Jenis`.
  - `Awal`.
  - Grup `Masuk` (sub-kolom masuk sesuai mapping).
  - `Total Masuk`.
  - Grup `Keluar` (sub-kolom keluar sesuai mapping).
  - `Total Keluar`.
  - `Total Akhir`.
- Mapping kolom grup dilakukan dengan normalisasi nama key agar tahan terhadap variasi format key.

## Format Angka dan Penekanan Data
- Data numerik diformat dengan `number_format(..., 2, ',', '.')`.
- Kolom numerik rata kanan (`.number`).
- Kolom `No` rata tengah.
- Jika terdapat kolom dengan kalimat `Total `, ditampilkan dengan font `bold`.

## Baris Total
- Ditampilkan di bagian bawah tabel jika data tersedia.
- Sel pertama berisi label `Total`.
- Kolom numerik menampilkan akumulasi total.

## Logika Orientasi Kertas
- Ditentukan di `PdfGenerator` berdasarkan jumlah kolom terlihat (setelah mengecualikan kolom tersembunyi).
- Jika jumlah kolom `> 8` gunakan `landscape`.
- Jika jumlah kolom `<= 8` gunakan `portrait`.

## Referensi File
- Generator PDF: `app/Services/PdfGenerator.php`
