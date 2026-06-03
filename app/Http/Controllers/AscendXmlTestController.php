<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAscendsEmployeeListReportRequest;
use App\Services\Ascends\Ru\Hrm\DaftarKaryawanBerdasarkanAbjadReportService;
use App\Services\Ascends\Ru\Hrm\DaftarKaryawanReportService;
use App\Services\Ascends\Ru\Hrm\DataKaryawanStatusKerjaReportService;
use App\Services\Ascends\Ru\Hrm\EmployeeListReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanAktifPerDepartemenReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanMasukPerDepartemenPerTanggalMasukReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerAgamaReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerDepartemenPerJabatanReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerEtnisReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerLevelReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerMasaKerjaReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerUmurReportService;
use App\Services\Ascends\Ru\Hrm\PerbandinganJumlahKaryawanTahunanPerBulanReportService;
use App\Services\Ascends\Ru\Hrm\UsiaGenerasiTahunKelahiranMasaKerjaReportService;
use App\Services\Ascends\Ru\Sales\SalesInvoiceReportService;
use App\Services\Ascends\Ru\Sales\SuratJalanReportService;
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
        PerbandinganJumlahKaryawanTahunanPerBulanReportService $perbandinganJumlahKaryawanTahunanPerBulanReportService,
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
                'perbandingan_jumlah_karyawan_tahunan_per_bulan' => $perbandinganJumlahKaryawanTahunanPerBulanReportService,
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

            $reportData = $selectedReportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request upload: xml_file'
            );
            if ($selectedReport === 'perbandingan_jumlah_karyawan_tahunan_per_bulan') {
                $company = strtoupper((string) $request->input('company', 'RU'));
                $title = $this->sharedHrmDisplayTitle('Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan', $company);

                $reportData['company'] = $company;
                $reportData['title'] = $title;
                $reportDefinition['filename'] = $this->sharedHrmEmployeeListFilename('Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan', $company);
            }

        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['xml_file' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render($reportDefinition['view'], [
            'reportData' => $reportData,
            'headers' => $reportData['headers'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'generatedAt' => now(),
            'pdf_format' => 'A4',
            'pdf_orientation' => $reportDefinition['orientation'],
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $reportDefinition['filename'] . '"',
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
            'pdf_format' => 'A4',
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
            'pdf_orientation' => 'portrait',
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
        $company = strtoupper((string) $request->input('company', 'UC'));
        $title = $this->sharedHrmDisplayTitle('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $company);

        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
            $reportData['company'] = $company;
            $reportData['title'] = $title;
            $reportData['label'] = $title;
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
            'Content-Disposition' => 'inline; filename="' . $this->sharedHrmEmployeeListFilename('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $company) . '"',
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
        PerbandinganJumlahKaryawanTahunanPerBulanReportService $perbandinganJumlahKaryawanTahunanPerBulanReportService,
        UsiaGenerasiTahunKelahiranMasaKerjaReportService $usiaGenerasiTahunKelahiranMasaKerjaReportService,
        PdfGenerator $pdfGenerator,
    ) {
        $company = strtoupper((string) $request->input('company', ''));
        if (! in_array($company, ['RU', 'GSU', 'UC'], true)) {
            return response()->json(['message' => 'Field company wajib dikirim dengan nilai RU, GSU, atau UC.'], 422);
        }

        $reportDefinition = $this->sharedHrmReportDefinition($report, $company);
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
            'perbandingan-jumlah-karyawan-tahunan-per-bulan' => $perbandinganJumlahKaryawanTahunanPerBulanReportService,
            'usia-generasi-tahun-kelahiran-masa-kerja' => $usiaGenerasiTahunKelahiranMasaKerjaReportService,
        };

        try {
            $xmlPayload = $request->xmlPayload();
            if ($xmlPayload === null) {
                throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
            }

            $reportData = $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
            $reportData['company'] = $company;
            $reportData['title'] = $reportDefinition['title'];
            $reportData['label'] = $reportDefinition['title'];
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
            'Content-Disposition' => 'inline; filename="' . $reportDefinition['filename'] . '"',
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
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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

    private function sharedHrmEmployeeListTitle(string $reportName, string $company): string
    {
        return "Employee List - {$reportName} ({$company})";
    }

    private function sharedHrmEmployeeListFilename(string $reportName, string $company): string
    {
        return $this->sharedHrmEmployeeListTitle($reportName, $company) . '.pdf';
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
