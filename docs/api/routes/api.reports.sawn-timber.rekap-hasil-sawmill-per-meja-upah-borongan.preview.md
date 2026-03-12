# POST `/api/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan`

Preview laporan Rekap Hasil Sawmill Per-Meja (Upah Borongan).

Output digrouping berdasarkan `NoMeja` lalu `TglSawmill`, dengan sorting tanggal dari yang terkecil sampai terbesar.

## Parameters

- `TglAwal` (date, required)
- `TglAkhir` (date, required)

## Response

Mengembalikan JSON berisi data mentah dari SP main + sub, hasil grouping per meja/tanggal, dan ringkasan total.

