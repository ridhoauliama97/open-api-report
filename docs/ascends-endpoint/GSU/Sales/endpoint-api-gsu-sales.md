- Sales Invoices (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/gsu/sales/sales-invoice/pdf`
- Surat Jalan (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/gsu/sales/surat-jalan/pdf`
- Sales Invoices (GSU) - Panjang: `POST http://192.168.10.100:5006/api/internal/ascends/gsu/sales/sales-invoice/panjang/pdf`
- Sales Invoices (GSU) - Normal: `POST http://192.168.10.100:5006/api/internal/ascends/gsu/sales/sales-invoice/normal/pdf`
- Surat Jalan (GSU) - Panjang: `POST http://192.168.10.100:5006/api/internal/ascends/gsu/sales/surat-jalan/panjang/pdf`
- Surat Jalan (GSU) - Normal: `POST http://192.168.10.100:5006/api/internal/ascends/gsu/sales/surat-jalan/normal/pdf`

Input XML sama seperti report Ascends XML lain:
- `multipart/form-data` field `xml_file`
- field `xml`
- raw XML body

Response sukses:
- `200 application/pdf`
- `Content-Disposition: inline`
