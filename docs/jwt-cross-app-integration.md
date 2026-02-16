# JWT Cross-App Integration Guide

Dokumen ini menjelaskan cara mengintegrasikan aplikasi lain ke endpoint report di service ini.

## Tujuan
- Aplikasi lain dapat memanggil endpoint report tanpa login user ke database lokal service report.
- Service report hanya memverifikasi JWT (signature + claim policy).

## Claim Minimal yang Direkomendasikan
- `iss`: issuer token (URL/id auth service)
- `aud`: audience token (contoh: `open-api-report`)
- `sub`: identifier user global (UUID direkomendasikan)
- `name`: nama user untuk metadata "Dicetak oleh"
- `email`: email user untuk metadata
- `scope`: minimal mengandung `report:generate` jika policy ini diaktifkan
- `iat`, `nbf`, `exp`: claim waktu standar JWT

## Konfigurasi di Service Report
Set nilai ini pada `.env` service report:

```env
REPORT_JWT_TRUSTED_ISSUERS=https://auth.company.local
REPORT_JWT_TRUSTED_AUDIENCES=open-api-report
REPORT_JWT_REQUIRED_SCOPE=report:generate
REPORT_JWT_SCOPE_CLAIM=scope
```

## Konfigurasi Signature Verification
Pilih sesuai algoritma token dari issuer:

1. `HS256`
- Gunakan secret yang sama antara issuer dan service report:
```env
JWT_ALGO=HS256
JWT_SECRET=shared-secret-from-issuer
```

2. `RS256/ES256`
- Simpan public key issuer di service report:
```env
JWT_ALGO=RS256
JWT_PUBLIC_KEY=file://C:/keys/report-public.pem
```

## Contoh Request dari Aplikasi Lain
```bash
curl -X POST http://report-service.local/api/reports/mutasi-barang-jadi \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token_dari_auth_service>" \
  -d "{\"TglAwal\":\"2026-01-01\",\"TglAkhir\":\"2026-01-31\"}"
```

## Expected Failure (401)
Request akan ditolak jika:
- token tidak dikirim
- signature tidak valid
- token expired
- `iss` tidak ada di whitelist
- `aud` tidak ada di whitelist
- `scope` tidak mengandung scope yang diwajibkan

## Catatan Operasional
- Gunakan NTP di semua server agar `iat/nbf/exp` tidak bermasalah karena clock skew.
- Gunakan `sub` global (UUID), jangan ID integer lokal aplikasi.
- Simpan policy issuer/audience/scope di config management terpusat.
