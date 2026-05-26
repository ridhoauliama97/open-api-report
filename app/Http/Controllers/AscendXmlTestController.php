<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAscendsEmployeeListReportRequest;
use App\Services\Ascends\Ru\Hrm\DaftarKaryawanBerdasarkanAbjadReportService;
use App\Services\Ascends\Ru\Hrm\DaftarKaryawanReportService;
use App\Services\Ascends\Ru\Hrm\DataKaryawanStatusKerjaReportService;
use App\Services\Ascends\Ru\Hrm\EmployeeListReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanAktifPerDepartemenReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerAgamaReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerDepartemenPerJabatanReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerEtnisReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerLevelReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerMasaKerjaReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanPerUmurReportService;
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
        KaryawanPerAgamaReportService $karyawanPerAgamaReportService,
        KaryawanPerEtnisReportService $karyawanPerEtnisReportService,
        KaryawanPerLevelReportService $karyawanPerLevelReportService,
        KaryawanPerUmurReportService $karyawanPerUmurReportService,
        KaryawanPerDepartemenPerJabatanReportService $karyawanPerDepartemenPerJabatanReportService,
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
                default => $reportService,
            };

            $reportData = $selectedReportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request upload: xml_file'
            );
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
            'pdf_format' => 'A4',
            'pdf_simple_tables' => false,
            'pdf_column_count' => count($reportData['headers'] ?? []),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="List-Karyawan-RU.pdf"',
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
            default => [
                'view' => 'ascends.ru.hrm.list_karyawan.pdf',
                'filename' => 'List-Karyawan-RU.pdf',
                'orientation' => 'portrait',
            ],
        };
    }
}
