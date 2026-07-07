<?php

namespace App\Http\Requests;

class GenerateAscendsEmployeeListReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'xml' => ['nullable', 'string'],
            'xml_file' => ['nullable', 'file', 'max:204800'],
            'preview_pdf' => ['nullable', 'boolean'],
            'company' => ['nullable', 'in:RU,GSU,UC,ru,gsu,uc'],
            'DB_CompanyName' => ['nullable', 'string', 'max:100'],
            'Sys_Username' => ['nullable', 'string', 'max:100'],
            'Sys_UserName' => ['nullable', 'string', 'max:100'],
            'report_type' => ['nullable', 'in:list_karyawan,gsu_list_karyawan,uc_list_karyawan,uc_karyawan_aktif_per_departemen,uc_daftar_karyawan,uc_daftar_karyawan_berdasarkan_abjad,uc_data_karyawan_status_kerja,uc_karyawan_masuk_per_departemen_per_tanggal_masuk,karyawan_per_masa_kerja,data_karyawan_status_kerja,daftar_karyawan_berdasarkan_abjad,daftar_karyawan,karyawan_aktif_per_departemen,karyawan_per_agama,karyawan_per_etnis,karyawan_per_level,karyawan_per_umur,karyawan_per_departemen_per_jabatan,list_karyawan_habis_kontrak,absensi_briefing_harian_ru,absensi_briefing_harian_gsu,rekapitulasi_absensi_briefing_harian_ru,rekapitulasi_absensi_briefing_harian_gsu,data_peserta_makan_siang_ibadah_aula_per_departemen,data_peserta_makan_siang_shalat_jumat_per_departemen,absensi_individu,kehadiran_kru_stick,kehadiran_kru_racip,kehadiran_kru_bahan_baku,persentase_kehadiran_mingguan_per_departemen,persentase_kehadiran_bulanan,rekapitulasi_kehadiran_kurang_93_tahunan,rekapitulasi_pengabaian_keterlambatan_tahunan,pengabaian_keterlambatan_kehadiran_manual,durasi_denda_keterlambatan,lembur_bulanan,perbandingan_kehadiran_per_bulan,keterlambatan_kehadiran_briefing_harian,daftar_libur_cuti_bersama,pendapatan_lain_lain,surat_peringatan,ketidakhadiran_bulanan,sales_invoice,sales_invoice_panjang,sales_invoice_normal,gsu_sales_invoice_panjang,gsu_sales_invoice_normal,surat_jalan,surat_jalan_panjang,surat_jalan_normal,gsu_surat_jalan_panjang,gsu_surat_jalan_normal'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:1900,2100'],
            'Date.StartDate' => ['nullable', 'date'],
            'Date.EndDate' => ['nullable', 'date'],
            'AttendanceDate.StartDate' => ['nullable', 'date'],
            'AttendanceDate.EndDate' => ['nullable', 'date'],
            'report_date' => ['nullable', 'date'],
            'tanggal' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
            'DateInput' => ['nullable', 'date'],
            'date_input' => ['nullable', 'date'],
            'DateRange.StartDate' => ['nullable', 'date'],
            'DateRange_StartDate' => ['nullable', 'date'],
            'DateRange_x0020_StartDate' => ['nullable', 'date'],
            'DateRange.EndDate' => ['nullable', 'date'],
            'DateRange_EndDate' => ['nullable', 'date'],
            'DateRange_x0020_EndDate' => ['nullable', 'date'],
            'group' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'divisi' => ['nullable', 'string', 'max:100'],
            'Pilih Divisi' => ['nullable', 'string', 'max:100'],
            'Pilih_x0020_Divisi' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'Category' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'Status' => ['nullable', 'string', 'max:50'],
            'tipe' => ['nullable', 'string', 'max:50'],
            'Tipe' => ['nullable', 'string', 'max:50'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'Kategori' => ['nullable', 'string', 'max:50'],
            'pilih_kategori' => ['nullable', 'string', 'max:50'],
            'PilihKategori' => ['nullable', 'string', 'max:50'],
            'Pilih_x0020_Kategori' => ['nullable', 'string', 'max:50'],
            'Pilih Type' => ['nullable', 'string', 'max:50'],
            'Pilih_x0020_Type' => ['nullable', 'string', 'max:50'],
            'pilih_type' => ['nullable', 'string', 'max:50'],
            'pilihType' => ['nullable', 'string', 'max:50'],
            'Pilih Tipe' => ['nullable', 'string', 'max:50'],
            'Pilih_x0020_Tipe' => ['nullable', 'string', 'max:50'],
            'pilih_tipe' => ['nullable', 'string', 'max:50'],
            'pilihTipe' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'],
            'Type' => ['nullable', 'string', 'max:50'],
            'penanggung_jawab' => ['nullable', 'string', 'max:100'],
            'responsible_person' => ['nullable', 'string', 'max:100'],
            'tema' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:50'],
            'kode_karyawan' => ['nullable', 'string', 'max:50'],
            'employee_name' => ['nullable', 'string', 'max:150'],
            'nama_karyawan' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function xmlPayload(): ?string
    {
        $xml = $this->input('xml');
        if (is_string($xml) && trim($xml) !== '') {
            return $xml;
        }

        $file = $this->file('xml_file');
        if ($file !== null && $file->isValid()) {
            $contents = file_get_contents((string) $file->getRealPath());

            return is_string($contents) && trim($contents) !== '' ? $contents : null;
        }

        $rawBody = trim($this->getContent());
        if (str_starts_with($rawBody, '<')) {
            return $rawBody;
        }

        return null;
    }

    public function xmlSourceLabel(): ?string
    {
        $file = $this->file('xml_file');
        if ($file !== null && $file->isValid()) {
            return 'request upload: '.$file->getClientOriginalName();
        }

        if (is_string($this->input('xml')) && trim((string) $this->input('xml')) !== '') {
            return 'request field: xml';
        }

        if (str_starts_with(trim($this->getContent()), '<')) {
            return 'request raw xml body';
        }

        return null;
    }
}
