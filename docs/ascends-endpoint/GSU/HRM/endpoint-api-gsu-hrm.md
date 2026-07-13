- List Karyawan (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/gsu/hrm/list-karyawan/pdf`

Input XML sama seperti report Ascends XML lain:
- `multipart/form-data` field `xml_file`
- field `xml`
- raw XML body

Response sukses:
- `200 application/pdf`
- `Content-Disposition: attachment`
