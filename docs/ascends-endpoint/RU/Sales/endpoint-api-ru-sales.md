- Sales Invoice (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/sales/sales-invoice/pdf`
- Sales Invoice (RU) - Panjang: `POST http://192.168.10.100:5006/api/internal/ascends/ru/sales/sales-invoice/panjang/pdf`
- Sales Invoice (RU) - Normal: `POST http://192.168.10.100:5006/api/internal/ascends/ru/sales/sales-invoice/normal/pdf`
- Surat Jalan (RU) - Panjang: `POST http://192.168.10.100:5006/api/internal/ascends/ru/sales/surat-jalan/panjang/pdf`
- Surat Jalan (RU) - Normal: `POST http://192.168.10.100:5006/api/internal/ascends/ru/sales/surat-jalan/normal/pdf`


Input XML sama seperti report Ascends XML lain:
- `multipart/form-data` field `xml_file`
- field `xml`
- raw XML body

Response sukses:
- `200 application/pdf`
- `Content-Disposition: inline`
