<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAscendsEmployeeListReportRequest;
use App\Services\Ascends\Ru\Hrm\AbsensiBriefingHarianGsuReportService;
use App\Services\Ascends\Ru\Hrm\AbsensiBriefingHarianReportService;
use App\Services\Ascends\Ru\Hrm\AbsensiIndividuReportService;
use App\Services\Ascends\Ru\Hrm\DaftarKaryawanBerdasarkanAbjadReportService;
use App\Services\Ascends\Ru\Hrm\DaftarKaryawanReportService;
use App\Services\Ascends\Ru\Hrm\DaftarLiburCutiBersamaReportService;
use App\Services\Ascends\Ru\Hrm\DataKaryawanStatusKerjaReportService;
use App\Services\Ascends\Ru\Hrm\DataPesertaMakanSiangIbadahAulaPerDepartemenReportService;
use App\Services\Ascends\Ru\Hrm\DataPesertaMakanSiangShalatJumatPerDepartemenReportService;
use App\Services\Ascends\Ru\Hrm\DurasiDendaKeterlambatanReportService;
use App\Services\Ascends\Ru\Hrm\EmployeeListReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanAktifPerDepartemenReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanMasukPerDepartemenPerTanggalMasukReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerAgamaReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerDepartemenPerJabatanReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerEtnisReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerLevelReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerMasaKerjaReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerUmurReportService;
use App\Services\Ascends\Ru\Hrm\KehadiranKkKtStReportService;
use App\Services\Ascends\Ru\Hrm\KehadiranKruBahanBakuReportService;
use App\Services\Ascends\Ru\Hrm\KehadiranKruRacipReportService;
use App\Services\Ascends\Ru\Hrm\KehadiranKruStickReportService;
use App\Services\Ascends\Ru\Hrm\KeterlambatanKehadiranBriefingHarianReportService;
use App\Services\Ascends\Ru\Hrm\KetidakhadiranBulananReportService;
use App\Services\Ascends\Ru\Hrm\LemburBulananReportService;
use App\Services\Ascends\Ru\Hrm\ListKaryawanHabisKontrakReportService;
use App\Services\Ascends\Ru\Hrm\LossTimeReportService;
use App\Services\Ascends\Ru\Hrm\PendapatanLainLainReportService;
use App\Services\Ascends\Ru\Hrm\PengabaianKeterlambatanKehadiranManualReportService;
use App\Services\Ascends\Ru\Hrm\PerbandinganJumlahKaryawanTahunanPerBulanReportService;
use App\Services\Ascends\Ru\Hrm\PerbandinganKehadiranPerBulanReportService;
use App\Services\Ascends\Ru\Hrm\PersentaseKehadiranBulananReportService;
use App\Services\Ascends\Ru\Hrm\PersentaseKehadiranMingguanPerDepartemenReportService;
use App\Services\Ascends\Ru\Hrm\RekapitulasiAbsensiBriefingHarianGsuReportService;
use App\Services\Ascends\Ru\Hrm\RekapitulasiAbsensiBriefingHarianReportService;
use App\Services\Ascends\Ru\Hrm\RekapitulasiKehadiranKurang93TahunanReportService;
use App\Services\Ascends\Ru\Hrm\RekapitulasiPengabaianKeterlambatanTahunanReportService;
use App\Services\Ascends\Ru\Hrm\SuratPeringatanReportService;
use App\Services\Ascends\Ru\Hrm\UsiaGenerasiTahunKelahiranMasaKerjaReportService;
use App\Services\Ascends\Ru\Sales\SalesInvoiceReportService;
use App\Services\Ascends\Ru\Sales\SuratJalanReportService;
use App\Services\Ascends\Shared\Analysis\AdjustmentLemariReportService;
use App\Services\Ascends\Shared\Analysis\AktifitasStockGsuPerGudangReportService;
use App\Services\Ascends\Shared\Analysis\AktifitasStockGsuReportService;
use App\Services\Ascends\Shared\Analysis\DOCustomerBelumTerkirimReportService;
use App\Services\Ascends\Shared\Analysis\DOLemariBelumTerkirimReportService;
use App\Services\Ascends\Shared\Analysis\DOPerKategoriBelumTerkirimReportService;
use App\Services\Ascends\Shared\Analysis\KhususPlastikKabinetReportService;
use App\Services\Ascends\Shared\Analysis\KursiAdjustmentReportService;
use App\Services\Ascends\Shared\Analysis\LaporanHppDanStockReportService;
use App\Services\Ascends\Shared\Analysis\LemariAdjustmentReportService;
use App\Services\Ascends\Shared\Analysis\ListDOBelumTerkirimReportService;
use App\Services\Ascends\Shared\Analysis\PengirimanLemariReportService;
use App\Services\Ascends\Shared\Analysis\PenyesuaianPersediaanReportService;
use App\Services\Ascends\Shared\Analysis\RekapanValueSuratJalanReportService;
use App\Services\Ascends\Shared\Hrm\EmployeeTerminationReportService;
use App\Services\Ascends\Shared\Hrm\MppTahunanPerDivisiGsuReportService;
use App\Services\Ascends\Shared\Hrm\ThrReportService;
use App\Services\Ascends\Shared\Production\HasilBrokerPerHariReportService;
use App\Services\Ascends\Shared\Production\HasilBrokerPerKategoriReportService;
use App\Services\Ascends\Shared\Production\HasilBrokerPerMesinReportService;
use App\Services\Ascends\Shared\Production\HasilCuciPerHariReportService;
use App\Services\Ascends\Shared\Production\HasilCuciPerMesinReportService;
use App\Services\Ascends\Shared\Production\HasilCuciPerSupplierReportService;
use App\Services\Ascends\Shared\Production\HasilProduksiPerMesinReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use RuntimeException;

class AscendXmlTestController extends Controller
{
    public function index(): View
    {
        return view('ascends.test-upload');
    }

    public function pdf(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
        KaryawanPerMasaKerjaReportService $karyawanPerMasaKerjaReportService,
        DataKaryawanStatusKerjaReportService $dataKaryawanStatusKerjaReportService,
        DaftarKaryawanBerdasarkanAbjadReportService $daftarKaryawanBerdasarkanAbjadReportService,
        DaftarKaryawanReportService $daftarKaryawanReportService,
        KaryawanAktifPerDepartemenReportService $karyawanAktifPerDepartemenReportService,
        KaryawanMasukPerDepartemenPerTanggalMasukReportService $karyawanMasukPerDepartemenPerTanggalMasukReportService,
        KaryawanPerAgamaReportService $karyawanPerAgamaReportService,
        KaryawanPerEtnisReportService $karyawanPerEtnisReportService,
        KaryawanPerLevelReportService $karyawanPerLevelReportService,
        KaryawanPerUmurReportService $karyawanPerUmurReportService,
        KaryawanPerDepartemenPerJabatanReportService $karyawanPerDepartemenPerJabatanReportService,
        ListKaryawanHabisKontrakReportService $listKaryawanHabisKontrakReportService,
        PerbandinganJumlahKaryawanTahunanPerBulanReportService $perbandinganJumlahKaryawanTahunanPerBulanReportService,
        PersentaseKehadiranMingguanPerDepartemenReportService $persentaseKehadiranMingguanPerDepartemenReportService,
        PersentaseKehadiranBulananReportService $persentaseKehadiranBulananReportService,
        RekapitulasiKehadiranKurang93TahunanReportService $rekapitulasiKehadiranKurang93TahunanReportService,
        RekapitulasiPengabaianKeterlambatanTahunanReportService $rekapitulasiPengabaianKeterlambatanTahunanReportService,
        PengabaianKeterlambatanKehadiranManualReportService $pengabaianKeterlambatanKehadiranManualReportService,
        AbsensiBriefingHarianReportService $absensiBriefingHarianReportService,
        AbsensiBriefingHarianGsuReportService $absensiBriefingHarianGsuReportService,
        RekapitulasiAbsensiBriefingHarianReportService $rekapitulasiAbsensiBriefingHarianReportService,
        RekapitulasiAbsensiBriefingHarianGsuReportService $rekapitulasiAbsensiBriefingHarianGsuReportService,
        DataPesertaMakanSiangIbadahAulaPerDepartemenReportService $dataPesertaMakanSiangIbadahAulaPerDepartemenReportService,
        DataPesertaMakanSiangShalatJumatPerDepartemenReportService $dataPesertaMakanSiangShalatJumatPerDepartemenReportService,
        AbsensiIndividuReportService $absensiIndividuReportService,
        KehadiranKruStickReportService $kehadiranKruStickReportService,
        KehadiranKruRacipReportService $kehadiranKruRacipReportService,
        KehadiranKruBahanBakuReportService $kehadiranKruBahanBakuReportService,
        KetidakhadiranBulananReportService $ketidakhadiranBulananReportService,
        SuratPeringatanReportService $suratPeringatanReportService,
        SalesInvoiceReportService $salesInvoiceReportService,
        SuratJalanReportService $suratJalanReportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('File XML wajib diupload untuk generate PDF.');
            }

            $selectedReport = (string) $request->input('report_type', 'list_karyawan');
            $reportDefinition = $this->testReportDefinition($selectedReport);
            $selectedReportService = match ($selectedReport) {
                'gsu_list_karyawan' => $reportService,
                'uc_list_karyawan' => $reportService,
                'uc_karyawan_aktif_per_departemen' => $karyawanAktifPerDepartemenReportService,
                'uc_daftar_karyawan' => $daftarKaryawanReportService,
                'uc_daftar_karyawan_berdasarkan_abjad' => $daftarKaryawanBerdasarkanAbjadReportService,
                'uc_data_karyawan_status_kerja' => $dataKaryawanStatusKerjaReportService,
                'uc_karyawan_masuk_per_departemen_per_tanggal_masuk' => $karyawanMasukPerDepartemenPerTanggalMasukReportService,
                'karyawan_per_masa_kerja' => $karyawanPerMasaKerjaReportService,
                'data_karyawan_status_kerja' => $dataKaryawanStatusKerjaReportService,
                'daftar_karyawan_berdasarkan_abjad' => $daftarKaryawanBerdasarkanAbjadReportService,
                'daftar_karyawan' => $daftarKaryawanReportService,
                'karyawan_aktif_per_departemen' => $karyawanAktifPerDepartemenReportService,
                'karyawan_per_agama' => $karyawanPerAgamaReportService,
                'karyawan_per_etnis' => $karyawanPerEtnisReportService,
                'karyawan_per_level' => $karyawanPerLevelReportService,
                'karyawan_per_umur' => $karyawanPerUmurReportService,
                'karyawan_per_departemen_per_jabatan' => $karyawanPerDepartemenPerJabatanReportService,
                'list_karyawan_habis_kontrak' => $listKaryawanHabisKontrakReportService,
                'perbandingan_jumlah_karyawan_tahunan_per_bulan' => $perbandinganJumlahKaryawanTahunanPerBulanReportService,
                'persentase_kehadiran_mingguan_per_departemen' => $persentaseKehadiranMingguanPerDepartemenReportService,
                'persentase_kehadiran_bulanan' => $persentaseKehadiranBulananReportService,
                'rekapitulasi_kehadiran_kurang_93_tahunan' => $rekapitulasiKehadiranKurang93TahunanReportService,
                'rekapitulasi_pengabaian_keterlambatan_tahunan' => $rekapitulasiPengabaianKeterlambatanTahunanReportService,
                'pengabaian_keterlambatan_kehadiran_manual' => $pengabaianKeterlambatanKehadiranManualReportService,
                'absensi_briefing_harian_ru' => $absensiBriefingHarianReportService,
                'absensi_briefing_harian_gsu' => $absensiBriefingHarianGsuReportService,
                'rekapitulasi_absensi_briefing_harian_ru' => $rekapitulasiAbsensiBriefingHarianReportService,
                'rekapitulasi_absensi_briefing_harian_gsu' => $rekapitulasiAbsensiBriefingHarianGsuReportService,
                'data_peserta_makan_siang_ibadah_aula_per_departemen' => $dataPesertaMakanSiangIbadahAulaPerDepartemenReportService,
                'data_peserta_makan_siang_shalat_jumat_per_departemen' => $dataPesertaMakanSiangShalatJumatPerDepartemenReportService,
                'absensi_individu' => $absensiIndividuReportService,
                'kehadiran_kru_stick' => $kehadiranKruStickReportService,
                'kehadiran_kru_racip' => $kehadiranKruRacipReportService,
                'kehadiran_kru_bahan_baku' => $kehadiranKruBahanBakuReportService,
                'ketidakhadiran_bulanan' => $ketidakhadiranBulananReportService,
                'surat_peringatan' => $suratPeringatanReportService,
                'sales_invoice' => $salesInvoiceReportService,
                'sales_invoice_panjang' => $salesInvoiceReportService,
                'sales_invoice_normal' => $salesInvoiceReportService,
                'gsu_sales_invoice_panjang' => $salesInvoiceReportService,
                'gsu_sales_invoice_normal' => $salesInvoiceReportService,
                'surat_jalan' => $suratJalanReportService,
                'surat_jalan_panjang' => $suratJalanReportService,
                'surat_jalan_normal' => $suratJalanReportService,
                'gsu_surat_jalan_panjang' => $suratJalanReportService,
                'gsu_surat_jalan_normal' => $suratJalanReportService,
                default => $reportService,
            };

            $reportData = match ($selectedReport) {
                'list_karyawan_habis_kontrak' => $listKaryawanHabisKontrakReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->listKaryawanHabisKontrakFilters($request)
                ),
                'absensi_briefing_harian_ru' => $absensiBriefingHarianReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->absensiBriefingHarianFilters($request)
                ),
                'absensi_briefing_harian_gsu' => $absensiBriefingHarianGsuReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->absensiBriefingHarianFilters($request)
                ),
                'rekapitulasi_absensi_briefing_harian_ru' => $rekapitulasiAbsensiBriefingHarianReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->absensiBriefingHarianFilters($request)
                ),
                'rekapitulasi_absensi_briefing_harian_gsu' => $rekapitulasiAbsensiBriefingHarianGsuReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->absensiBriefingHarianFilters($request)
                ),
                'data_peserta_makan_siang_ibadah_aula_per_departemen' => $dataPesertaMakanSiangIbadahAulaPerDepartemenReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullMealParticipantFilters($request)
                ),
                'data_peserta_makan_siang_shalat_jumat_per_departemen' => $dataPesertaMakanSiangShalatJumatPerDepartemenReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullMealParticipantFilters($request)
                ),
                'absensi_individu' => $absensiIndividuReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->absensiIndividuFilters($request)
                ),
                'kehadiran_kru_stick' => $kehadiranKruStickReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullPeriodFilters($request)
                ),
                'kehadiran_kru_racip' => $kehadiranKruRacipReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullPeriodFilters($request)
                ),
                'kehadiran_kru_bahan_baku' => $kehadiranKruBahanBakuReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullPeriodFilters($request)
                ),
                'persentase_kehadiran_mingguan_per_departemen' => $persentaseKehadiranMingguanPerDepartemenReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullPeriodFilters($request)
                ),
                'persentase_kehadiran_bulanan' => $persentaseKehadiranBulananReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullTypeFilters($request)
                ),
                'rekapitulasi_kehadiran_kurang_93_tahunan' => $rekapitulasiKehadiranKurang93TahunanReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullStatusFilters($request)
                ),
                'rekapitulasi_pengabaian_keterlambatan_tahunan' => $rekapitulasiPengabaianKeterlambatanTahunanReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullStatusFilters($request)
                ),
                'pengabaian_keterlambatan_kehadiran_manual' => $pengabaianKeterlambatanKehadiranManualReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->attendanceFullStatusFilters($request)
                ),
                'ketidakhadiran_bulanan' => $ketidakhadiranBulananReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->absenceFilters($request)
                ),
                'surat_peringatan' => $suratPeringatanReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file',
                    $this->warningNoticeFilters($request)
                ),
                default => $selectedReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request upload: xml_file'
                ),
            };
            if (in_array($selectedReport, ['list_karyawan_habis_kontrak', 'perbandingan_jumlah_karyawan_tahunan_per_bulan'], true)) {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $reportName = $selectedReport === 'list_karyawan_habis_kontrak'
                    ? 'Laporan List Karyawan Habis Kontrak'
                    : 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan';
                $title = $this->sharedHrmDisplayTitle($reportName, $company);

                $reportData['company'] = $company;
                $reportData['title'] = $title;
                $reportDefinition['filename'] = $this->sharedHrmEmployeeListFilename($reportName, $company);
            }
            if ($selectedReport === 'absensi_briefing_harian_ru') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $group = trim((string) ($reportData['group'] ?? $request->input('Pilih Group', $request->input('Pilih_Group', $request->input('group', 'VKD')))));

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Absensi Briefing Harian ({$company}) - {$group}";
                $reportDefinition['filename'] = "Attendance Full - Laporan Absensi Briefing Harian ({$company}) - {$group}.pdf";
            }
            if ($selectedReport === 'rekapitulasi_absensi_briefing_harian_ru') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Rekapitulasi Absensi Briefing Harian ({$company})";
                $reportDefinition['filename'] = "Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian ({$company}).pdf";
            }
            if ($selectedReport === 'rekapitulasi_absensi_briefing_harian_gsu') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Rekapitulasi Absensi Briefing Harian ({$company})";
                $reportDefinition['filename'] = "Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian ({$company}).pdf";
            }
            if ($selectedReport === 'data_peserta_makan_siang_ibadah_aula_per_departemen') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');

                $reportData['company'] = $company;
                $reportData['title'] = 'Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen';
                $reportDefinition['filename'] = "Attendance Full - Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen ({$company}).pdf";
            }
            if ($selectedReport === 'data_peserta_makan_siang_shalat_jumat_per_departemen') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');

                $reportData['company'] = $company;
                $reportData['title'] = 'Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen';
                $reportDefinition['filename'] = "Attendance Full - Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen ({$company}).pdf";
            }
            if ($selectedReport === 'kehadiran_kru_stick') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Kehadiran Kru Stick ({$company})";
                $reportDefinition['filename'] = "Attendance Full - Laporan Kehadiran Kru Stick ({$company}).pdf";
            }
            if ($selectedReport === 'kehadiran_kru_racip') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut ({$company})";
                $reportDefinition['filename'] = "Attendance Full - Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut ({$company}).pdf";
            }
            if ($selectedReport === 'kehadiran_kru_bahan_baku') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Kehadiran Kru Bahan Baku ({$company})";
                $reportDefinition['filename'] = "Attendance Full - Laporan Kehadiran Kru Bahan Baku ({$company}).pdf";
            }
            if ($selectedReport === 'persentase_kehadiran_mingguan_per_departemen') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Persentase Kehadiran Mingguan Per Departemen ({$company})";
                $reportDefinition['filename'] = "Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen ({$company}).pdf";
            }
            if ($selectedReport === 'persentase_kehadiran_bulanan') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $type = trim((string) ($reportData['type'] ?? $this->attendanceFullType($request)));

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Persentase Kehadiran Bulanan {$type} ({$company})";
                $reportDefinition['filename'] = 'Attendance Full - Laporan Persentase Kehadiran Bulanan '.str_replace('/', ' ', $type)." ({$company}).pdf";
            }
            if ($selectedReport === 'rekapitulasi_kehadiran_kurang_93_tahunan') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $status = trim((string) ($reportData['status'] ?? $this->attendanceFullStatus($request)));

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Rekapitulasi Kehadiran < 93 % Tahunan ({$status}) ({$company})";
                $reportDefinition['filename'] = 'Attendance Full - Laporan Rekapitulasi Kehadiran Kurang 93 Persen Tahunan '.str_replace('/', ' ', $status)." ({$company}).pdf";
            }
            if ($selectedReport === 'rekapitulasi_pengabaian_keterlambatan_tahunan') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $status = trim((string) ($reportData['status'] ?? $this->attendanceFullStatus($request)));

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan ({$status}) ({$company})";
                $reportDefinition['filename'] = 'Attendance Full - Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan '.str_replace('/', ' ', $status)." ({$company}).pdf";
            }
            if ($selectedReport === 'pengabaian_keterlambatan_kehadiran_manual') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $status = trim((string) ($reportData['status'] ?? $this->attendanceFullStatus($request)));

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Pengabaian Keterlambatan & Kehadiran Manual ({$status}) Per Departemen ({$company})";
                $reportDefinition['filename'] = 'Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual '.str_replace('/', ' ', $status)." Per Departemen ({$company}).pdf";
            }
            if ($selectedReport === 'ketidakhadiran_bulanan') {
                $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'RU');
                $tipe = trim((string) ($reportData['tipe'] ?? $this->absenceCategory($request)));

                $reportData['company'] = $company;
                $reportData['title'] = "Laporan Ketidakhadiran Bulanan ({$company}) - {$tipe}";
                $reportDefinition['filename'] = "Absence - Laporan Ketidakhadiran Bulanan ({$company}) - ".str_replace('/', ' ', $tipe).'.pdf';
            }

        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['xml_file' => $exception->getMessage()]);
        }

        $reportData = $this->applyAscendSystemFields($request, $reportData);

        $pdf = $pdfGenerator->render($reportDefinition['view'], [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => $reportDefinition['format'] ?? 'A4',
            'pdf_orientation' => $reportDefinition['orientation'],
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$reportDefinition['filename'].'"',
        ]);
    }

    public function apiPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.list_karyawan.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A3',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="List-Karyawan-RU.pdf"',
        ]);
    }

    public function apiUcListKaryawanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.uc.hrm.list_karyawan.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="List-Karyawan-UC.pdf"',
        ]);
    }

    public function apiGsuListKaryawanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.gsu.hrm.list_karyawan.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="List-Karyawan-GSU.pdf"',
        ]);
    }

    public function apiUcKaryawanAktifPerDepartemenPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanAktifPerDepartemenReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.uc.hrm.karyawan_aktif_per_departemen.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Aktif Per Departemen (UC).pdf"',
        ]);
    }

    public function apiKaryawanPerMasaKerjaPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanPerMasaKerjaReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_per_masa_kerja.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Per Masa Kerja (RU).pdf"',
        ]);
    }

    public function apiDataKaryawanStatusKerjaPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DataKaryawanStatusKerjaReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.data_karyawan_status_kerja.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Data Karyawan (RU) - Status Kerja.pdf"',
        ]);
    }

    public function apiUcDataKaryawanStatusKerjaPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DataKaryawanStatusKerjaReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.uc.hrm.data_karyawan_status_kerja.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Data Karyawan (UC) - Status Kerja.pdf"',
        ]);
    }

    public function apiUcKaryawanMasukPerDepartemenPerTanggalMasukPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanMasukPerDepartemenPerTanggalMasukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
            $reportData['title'] = 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC)';
            $reportData['label'] = 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC)';
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.uc.hrm.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC).pdf"',
        ]);
    }

    public function apiSharedHrmKaryawanMasukPerDepartemenPerTanggalMasukPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanMasukPerDepartemenPerTanggalMasukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'UC');
            $title = $this->sharedHrmDisplayTitle('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $company);

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
            $reportData['company'] = $company;
            $reportData['title'] = $title;
            $reportData['label'] = $title;
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.employee_list.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->sharedHrmEmployeeListFilename('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $company).'"',
        ]);
    }

    public function apiSharedHrmReportPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        string $report,
        EmployeeListReportService $employeeListReportService,
        DaftarKaryawanReportService $daftarKaryawanReportService,
        DaftarKaryawanBerdasarkanAbjadReportService $daftarKaryawanBerdasarkanAbjadReportService,
        DataKaryawanStatusKerjaReportService $dataKaryawanStatusKerjaReportService,
        KaryawanAktifPerDepartemenReportService $karyawanAktifPerDepartemenReportService,
        KaryawanMasukPerDepartemenPerTanggalMasukReportService $karyawanMasukPerDepartemenPerTanggalMasukReportService,
        KaryawanPerAgamaReportService $karyawanPerAgamaReportService,
        KaryawanPerDepartemenPerJabatanReportService $karyawanPerDepartemenPerJabatanReportService,
        KaryawanPerEtnisReportService $karyawanPerEtnisReportService,
        KaryawanPerLevelReportService $karyawanPerLevelReportService,
        KaryawanPerMasaKerjaReportService $karyawanPerMasaKerjaReportService,
        KaryawanPerUmurReportService $karyawanPerUmurReportService,
        KehadiranKkKtStReportService $kehadiranKkKtStReportService,
        ListKaryawanHabisKontrakReportService $listKaryawanHabisKontrakReportService,
        PerbandinganJumlahKaryawanTahunanPerBulanReportService $perbandinganJumlahKaryawanTahunanPerBulanReportService,
        UsiaGenerasiTahunKelahiranMasaKerjaReportService $usiaGenerasiTahunKelahiranMasaKerjaReportService,
        PdfGenerator $pdfGenerator,
    ) {
        $reportService = match ($report) {
            'list-karyawan' => $employeeListReportService,
            'daftar-karyawan' => $daftarKaryawanReportService,
            'daftar-karyawan-berdasarkan-abjad' => $daftarKaryawanBerdasarkanAbjadReportService,
            'data-karyawan-status-kerja' => $dataKaryawanStatusKerjaReportService,
            'karyawan-aktif-per-departemen' => $karyawanAktifPerDepartemenReportService,
            'karyawan-masuk-per-departemen-per-tanggal-masuk' => $karyawanMasukPerDepartemenPerTanggalMasukReportService,
            'karyawan-per-agama' => $karyawanPerAgamaReportService,
            'karyawan-per-departemen-per-jabatan' => $karyawanPerDepartemenPerJabatanReportService,
            'karyawan-per-etnis' => $karyawanPerEtnisReportService,
            'karyawan-per-level' => $karyawanPerLevelReportService,
            'karyawan-per-masa-kerja' => $karyawanPerMasaKerjaReportService,
            'karyawan-per-umur' => $karyawanPerUmurReportService,
            'kehadiran-kk-kt-st' => $kehadiranKkKtStReportService,
            'list-karyawan-habis-kontrak' => $listKaryawanHabisKontrakReportService,
            'perbandingan-jumlah-karyawan-tahunan-per-bulan' => $perbandinganJumlahKaryawanTahunanPerBulanReportService,
            'usia-generasi-tahun-kelahiran-masa-kerja' => $usiaGenerasiTahunKelahiranMasaKerjaReportService,
        };

        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $reportDefinition = $this->sharedHrmReportDefinition($report, $company);

            $reportData = $report === 'list-karyawan-habis-kontrak'
                ? $listKaryawanHabisKontrakReportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request xml payload',
                    $this->listKaryawanHabisKontrakFilters($request)
                )
                : $reportService->buildReportDataFromXml(
                    $xmlPayload,
                    $request->xmlSourceLabel() ?? 'request xml payload'
                );
            $reportData['company'] = $company;
            $reportData['title'] = $reportDefinition['title'];
            $reportData['label'] = $reportDefinition['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render($reportDefinition['view'], [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$reportDefinition['filename'].'"',
        ]);
    }

    public function apiSharedHrmAbsensiBriefingHarianPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        AbsensiBriefingHarianReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->absensiBriefingHarianFilters($request)
            );

            $group = trim((string) ($reportData['group'] ?? $request->input('Pilih Group', $request->input('Pilih_Group', $request->input('group', 'VKD')))));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Absensi Briefing Harian ({$company}) - {$group}";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.absensi_briefing_harian_ru.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Absensi Briefing Harian ('.$company.') - '.$group.'.pdf"',
        ]);
    }

    public function apiSharedHrmAbsensiBriefingHarianGsuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        AbsensiBriefingHarianGsuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->absensiBriefingHarianFilters($request)
            );

            $group = trim((string) ($reportData['group'] ?? $request->input('Pilih Group', $request->input('Pilih_Group', $request->input('group', 'Bahan Baku, Washing & Broker')))));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Absensi Briefing Harian ({$company}) - {$group}";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.absensi_briefing_harian_gsu.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Absensi Briefing Harian ('.$company.') - '.$group.'.pdf"',
        ]);
    }

    public function apiSharedHrmRekapitulasiAbsensiBriefingHarianRuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        RekapitulasiAbsensiBriefingHarianReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->absensiBriefingHarianFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Rekapitulasi Absensi Briefing Harian ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_ru.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmRekapitulasiAbsensiBriefingHarianGsuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        RekapitulasiAbsensiBriefingHarianGsuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->absensiBriefingHarianFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Rekapitulasi Absensi Briefing Harian ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_gsu.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmAbsensiIndividuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        AbsensiIndividuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->absensiIndividuFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Absensi Individu ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.absensi_individu.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Absensi Individu ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmKehadiranKruStickPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KehadiranKruStickReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Kehadiran Kru Stick ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.kehadiran_kru_stick.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Kehadiran Kru Stick ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmDataPesertaMakanSiangIbadahAulaPerDepartemenPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DataPesertaMakanSiangIbadahAulaPerDepartemenReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullMealParticipantFilters($request) + ['company' => $company]
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.data_peserta_makan_siang_ibadah_aula_per_departemen.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => 2 + (count($reportData['dates'] ?? []) * 2),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmDataPesertaMakanSiangShalatJumatPerDepartemenPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DataPesertaMakanSiangShalatJumatPerDepartemenReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullMealParticipantFilters($request) + ['company' => $company]
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.data_peserta_makan_siang_shalat_jumat_per_departemen.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => 2 + (count($reportData['dates'] ?? []) * 2),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmKehadiranKruRacipPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KehadiranKruRacipReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.kehadiran_kru_racip.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmKehadiranKruBahanBakuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KehadiranKruBahanBakuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Kehadiran Kru Bahan Baku ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.kehadiran_kru_bahan_baku.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Kehadiran Kru Bahan Baku ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmPersentaseKehadiranMingguanPerDepartemenPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PersentaseKehadiranMingguanPerDepartemenReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request) + ['company' => $company]
            );

            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Persentase Kehadiran Mingguan Per Departemen ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.persentase_kehadiran_mingguan_per_departemen.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmPersentaseKehadiranBulananPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PersentaseKehadiranBulananReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullTypeFilters($request) + ['company' => $company]
            );

            $type = trim((string) ($reportData['type'] ?? $this->attendanceFullType($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Persentase Kehadiran Bulanan {$type} ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.persentase_kehadiran_bulanan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Persentase Kehadiran Bulanan '.str_replace('/', ' ', (string) ($reportData['type'] ?? 'KK KT')).' ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmRekapitulasiKehadiranKurang93TahunanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        RekapitulasiKehadiranKurang93TahunanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullStatusFilters($request) + ['company' => $company]
            );

            $status = trim((string) ($reportData['status'] ?? $this->attendanceFullStatus($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Rekapitulasi Kehadiran < 93 % Tahunan ({$status}) ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.rekapitulasi_kehadiran_kurang_93_tahunan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Rekapitulasi Kehadiran Kurang 93 Persen Tahunan '.str_replace('/', ' ', (string) ($reportData['status'] ?? 'KK KT')).' ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmRekapitulasiPengabaianKeterlambatanTahunanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        RekapitulasiPengabaianKeterlambatanTahunanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullStatusFilters($request) + ['company' => $company]
            );

            $status = trim((string) ($reportData['status'] ?? $this->attendanceFullStatus($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan ({$status}) ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.rekapitulasi_pengabaian_keterlambatan_tahunan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan '.str_replace('/', ' ', (string) ($reportData['status'] ?? 'KK KT')).' ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmPengabaianKeterlambatanKehadiranManualPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PengabaianKeterlambatanKehadiranManualReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullStatusFilters($request) + ['company' => $company]
            );

            $status = trim((string) ($reportData['status'] ?? $this->attendanceFullStatus($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Pengabaian Keterlambatan & Kehadiran Manual ({$status}) Per Departemen ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance_full.pengabaian_keterlambatan_kehadiran_manual.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual '.str_replace('/', ' ', (string) ($reportData['status'] ?? 'KK KT')).' Per Departemen ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmDurasiDendaKeterlambatanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DurasiDendaKeterlambatanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullTypeFilters($request) + [
                    'company' => $company,
                    'DateInput' => $request->input('DateInput') ?? $request->input('date_input'),
                ]
            );

            $type = trim((string) ($reportData['type'] ?? $this->attendanceFullType($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Durasi & Denda Keterlambatan ({$type}) Per Departemen ({$company})";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.late_sign_in.durasi_denda_keterlambatan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Late Sign In - Laporan Durasi & Denda Keterlambatan '.str_replace('/', ' ', (string) ($reportData['type'] ?? 'KK KT')).' Per Departemen ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmLemburBulananPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        LemburBulananReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullTypeFilters($request) + [
                    'company' => $company,
                    'Pilih Tipe' => $this->requestInputByAliases($request, ['Pilih Tipe', 'Pilih_x0020_Tipe', 'pilih_tipe', 'pilihTipe', 'tipe', 'Tipe']),
                ]
            );

            $type = trim((string) ($reportData['type'] ?? $this->attendanceFullType($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Lembur Bulanan ({$type}) Per Departemen";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.overtime.lembur_bulanan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Overtime - Laporan Lembur Bulanan '.str_replace('/', ' ', (string) ($reportData['type'] ?? 'KK KT')).' Per Departemen ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmPerbandinganKehadiranPerBulanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PerbandinganKehadiranPerBulanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request) + ['company' => $company]
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Perbandingan Kehadiran Per Bulan';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance.perbandingan_kehadiran_per_bulan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance - Laporan Perbandingan Kehadiran Per Bulan ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmKeterlambatanKehadiranBriefingHarianPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KeterlambatanKehadiranBriefingHarianReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request) + ['company' => $company]
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Keterlambatan Kehadiran Briefing Harian';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.attendance.keterlambatan_kehadiran_briefing_harian.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Attendance - Laporan Keterlambatan Kehadiran Briefing Harian ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmDaftarLiburCutiBersamaPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DaftarLiburCutiBersamaReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Daftar Libur Dan Cuti Bersama';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.holiday.daftar_libur_cuti_bersama.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Holiday - Daftar Libur Dan Cuti Bersama ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmPendapatanLainLainPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PendapatanLainLainReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Pendapatan Lain-Lain';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.other_income_deduction.pendapatan_lain_lain.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Other Income Deduction - Laporan Pendapatan Lain-Lain ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmSuratPeringatanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratPeringatanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->warningNoticeFilters($request)
            );

            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Surat Peringatan';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.warning_notice.surat_peringatan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Warning Notice - Laporan Surat Peringatan ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmLossTimePdf(
        GenerateAscendsEmployeeListReportRequest $request,
        LossTimeReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->lossTimeFilters($request)
            );

            $reportData['company'] = $company;
            $type = $reportData['type'];
            $reportData['title'] = 'Laporan Loss Time ('.$type.')';
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.loss_time.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Loss Time - Laporan Loss Time '.$type.' ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmMppTahunanPerDivisiGsuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        MppTahunanPerDivisiGsuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload, 'GSU');
            $filters = $this->mppTahunanPerDivisiGsuFilters($request);
            $filters['company'] = $company;
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters
            );

            $reportData['company'] = $company;
            $divisi = $reportData['divisi'] ?? '';
            $reportData['title'] = 'Laporan MPP Tahunan Per Divisi '.$divisi;
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.employee_list.mpp_tahunan_per_divisi_gsu.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Employee List - Laporan MPP Tahunan Per Divisi '.$divisi.' ('.$company.').pdf"',
        ]);
    }

    public function apiSharedHrmKetidakhadiranBulananPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KetidakhadiranBulananReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->absenceFilters($request)
            );

            $tipe = trim((string) ($reportData['tipe'] ?? $this->absenceCategory($request)));
            $reportData['company'] = $company;
            $reportData['title'] = "Laporan Ketidakhadiran Bulanan ({$company}) - {$tipe}";
            $reportData['label'] = $reportData['title'];
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.absence.ketidakhadiran_bulanan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Absence - Laporan Ketidakhadiran Bulanan ('.$company.') - '.str_replace('/', ' ', (string) ($reportData['tipe'] ?? 'KK/KT')).'.pdf"',
        ]);
    }

    public function apiSharedHrmEmployeeTerminationPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeTerminationReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $title = $this->sharedHrmDisplayTitle('Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar', $company);

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $this->attendanceFullPeriodFilters($request)
            );
            $reportData['company'] = $company;
            $reportData['title'] = $title;
            $reportData['label'] = $title;
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.employee_termination.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'subtitle' => $reportData['period_label'] ?? '',
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->sharedHrmEmployeeTerminationFilename('Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar', $company).'"',
        ]);
    }

    public function apiSharedHrmThrPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        ThrReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $company = $this->resolveSharedHrmCompany($request, $xmlPayload);
            $filters = $this->thrFilters($request);
            $filters['company'] = $company;
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters
            );

            $sections = $reportData['sections'] ?? [];
            $firstTitle = $sections[0]['title'] ?? $reportData['title'] ?? 'Laporan THR';
            $reportData['company'] = $company;
            $reportData['title'] = $firstTitle;
            $reportData['label'] = $firstTitle;
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.hrm.employee_list.thr.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        $thrType = $reportData['thr_type'] ?? 'Idul Fitri';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Employee List - Laporan THR ('.$thrType.') ('.$company.').pdf"',
        ]);
    }

    public function apiSharedAnalysisPenyesuaianPersediaanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PenyesuaianPersediaanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->penyesuaianPersediaanFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Penyesuaian Persediaan';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.adjustment_by_item.penyesuaian_persediaan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Adjustment By Item - Laporan Penyesuaian Persediaan ('.$company.').pdf"',
        ]);
    }

    public function apiAdjustmentLemariPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        AdjustmentLemariReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->penyesuaianPersediaanFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Adjustment Lemari';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.adjustment_by_item.adjustment.adjustment_lemari.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Adjustment By Item - Laporan Adjustment Lemari ('.$company.').pdf"',
        ]);
    }

    public function apiKursiAdjustmentPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KursiAdjustmentReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->penyesuaianPersediaanFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Adjustment Selisih Kursi';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.adjustment_by_item.adjustment.khusus_kursi.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Adjustment By Item - Laporan Adjustment Selisih Kursi ('.$company.').pdf"',
        ]);
    }

    public function apiLemariAdjustmentPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        LemariAdjustmentReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->penyesuaianPersediaanFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Adjustment Selisih Lemari';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.adjustment_by_item.adjustment.khusus_lemari.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Adjustment By Item - Laporan Adjustment Selisih Lemari ('.$company.').pdf"',
        ]);
    }

    public function apiRekapanValueSuratJalanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        RekapanValueSuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->penyesuaianPersediaanFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Rekapan Value Surat Jalan';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.goods_delivery_note.rekapan_value_surat_jalan.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Goods Delivery Note - Laporan Rekapan Value Surat Jalan ('.$company.').pdf"',
        ]);
    }

    public function apiPengirimanLemariPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        PengirimanLemariReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->penyesuaianPersediaanFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Pengiriman Lemari';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.goods_delivery_note.pengiriman_lemari.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Goods Delivery Note - Laporan Pengiriman Lemari ('.$company.').pdf"',
        ]);
    }

    public function apiListDOBelumTerkirimPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        ListDOBelumTerkirimReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->listDOBelumTerkirimFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan List DO Belum Terkirim';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.outstanding_undelivery_goods.list_do_belum_terkirim.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Outstanding Undelivery Goods - Laporan List DO Belum Terkirim ('.$company.').pdf"',
        ]);
    }

    public function apiDOCustomerBelumTerkirimPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DOCustomerBelumTerkirimReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->doCustomerBelumTerkirimFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan DO Customer Belum Terkirim';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.outstanding_undelivery_goods.do_customer_belum_terkirim.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Outstanding Undelivery Goods - Laporan DO Customer Belum Terkirim ('.$company.').pdf"',
        ]);
    }

    public function apiDOLemariBelumTerkirimPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DOLemariBelumTerkirimReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->doLemariBelumTerkirimFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan DO Lemari Belum Terkirim';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.outstanding_undelivery_goods.do_lemari_belum_terkirim.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Outstanding Undelivery Goods - Laporan DO Lemari Belum Terkirim ('.$company.').pdf"',
        ]);
    }

    public function apiDOPerKategoriBelumTerkirimPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DOPerKategoriBelumTerkirimReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->doPerKategoriBelumTerkirimFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan DO Per Kategori Belum Terkirim';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.outstanding_undelivery_goods.do_per_kategori_belum_terkirim.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Outstanding Undelivery Goods - Laporan DO Per Kategori Belum Terkirim ('.$company.').pdf"',
        ]);
    }

    public function apiLaporanHppDanStockPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        LaporanHppDanStockReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->laporanHppDanStockFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan HPP Dan Stock';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.stock_activities_summary.laporan_hpp_dan_stock.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Stock Activities Summary - Laporan HPP Dan Stock ('.$company.').pdf"',
        ]);
    }

    public function apiKhususPlastikKabinetPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KhususPlastikKabinetReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->khususPlastikKabinetFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Khusus Plastik Kabinet';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.stock_activities_summary.khusus_plastik_kabinet.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Stock Activities Summary - Laporan Khusus Plastik Kabinet ('.$company.').pdf"',
        ]);
    }

    public function apiAktifitasStockGsuPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        AktifitasStockGsuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->aktifitasStockGsuFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Ringkasan Valuasi Persediaan';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.stock_activities_summary.aktifitas_stock_gsu.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Stock Activities Summary - Ringkasan Valuasi Persediaan ('.$company.').pdf"',
        ]);
    }

    public function apiAktifitasStockGsuPerGudangPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        AktifitasStockGsuPerGudangReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->aktifitasStockGsuPerGudangFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Ringkasan Valuasi Persediaan Per Gudang';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.stock_activities_summary.aktifitas_stock_gsu_per_gudang.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Stock Activities Summary - Ringkasan Valuasi Persediaan Per Gudang ('.$company.').pdf"',
        ]);
    }

    public function apiHasilBrokerPerHariPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilBrokerPerHariReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Harian Hasil Broker';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_broker_per_hari.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Harian Hasil Broker ('.$company.').pdf"',
        ]);
    }

    public function apiHasilBrokerPerKategoriPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilBrokerPerKategoriReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Hasil Broker Per Kategori';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_broker_per_kategori.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Hasil Broker Per Kategori ('.$company.').pdf"',
        ]);
    }

    public function apiHasilBrokerPerMesinPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilBrokerPerMesinReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Hasil Broker Per Mesin';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_broker_per_mesin.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Hasil Broker Per Mesin ('.$company.').pdf"',
        ]);
    }

    public function apiHasilCuciPerHariPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilCuciPerHariReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Harian Hasil Cuci';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_cuci_per_hari.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Harian Hasil Cuci ('.$company.').pdf"',
        ]);
    }

    public function apiHasilCuciPerMesinPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilCuciPerMesinReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Hasil Cuci Per Mesin';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_cuci_per_mesin.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Hasil Cuci Per Mesin ('.$company.').pdf"',
        ]);
    }

    public function apiHasilCuciPerSupplierPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilCuciPerSupplierReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Hasil Cuci Per Supplier';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_cuci_per_supplier.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Hasil Cuci Per Supplier ('.$company.').pdf"',
        ]);
    }

    public function apiHasilProduksiPerMesinPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        HasilProduksiPerMesinReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $filters = $this->hasilBrokerPerHariFilters($request);
            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload',
                $filters,
            );
            $company = trim((string) ($request->input('DB_CompanyName') ?? 'GSU'));
            $reportData['company'] = $company;
            $reportData['title'] = 'Laporan Hasil Produksi Per Mesin';
            $reportData = $this->applyAscendSystemFields($request, $reportData);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.shared.analysis.production.hasil_produksi_per_mesin.pdf', [
            'company' => $company,
            'reportData' => $reportData,
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Production - Laporan Hasil Produksi Per Mesin ('.$company.').pdf"',
        ]);
    }

    public function apiDaftarKaryawanBerdasarkanAbjadPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DaftarKaryawanBerdasarkanAbjadReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.daftar_karyawan_berdasarkan_abjad.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Daftar Karyawan (RU) - Berdasarkan Abjad.pdf"',
        ]);
    }

    public function apiUcDaftarKaryawanBerdasarkanAbjadPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DaftarKaryawanBerdasarkanAbjadReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.uc.hrm.daftar_karyawan_berdasarkan_abjad.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Daftar Karyawan (UC) - Berdasarkan Abjad.pdf"',
        ]);
    }

    public function apiDaftarKaryawanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DaftarKaryawanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.daftar_karyawan.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Daftar Karyawan (RU).pdf"',
        ]);
    }

    public function apiUcDaftarKaryawanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        DaftarKaryawanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.uc.hrm.daftar_karyawan.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Daftar Karyawan (UC).pdf"',
        ]);
    }

    public function apiKaryawanAktifPerDepartemenPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanAktifPerDepartemenReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_aktif_per_departemen.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Aktif Per Departemen (RU).pdf"',
        ]);
    }

    public function apiKaryawanPerAgamaPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanPerAgamaReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_per_agama.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Per Agama (RU).pdf"',
        ]);
    }

    public function apiKaryawanPerEtnisPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanPerEtnisReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_per_etnis.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Per Etnis (RU).pdf"',
        ]);
    }

    public function apiKaryawanPerLevelPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanPerLevelReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_per_level.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Per Level (RU).pdf"',
        ]);
    }

    public function apiKaryawanPerUmurPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanPerUmurReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_per_umur.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Per Umur (RU).pdf"',
        ]);
    }

    public function apiKaryawanPerDepartemenPerJabatanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        KaryawanPerDepartemenPerJabatanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('ascends.ru.hrm.karyawan_per_departemen_per_jabatan.pdf', [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Laporan Karyawan Per Departemen Per Jabatan (RU).pdf"',
        ]);
    }

    public function apiSalesInvoicePdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSalesInvoicePdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.ru.sales.sales_invoice.panjang-pdf',
            'Sales Invoice (RU).pdf'
        );
    }

    public function apiSalesInvoicePanjangPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSalesInvoicePdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.ru.sales.sales_invoice.panjang-pdf',
            'Sales Invoice (RU) - Panjang.pdf'
        );
    }

    public function apiSalesInvoiceNormalPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSalesInvoicePdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.ru.sales.sales_invoice.normal-pdf',
            'Sales Invoice (RU) - Normal.pdf'
        );
    }

    public function apiGsuSalesInvoicePdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSalesInvoicePdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.gsu.sales.sales_invoice.panjang-pdf',
            'Sales Invoices (GSU).pdf'
        );
    }

    public function apiGsuSalesInvoicePanjangPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSalesInvoicePdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.gsu.sales.sales_invoice.panjang-pdf',
            'Sales Invoices (GSU) - Panjang.pdf'
        );
    }

    public function apiGsuSalesInvoiceNormalPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSalesInvoicePdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.gsu.sales.sales_invoice.normal-pdf',
            'Sales Invoices (GSU) - Normal.pdf'
        );
    }

    public function apiSuratJalanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSuratJalanPdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.ru.sales.surat_jalan.panjang-pdf',
            'Surat Jalan (RU) - Panjang.pdf'
        );
    }

    public function apiSuratJalanPanjangPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSuratJalanPdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.ru.sales.surat_jalan.panjang-pdf',
            'Surat Jalan (RU) - Panjang.pdf'
        );
    }

    public function apiSuratJalanNormalPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSuratJalanPdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.ru.sales.surat_jalan.normal-pdf',
            'Surat Jalan (RU) - Normal.pdf'
        );
    }

    public function apiGsuSuratJalanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSuratJalanPdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.gsu.sales.surat_jalan.panjang-pdf',
            'Surat Jalan (GSU).pdf'
        );
    }

    public function apiGsuSuratJalanPanjangPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSuratJalanPdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.gsu.sales.surat_jalan.panjang-pdf',
            'Surat Jalan (GSU) - Panjang.pdf'
        );
    }

    public function apiGsuSuratJalanNormalPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderSuratJalanPdf(
            $request,
            $reportService,
            $pdfGenerator,
            'ascends.gsu.sales.surat_jalan.normal-pdf',
            'Surat Jalan (GSU) - Normal.pdf'
        );
    }

    private function renderSuratJalanPdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
        string $view,
        string $filename,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render($view, [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function renderSalesInvoicePdf(
        GenerateAscendsEmployeeListReportRequest $request,
        SalesInvoiceReportService $reportService,
        PdfGenerator $pdfGenerator,
        string $view,
        string $filename,
    ) {
        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render($view, [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array{view: string, title: string, filename: string}
     */
    private function sharedHrmReportDefinition(string $report, string $company): array
    {
        return match ($report) {
            'list-karyawan' => [
                'view' => 'ascends.shared.hrm.employee_list.list_karyawan.pdf',
                'title' => $this->sharedHrmDisplayTitle('List Karyawan', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('List Karyawan', $company),
            ],
            'daftar-karyawan' => [
                'view' => 'ascends.shared.hrm.employee_list.daftar_karyawan.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Daftar Karyawan', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Daftar Karyawan', $company),
            ],
            'daftar-karyawan-berdasarkan-abjad' => [
                'view' => 'ascends.shared.hrm.employee_list.daftar_karyawan_berdasarkan_abjad.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Daftar Karyawan Berdasarkan Abjad', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Daftar Karyawan Berdasarkan Abjad', $company),
            ],
            'data-karyawan-status-kerja' => [
                'view' => 'ascends.shared.hrm.employee_list.data_karyawan_status_kerja.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Data Karyawan Staff, Karyawan Tetap & Karyawan Kontrak Berdasarkan Status Kerja', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Data Karyawan Staff, Karyawan Tetap & Karyawan Kontrak Berdasarkan Status Kerja', $company),
            ],
            'karyawan-aktif-per-departemen' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_aktif_per_departemen.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Aktif Per Departemen', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Aktif Per Departemen', $company),
            ],
            'karyawan-masuk-per-departemen-per-tanggal-masuk' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $company),
            ],
            'karyawan-per-agama' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_per_agama.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Per Agama', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Per Agama', $company),
            ],
            'karyawan-per-departemen-per-jabatan' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_per_departemen_per_jabatan.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Per Departemen Per Jabatan', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Per Departemen Per Jabatan', $company),
            ],
            'karyawan-per-etnis' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_per_etnis.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Per Etnis', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Per Etnis', $company),
            ],
            'karyawan-per-level' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_per_level.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Per Level', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Per Level', $company),
            ],
            'karyawan-per-masa-kerja' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_per_masa_kerja.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Per Masa Kerja', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Per Masa Kerja', $company),
            ],
            'karyawan-per-umur' => [
                'view' => 'ascends.shared.hrm.employee_list.karyawan_per_umur.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Karyawan Per Umur', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Karyawan Per Umur', $company),
            ],
            'kehadiran-kk-kt-st' => [
                'view' => 'ascends.shared.hrm.employee_list.kehadiran_kk_kt_st.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Kehadiran KK/KT/ST', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Kehadiran KK KT ST', $company),
            ],
            'list-karyawan-habis-kontrak' => [
                'view' => 'ascends.shared.hrm.employee_list.list_karyawan_habis_kontrak.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan List Karyawan Habis Kontrak', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan List Karyawan Habis Kontrak', $company),
            ],
            'perbandingan-jumlah-karyawan-tahunan-per-bulan' => [
                'view' => 'ascends.shared.hrm.employee_list.perbandingan_jumlah_karyawan_tahunan_per_bulan.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan', $company),
            ],
            'usia-generasi-tahun-kelahiran-masa-kerja' => [
                'view' => 'ascends.shared.hrm.employee_list.usia_generasi_tahun_kelahiran_masa_kerja.pdf',
                'title' => $this->sharedHrmDisplayTitle('Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja', $company),
                'filename' => $this->sharedHrmEmployeeListFilename('Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja', $company),
            ],
        };
    }

    private function sharedHrmDisplayTitle(string $reportName, string $company): string
    {
        return "{$reportName} ({$company})";
    }

    private function resolveSharedHrmCompany(
        GenerateAscendsEmployeeListReportRequest $request,
        string $xmlPayload,
        ?string $fallback = null,
    ): string {
        $dbCompanyName = trim((string) $request->input('DB_CompanyName', ''));
        if ($dbCompanyName !== '') {
            return $this->normalizeSharedHrmCompany($dbCompanyName);
        }

        $requestCompany = trim((string) $request->input('company', ''));
        if ($requestCompany !== '') {
            return $this->normalizeSharedHrmCompany($requestCompany);
        }

        if ($fallback !== null && trim($fallback) !== '') {
            return $this->normalizeSharedHrmCompany($fallback);
        }

        throw new RuntimeException('Field DB_CompanyName atau company wajib dikirim.');
    }

    private function normalizeSharedHrmCompany(string $company): string
    {
        $company = trim($company);
        $upperCompany = strtoupper($company);

        return in_array($upperCompany, ['RU', 'GSU', 'UC'], true) ? $upperCompany : $company;
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function applyAscendSystemFields(GenerateAscendsEmployeeListReportRequest $request, array $reportData): array
    {
        $username = trim((string) ($request->input('Sys_Username') ?? $request->input('Sys_UserName', '')));
        if ($username !== '') {
            $reportData['printed_by'] = $username;
        }

        return $reportData;
    }

    private function sharedHrmEmployeeListTitle(string $reportName, string $company): string
    {
        return "Employee List - {$reportName} ({$company})";
    }

    private function sharedHrmEmployeeListFilename(string $reportName, string $company): string
    {
        return $this->sharedHrmEmployeeListTitle($reportName, $company).'.pdf';
    }

    private function sharedHrmEmployeeTerminationTitle(string $reportName, string $company): string
    {
        return "Employee Termination - {$reportName} ({$company})";
    }

    private function sharedHrmEmployeeTerminationFilename(string $reportName, string $company): string
    {
        return $this->sharedHrmEmployeeTerminationTitle($reportName, $company).'.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    private function listKaryawanHabisKontrakFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'month' => $request->input('month'),
            'year' => $request->input('year'),
            'bulan' => $request->input('bulan'),
            'tahun' => $request->input('tahun'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'TglAwal' => $request->input('TglAwal'),
            'TglAkhir' => $request->input('TglAkhir'),
            'report_date' => $request->input('report_date'),
            'tanggal' => $request->input('tanggal'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function absensiBriefingHarianFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'group' => $request->input('Pilih Group') ?? $request->input('Pilih_Group') ?? $request->input('Pilih_x0020_Group') ?? $request->input('group'),
            'Pilih Group' => $request->input('Pilih Group'),
            'Pilih_Group' => $request->input('Pilih_Group'),
            'Pilih_x0020_Group' => $request->input('Pilih_x0020_Group'),
            'division' => $request->input('division'),
            'divisi' => $request->input('divisi'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'TglAwal' => $request->input('TglAwal'),
            'TglAkhir' => $request->input('TglAkhir'),
            'report_date' => $request->input('report_date'),
            'tanggal' => $request->input('tanggal'),
            'date' => $request->input('date'),
            'penanggung_jawab' => $request->input('penanggung_jawab'),
            'responsible_person' => $request->input('responsible_person'),
            'tema' => $request->input('tema'),
            'theme' => $request->input('theme'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function warningNoticeFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'report_date' => $request->input('report_date'),
            'tanggal' => $request->input('tanggal'),
            'date' => $request->input('date'),
            'Tanggal' => $request->input('Tanggal'),
            'print_date' => $request->input('print_date'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mppTahunanPerDivisiGsuFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'Pilih Divisi' => $this->requestInputByAliases($request, [
                'Pilih Divisi',
                'Pilih_x0020_Divisi',
                'Pilih Divisi_x0020_',
                'pilih_divisi',
                'pilihDivisi',
                'divisi',
                'Divisi',
                'division',
                'Division',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lossTimeFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'TglAwal' => $request->input('TglAwal'),
            'TglAkhir' => $request->input('TglAkhir'),
            'Pilih Tipe' => $this->requestInputByAliases($request, [
                'Pilih Tipe',
                'Pilih_x0020_Tipe',
                'pilih_tipe',
                'pilihTipe',
                'tipe',
                'Tipe',
                'Pilih Type',
                'Pilih_x0020_Type',
                'pilih_type',
                'pilihType',
                'type',
                'Type',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function absensiIndividuFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'employee_code' => $request->input('employee_code') ?? $request->input('kode_karyawan'),
            'employee_name' => $request->input('employee_name') ?? $request->input('nama_karyawan'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'TglAwal' => $request->input('TglAwal'),
            'TglAkhir' => $request->input('TglAkhir'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceFullPeriodFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'startDate',
                'date_start',
                'DateStart',
                'from_date',
                'FromDate',
                'TglAwal',
                'TanggalAwal',
                'AttendanceDate.StartDate',
                'AttendanceDate_StartDate',
                'AttendanceDate_x002e_StartDate',
                'AttendanceDate_x0020_StartDate',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'endDate',
                'date_end',
                'DateEnd',
                'to_date',
                'ToDate',
                'TglAkhir',
                'TanggalAkhir',
                'AttendanceDate.EndDate',
                'AttendanceDate_EndDate',
                'AttendanceDate_x002e_EndDate',
                'AttendanceDate_x0020_EndDate',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceFullMealParticipantFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return $this->attendanceFullPeriodFilters($request) + [
            'month' => $request->input('month'),
            'year' => $request->input('year'),
            'bulan' => $request->input('bulan'),
            'tahun' => $request->input('tahun'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceFullCategoryFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return $this->attendanceFullPeriodFilters($request) + [
            'category' => $request->input('category'),
            'Category' => $request->input('Category'),
            'kategori' => $request->input('kategori'),
            'Kategori' => $request->input('Kategori'),
            'pilih_kategori' => $request->input('pilih_kategori'),
            'PilihKategori' => $request->input('PilihKategori'),
            'Pilih Kategori' => $request->input('Pilih Kategori'),
            'Pilih_x0020_Kategori' => $request->input('Pilih_x0020_Kategori'),
            'status' => $request->input('status'),
            'Status' => $request->input('Status'),
            'tipe' => $request->input('tipe'),
            'Tipe' => $request->input('Tipe'),
            'type' => $request->input('type'),
            'Type' => $request->input('Type'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceFullStatusFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return $this->attendanceFullPeriodFilters($request) + [
            'Pilih Status' => $this->requestInputByAliases($request, [
                'Pilih Status',
                'Pilih_x0020_Status',
                'pilih_status',
                'pilihStatus',
                'status',
                'Status',
                'category',
                'Category',
                'kategori',
                'Kategori',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceFullTypeFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return $this->attendanceFullPeriodFilters($request) + [
            'Pilih Type' => $this->requestInputByAliases($request, ['Pilih Type', 'Pilih_x0020_Type', 'pilih_type', 'pilihType', 'type', 'Type']),
        ];
    }

    private function attendanceFullType(GenerateAscendsEmployeeListReportRequest $request): string
    {
        $type = trim((string) $this->requestInputByAliases($request, ['Pilih Type', 'Pilih_x0020_Type', 'pilih_type', 'pilihType', 'type', 'Type']));

        return str_contains(strtoupper($type), 'STAFF') ? 'Staff' : 'KK/KT';
    }

    private function attendanceFullStatus(GenerateAscendsEmployeeListReportRequest $request): string
    {
        $status = trim((string) $this->requestInputByAliases($request, [
            'Pilih Status',
            'Pilih_x0020_Status',
            'pilih_status',
            'pilihStatus',
            'status',
            'Status',
            'category',
            'Category',
            'kategori',
            'Kategori',
        ]));

        return str_contains(strtoupper($status), 'STAFF') ? 'Staff' : 'KK/KT';
    }

    /**
     * @param  array<int, string>  $aliases
     */
    /**
     * @return array<string, string|null>
     */
    private function thrFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'Pilih THR' => $this->requestInputByAliases($request, [
                'Pilih THR',
                'Pilih_x0020_THR',
                'pilih_thr',
                'PilihTHR',
                'thr',
                'THR',
            ]),
            'PerDate' => $this->requestInputByAliases($request, [
                'PerDate',
                'per_date',
                'perdate',
                'PeriodeDate',
                'tanggal',
                'date',
            ]),
        ];
    }

    private function penyesuaianPersediaanFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'AdjustmentDate.StartDate',
                'AdjustmentDate.StartDatee',
                'TglAwal',
                'tgl_awal',
                'date_start',
                'DateStart',
                'dari_tanggal',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'AdjustmentDate.EndDate',
                'TglAkhir',
                'tgl_akhir',
                'date_end',
                'DateEnd',
                'sampai_tanggal',
            ]),
        ];
    }

    private function hasilBrokerPerHariFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'ProductionDate.StartDate',
                'ProductionDate_StartDate',
                'ProductionDate_x0020_StartDate',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'ProductionDate.EndDate',
                'ProductionDate_EndDate',
                'ProductionDate_x0020_EndDate',
            ]),
        ];
    }

    private function listDOBelumTerkirimFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'per_date' => $this->requestInputByAliases($request, [
                'per_date',
                'PerDate',
                'Per Date',
                'perDate',
                'tgl_per',
                'TglPer',
            ]),
        ];
    }

    private function doCustomerBelumTerkirimFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'per_date' => $this->requestInputByAliases($request, [
                'per_date',
                'PerDate',
                'Per Date',
                'perDate',
                'tgl_per',
                'TglPer',
            ]),
        ];
    }

    private function doLemariBelumTerkirimFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'per_date' => $this->requestInputByAliases($request, [
                'per_date',
                'PerDate',
                'Per Date',
                'perDate',
                'tgl_per',
                'TglPer',
            ]),
        ];
    }

    private function doPerKategoriBelumTerkirimFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'per_date' => $this->requestInputByAliases($request, [
                'per_date',
                'PerDate',
                'Per Date',
                'perDate',
                'tgl_per',
                'TglPer',
            ]),
        ];
    }

    private function laporanHppDanStockFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'DateRange.StartDate',
                'DateRange_StartDate',
                'DateRange_x0020_StartDate',
                'DateRange.Start Date',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'DateRange.EndDate',
                'DateRange_EndDate',
                'DateRange_x0020_EndDate',
                'DateRange.End Date',
            ]),
        ];
    }

    private function khususPlastikKabinetFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'DateRange.StartDate',
                'DateRange_StartDate',
                'DateRange_x0020_StartDate',
                'DateRange.Start Date',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'DateRange.EndDate',
                'DateRange_EndDate',
                'DateRange_x0020_EndDate',
                'DateRange.End Date',
            ]),
        ];
    }

    private function aktifitasStockGsuFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'DateRange.StartDate',
                'DateRange_StartDate',
                'DateRange_x0020_StartDate',
                'DateRange.Start Date',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'DateRange.EndDate',
                'DateRange_EndDate',
                'DateRange_x0020_EndDate',
                'DateRange.End Date',
            ]),
        ];
    }

    private function aktifitasStockGsuPerGudangFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $this->requestInputByAliases($request, [
                'start_date',
                'StartDate',
                'DateRange.StartDate',
                'DateRange_StartDate',
                'DateRange_x0020_StartDate',
                'DateRange.Start Date',
            ]),
            'end_date' => $this->requestInputByAliases($request, [
                'end_date',
                'EndDate',
                'DateRange.EndDate',
                'DateRange_EndDate',
                'DateRange_x0020_EndDate',
                'DateRange.End Date',
            ]),
        ];
    }

    private function requestInputByAliases(GenerateAscendsEmployeeListReportRequest $request, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $value = trim((string) $request->input($alias, ''));
            if ($value !== '') {
                return $value;
            }
        }

        $normalizedAliases = array_map(static fn (string $alias): string => self::normalizeRequestKey($alias), $aliases);
        foreach ($request->all() as $key => $value) {
            if (in_array(self::normalizeRequestKey((string) $key), $normalizedAliases, true)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private static function normalizeRequestKey(string $key): string
    {
        return strtolower(str_replace([' ', '_x0020_', '_x002e_', '_', '-', '.'], '', $key));
    }

    private function attendanceFullCategory(GenerateAscendsEmployeeListReportRequest $request): string
    {
        foreach (['category', 'Category', 'kategori', 'Kategori', 'pilih_kategori', 'PilihKategori', 'Pilih Kategori', 'Pilih_x0020_Kategori', 'status', 'Status', 'tipe', 'Tipe', 'type', 'Type'] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'ST';
    }

    /**
     * @return array<string, mixed>
     */
    private function absenceFilters(GenerateAscendsEmployeeListReportRequest $request): array
    {
        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'TglAwal' => $request->input('TglAwal'),
            'TglAkhir' => $request->input('TglAkhir'),
            'tipe' => $request->input('tipe'),
            'Tipe' => $request->input('Tipe'),
            'kategori' => $request->input('kategori'),
            'Kategori' => $request->input('Kategori'),
            'pilih_kategori' => $request->input('pilih_kategori'),
            'PilihKategori' => $request->input('PilihKategori'),
            'Pilih Kategori' => $request->input('Pilih Kategori'),
            'Pilih_x0020_Kategori' => $request->input('Pilih_x0020_Kategori'),
            'type' => $request->input('type'),
            'Type' => $request->input('Type'),
        ];
    }

    private function absenceCategory(GenerateAscendsEmployeeListReportRequest $request): string
    {
        foreach (['tipe', 'Tipe', 'kategori', 'Kategori', 'pilih_kategori', 'PilihKategori', 'Pilih Kategori', 'Pilih_x0020_Kategori', 'type', 'Type'] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'KK/KT';
    }

    /**
     * @return array{view: string, filename: string, orientation: string}
     */
    private function testReportDefinition(string $reportType): array
    {
        return match ($reportType) {
            'karyawan_per_masa_kerja' => [
                'view' => 'ascends.ru.hrm.karyawan_per_masa_kerja.pdf',
                'filename' => 'Laporan Karyawan Per Masa Kerja (RU).pdf',
                'orientation' => 'portrait',
            ],
            'data_karyawan_status_kerja' => [
                'view' => 'ascends.ru.hrm.data_karyawan_status_kerja.pdf',
                'filename' => 'Laporan Data Karyawan (RU) - Status Kerja.pdf',
                'orientation' => 'portrait',
            ],
            'daftar_karyawan_berdasarkan_abjad' => [
                'view' => 'ascends.ru.hrm.daftar_karyawan_berdasarkan_abjad.pdf',
                'filename' => 'Laporan Daftar Karyawan (RU) - Berdasarkan Abjad.pdf',
                'orientation' => 'portrait',
            ],
            'daftar_karyawan' => [
                'view' => 'ascends.ru.hrm.daftar_karyawan.pdf',
                'filename' => 'Laporan Daftar Karyawan (RU).pdf',
                'orientation' => 'portrait',
            ],
            'karyawan_aktif_per_departemen' => [
                'view' => 'ascends.ru.hrm.karyawan_aktif_per_departemen.pdf',
                'filename' => 'Laporan Karyawan Aktif Per Departemen (RU).pdf',
                'orientation' => 'portrait',
            ],
            'karyawan_per_agama' => [
                'view' => 'ascends.ru.hrm.karyawan_per_agama.pdf',
                'filename' => 'Laporan Karyawan Per Agama (RU).pdf',
                'orientation' => 'portrait',
            ],
            'karyawan_per_etnis' => [
                'view' => 'ascends.ru.hrm.karyawan_per_etnis.pdf',
                'filename' => 'Laporan Karyawan Per Etnis (RU).pdf',
                'orientation' => 'portrait',
            ],
            'karyawan_per_level' => [
                'view' => 'ascends.ru.hrm.karyawan_per_level.pdf',
                'filename' => 'Laporan Karyawan Per Level (RU).pdf',
                'orientation' => 'portrait',
            ],
            'karyawan_per_umur' => [
                'view' => 'ascends.ru.hrm.karyawan_per_umur.pdf',
                'filename' => 'Laporan Karyawan Per Umur (RU).pdf',
                'orientation' => 'portrait',
            ],
            'karyawan_per_departemen_per_jabatan' => [
                'view' => 'ascends.ru.hrm.karyawan_per_departemen_per_jabatan.pdf',
                'filename' => 'Laporan Karyawan Per Departemen Per Jabatan (RU).pdf',
                'orientation' => 'portrait',
            ],
            'perbandingan_jumlah_karyawan_tahunan_per_bulan' => [
                'view' => 'ascends.shared.hrm.employee_list.perbandingan_jumlah_karyawan_tahunan_per_bulan.pdf',
                'filename' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan.pdf',
                'orientation' => 'portrait',
            ],
            'list_karyawan_habis_kontrak' => [
                'view' => 'ascends.shared.hrm.employee_list.list_karyawan_habis_kontrak.pdf',
                'filename' => 'Laporan List Karyawan Habis Kontrak.pdf',
                'orientation' => 'portrait',
            ],
            'absensi_briefing_harian_ru' => [
                'view' => 'ascends.shared.hrm.attendance_full.absensi_briefing_harian_ru.pdf',
                'filename' => 'Laporan Absensi Briefing Harian.pdf',
                'orientation' => 'portrait',
            ],
            'absensi_briefing_harian_gsu' => [
                'view' => 'ascends.shared.hrm.attendance_full.absensi_briefing_harian_gsu.pdf',
                'filename' => 'Laporan Absensi Briefing Harian (GSU).pdf',
                'orientation' => 'portrait',
            ],
            'rekapitulasi_absensi_briefing_harian_ru' => [
                'view' => 'ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_ru.pdf',
                'filename' => 'Laporan Rekapitulasi Absensi Briefing Harian.pdf',
                'orientation' => 'portrait',
            ],
            'rekapitulasi_absensi_briefing_harian_gsu' => [
                'view' => 'ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_gsu.pdf',
                'filename' => 'Laporan Rekapitulasi Absensi Briefing Harian.pdf',
                'orientation' => 'portrait',
            ],
            'data_peserta_makan_siang_ibadah_aula_per_departemen' => [
                'view' => 'ascends.shared.hrm.attendance_full.data_peserta_makan_siang_ibadah_aula_per_departemen.pdf',
                'filename' => 'Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen.pdf',
                'orientation' => 'portrait',
                'format' => 'A4',
            ],
            'data_peserta_makan_siang_shalat_jumat_per_departemen' => [
                'view' => 'ascends.shared.hrm.attendance_full.data_peserta_makan_siang_shalat_jumat_per_departemen.pdf',
                'filename' => 'Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen.pdf',
                'orientation' => 'portrait',
                'format' => 'A4',
            ],
            'absensi_individu' => [
                'view' => 'ascends.shared.hrm.attendance_full.absensi_individu.pdf',
                'filename' => 'Laporan Absensi Individu.pdf',
                'orientation' => 'portrait',
            ],
            'kehadiran_kru_stick' => [
                'view' => 'ascends.shared.hrm.attendance_full.kehadiran_kru_stick.pdf',
                'filename' => 'Laporan Kehadiran Kru Stick.pdf',
                'orientation' => 'landscape',
                'format' => 'A4',
            ],
            'kehadiran_kru_racip' => [
                'view' => 'ascends.shared.hrm.attendance_full.kehadiran_kru_racip.pdf',
                'filename' => 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut.pdf',
                'orientation' => 'landscape',
                'format' => 'A4',
            ],
            'kehadiran_kru_bahan_baku' => [
                'view' => 'ascends.shared.hrm.attendance_full.kehadiran_kru_bahan_baku.pdf',
                'filename' => 'Laporan Kehadiran Kru Bahan Baku.pdf',
                'orientation' => 'landscape',
                'format' => 'A4',
            ],
            'persentase_kehadiran_mingguan_per_departemen' => [
                'view' => 'ascends.shared.hrm.attendance_full.persentase_kehadiran_mingguan_per_departemen.pdf',
                'filename' => 'Laporan Persentase Kehadiran Mingguan Per Departemen.pdf',
                'orientation' => 'portrait',
            ],
            'persentase_kehadiran_bulanan' => [
                'view' => 'ascends.shared.hrm.attendance_full.persentase_kehadiran_bulanan.pdf',
                'filename' => 'Laporan Persentase Kehadiran Bulanan.pdf',
                'orientation' => 'portrait',
            ],
            'rekapitulasi_kehadiran_kurang_93_tahunan' => [
                'view' => 'ascends.shared.hrm.attendance_full.rekapitulasi_kehadiran_kurang_93_tahunan.pdf',
                'filename' => 'Laporan Rekapitulasi Kehadiran Kurang 93 Persen Tahunan.pdf',
                'orientation' => 'portrait',
            ],
            'rekapitulasi_pengabaian_keterlambatan_tahunan' => [
                'view' => 'ascends.shared.hrm.attendance_full.rekapitulasi_pengabaian_keterlambatan_tahunan.pdf',
                'filename' => 'Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan.pdf',
                'orientation' => 'portrait',
            ],
            'pengabaian_keterlambatan_kehadiran_manual' => [
                'view' => 'ascends.shared.hrm.attendance_full.pengabaian_keterlambatan_kehadiran_manual.pdf',
                'filename' => 'Laporan Pengabaian Keterlambatan & Kehadiran Manual Per Departemen.pdf',
                'orientation' => 'portrait',
            ],
            'ketidakhadiran_bulanan' => [
                'view' => 'ascends.shared.hrm.absence.ketidakhadiran_bulanan.pdf',
                'filename' => 'Laporan Ketidakhadiran Bulanan.pdf',
                'orientation' => 'landscape',
            ],
            'surat_peringatan' => [
                'view' => 'ascends.shared.hrm.warning_notice.surat_peringatan.pdf',
                'filename' => 'Laporan Surat Peringatan.pdf',
                'orientation' => 'portrait',
            ],
            'sales_invoice' => [
                'view' => 'ascends.ru.sales.sales_invoice.panjang-pdf',
                'filename' => 'Sales Invoice (RU).pdf',
                'orientation' => 'portrait',
            ],
            'sales_invoice_panjang' => [
                'view' => 'ascends.ru.sales.sales_invoice.panjang-pdf',
                'filename' => 'Sales Invoice (RU) - Panjang.pdf',
                'orientation' => 'portrait',
            ],
            'sales_invoice_normal' => [
                'view' => 'ascends.ru.sales.sales_invoice.normal-pdf',
                'filename' => 'Sales Invoice (RU) - Normal.pdf',
                'orientation' => 'portrait',
            ],
            'gsu_sales_invoice_panjang' => [
                'view' => 'ascends.gsu.sales.sales_invoice.panjang-pdf',
                'filename' => 'Sales Invoices (GSU) - Panjang.pdf',
                'orientation' => 'portrait',
            ],
            'gsu_sales_invoice_normal' => [
                'view' => 'ascends.gsu.sales.sales_invoice.normal-pdf',
                'filename' => 'Sales Invoices (GSU) - Normal.pdf',
                'orientation' => 'portrait',
            ],
            'surat_jalan' => [
                'view' => 'ascends.ru.sales.surat_jalan.panjang-pdf',
                'filename' => 'Surat Jalan (RU) - Panjang.pdf',
                'orientation' => 'portrait',
            ],
            'surat_jalan_panjang' => [
                'view' => 'ascends.ru.sales.surat_jalan.panjang-pdf',
                'filename' => 'Surat Jalan (RU) - Panjang.pdf',
                'orientation' => 'portrait',
            ],
            'surat_jalan_normal' => [
                'view' => 'ascends.ru.sales.surat_jalan.normal-pdf',
                'filename' => 'Surat Jalan (RU) - Normal.pdf',
                'orientation' => 'portrait',
            ],
            'gsu_surat_jalan_panjang' => [
                'view' => 'ascends.gsu.sales.surat_jalan.panjang-pdf',
                'filename' => 'Surat Jalan (GSU) - Panjang.pdf',
                'orientation' => 'portrait',
            ],
            'gsu_surat_jalan_normal' => [
                'view' => 'ascends.gsu.sales.surat_jalan.normal-pdf',
                'filename' => 'Surat Jalan (GSU) - Normal.pdf',
                'orientation' => 'portrait',
            ],
            'gsu_list_karyawan' => [
                'view' => 'ascends.gsu.hrm.list_karyawan.pdf',
                'filename' => 'List-Karyawan-GSU.pdf',
                'orientation' => 'portrait',
            ],
            'uc_list_karyawan' => [
                'view' => 'ascends.uc.hrm.list_karyawan.pdf',
                'filename' => 'List-Karyawan-UC.pdf',
                'orientation' => 'portrait',
            ],
            'uc_karyawan_aktif_per_departemen' => [
                'view' => 'ascends.uc.hrm.karyawan_aktif_per_departemen.pdf',
                'filename' => 'Laporan Karyawan Aktif Per Departemen (UC).pdf',
                'orientation' => 'portrait',
            ],
            'uc_daftar_karyawan' => [
                'view' => 'ascends.uc.hrm.daftar_karyawan.pdf',
                'filename' => 'Laporan Daftar Karyawan (UC).pdf',
                'orientation' => 'portrait',
            ],
            'uc_daftar_karyawan_berdasarkan_abjad' => [
                'view' => 'ascends.uc.hrm.daftar_karyawan_berdasarkan_abjad.pdf',
                'filename' => 'Laporan Daftar Karyawan (UC) - Berdasarkan Abjad.pdf',
                'orientation' => 'portrait',
            ],
            'uc_data_karyawan_status_kerja' => [
                'view' => 'ascends.uc.hrm.data_karyawan_status_kerja.pdf',
                'filename' => 'Laporan Data Karyawan (UC) - Status Kerja.pdf',
                'orientation' => 'portrait',
            ],
            'uc_karyawan_masuk_per_departemen_per_tanggal_masuk' => [
                'view' => 'ascends.uc.hrm.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf',
                'filename' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC).pdf',
                'orientation' => 'portrait',
            ],
            default => [
                'view' => 'ascends.ru.hrm.list_karyawan.pdf',
                'filename' => 'List-Karyawan-RU.pdf',
                'orientation' => 'portrait',
            ],
        };
    }
}
