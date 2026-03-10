# Referensi Desain Laporan PDF

Dokumen ini menetapkan gaya standar laporan PDF berdasarkan file referensi `Versi 3 - Tanpa garis diantara baris data.pdf`.

## Karakter visual utama

- Judul utama berada di tengah, tebal, ringkas, dan tanpa ornamen tambahan.
- Subtitle periode berada tepat di bawah judul dengan warna abu tipis.
- Tabel memakai border luar 1px hitam yang tegas.
- Header tabel memakai border penuh dan teks tebal.
- Baris data tidak memakai garis horizontal antar baris.
- Pemisahan data dilakukan dengan zebra striping abu-biru muda:
  - baris ganjil `#c9d1df`
  - baris genap `#eef2f8`
- Nilai numerik diratakan ke kanan dengan font sans agar angka lebih stabil.
- Footer kecil, italic, kiri untuk metadata cetak dan kanan untuk nomor halaman.
- Bagian keterangan atau ringkasan ditempatkan setelah tabel utama, idealnya di halaman terpisah jika isi cukup panjang.

## Struktur layout standar

1. Judul laporan
2. Subtitle periode
3. Tabel utama
4. Ringkasan atau keterangan
5. Footer cetak

## Aturan tabel

- Gunakan `table-layout: fixed` untuk menjaga lebar kolom konsisten.
- `thead` harus tetap berulang di halaman berikutnya.
- `tbody` baris data gunakan kelas `data-row`.
- Sel data gunakan kelas `data-cell` agar border vertikal tetap muncul walau border horizontal dihilangkan.
- Jika ada total, tampilkan dengan border penuh dan font tebal.
- Tambahkan `table-end-line` pada `tfoot` untuk menutup tabel dengan satu garis horizontal bawah yang rapi.

## Implementasi di Blade

Gunakan partial berikut untuk laporan baru:

- `reports.partials.pdf-reference-style`
- `reports.partials.pdf-reference-footer`

Contoh:

```blade
<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    @include('reports.partials.pdf-reference-style', [
        'pageMargin' => '24mm 10mm 18mm 10mm',
    ])
</head>
```

```blade
@include('reports.partials.pdf-reference-footer')
```

## Template yang sudah memakai standar ini

- `resources/views/reports/kayu-bulat/hidup-pdf.blade.php`
- `resources/views/reports/kayu-bulat/penerimaan-per-supplier-kg-pdf.blade.php`

Template PDF baru sebaiknya mulai dari partial ini, bukan menyalin CSS lama per file.
