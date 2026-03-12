# POST `/api/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan/health`

Health check struktur output SP untuk laporan Rekap Hasil Sawmill Per-Meja (Upah Borongan).

## Parameters

- `TglAwal` (date, required)
- `TglAkhir` (date, required)

## Response

Mengembalikan JSON status kesesuaian kolom (`expected_columns`) dengan output SP.

