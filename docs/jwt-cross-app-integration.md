# JWT Cross-App Integration Guide

Dokumen ini menjelaskan cara mengintegrasikan aplikasi lain ke endpoint report di service ini.

## Tujuan
- Aplikasi lain dapat memanggil endpoint report tanpa login user ke database lokal service report.
- Service report hanya memverifikasi JWT (signature + claim policy).

## Claim Minimal yang Direkomendasikan
- `username`: username user login di backend existing
- `exp`: waktu kedaluwarsa token (Unix timestamp)
- `iat`: waktu terbit token (Unix timestamp, opsional tapi direkomendasikan)
- `sub`: identifier user global (opsional)
- `name`: nama user untuk metadata "Dicetak oleh" (opsional)
- `email`: email user untuk metadata (opsional)
- `scope`: minimal mengandung `report:generate` jika policy ini diaktifkan (opsional)
- `iat`, `nbf`, `exp`: claim waktu standar JWT

## Konfigurasi di Service Report
Set nilai ini pada `.env` service report:

```env
SECRET_KEY=ratimdoKey
REPORT_API_JWT_SECRET=${SECRET_KEY}
REPORT_API_JWT_CLOCK_SKEW_SECONDS=30
REPORT_API_JWT_SUBJECT_CLAIM=sub
REPORT_API_JWT_USERNAME_CLAIM=username
REPORT_API_JWT_NAME_CLAIM=name
REPORT_API_JWT_EMAIL_CLAIM=email
REPORT_API_ENFORCE_SCOPE=false
# Optional policy:
# REPORT_API_REQUIRED_SCOPE=report:generate
# REPORT_API_TRUSTED_ISSUERS=https://auth.company.local
# REPORT_API_TRUSTED_AUDIENCES=open-api-report
```

## Konfigurasi Signature Verification
Service report saat ini memverifikasi JWT algoritma `HS256`.
Gunakan secret yang sama antara issuer dan service report via `REPORT_API_JWT_SECRET` (atau fallback `SECRET_KEY`).

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
- `iss` tidak ada di whitelist (jika whitelist issuer diaktifkan)
- `aud` tidak ada di whitelist (jika whitelist audience diaktifkan)
- `scope` tidak mengandung scope yang diwajibkan (jika `REPORT_API_ENFORCE_SCOPE=true`)

## Catatan Operasional
- Gunakan NTP di semua server agar `iat/nbf/exp` tidak bermasalah karena clock skew.
- Gunakan `sub` global (UUID), jangan ID integer lokal aplikasi.
- Simpan policy issuer/audience/scope di config management terpusat.
