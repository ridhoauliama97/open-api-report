<?php

namespace App\Http\Controllers\Ascends\Ru\Hrm;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAscendsEmployeeListReportRequest;
use App\Services\Ascends\Shared\Hrm\EmployeeListReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class EmployeeListController extends Controller
{
    public function index(EmployeeListReportService $reportService): View
    {
        $reportData = $reportService->buildReportData();

        return view('ascends.ru.hrm.employee-list.list_karyawan.index', [
            'reportData' => $reportData,
        ]);
    }

    public function download(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $reportData = $this->buildReportData($request, $reportService);
        } catch (RuntimeException $exception) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withErrors(['report' => $exception->getMessage()]);
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

        $dispositionType = $request->boolean('preview_pdf') ? 'attachment' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, 'List-Karyawan-RU.pdf'),
        ]);
    }

    public function preview(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $this->buildReportData($request, $reportService);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan List Karyawan RU berhasil diambil.',
            'meta' => [
                'title' => $reportData['title'] ?? 'List Karyawan RU',
                'total_rows' => (int) ($reportData['total_rows'] ?? 0),
                'printed_at' => $reportData['printed_at'] ?? null,
            ],
            'summary' => $reportData['summary'] ?? [],
            'headers' => $reportData['headers'] ?? [],
            'rows' => array_slice($reportData['rows'] ?? [], 0, 25),
        ]);
    }

    public function health(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $this->buildReportData($request, $reportService);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $headers = $reportData['headers'] ?? [];
        $rows = $reportData['rows'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $isHealthy = is_array($headers) && $headers !== [] && is_array($rows);

        return response()->json([
            'message' => $isHealthy
                ? 'Struktur output List Karyawan RU valid.'
                : 'Struktur output List Karyawan RU tidak lengkap.',
            'meta' => [
                'title' => $reportData['title'] ?? 'List Karyawan RU',
                'source_file' => $reportData['source_file'] ?? null,
            ],
            'health' => [
                'is_healthy' => $isHealthy,
                'header_count' => is_array($headers) ? count($headers) : 0,
                'row_count' => is_array($rows) ? count($rows) : 0,
                'department_count' => (int) ($summary['department_count'] ?? 0),
                'has_gender_summary' => ! empty($summary['gender_summary']),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(
        GenerateAscendsEmployeeListReportRequest $request,
        EmployeeListReportService $reportService,
    ): array {
        $xmlPayload = $request->xmlPayload();
        if ($xmlPayload !== null) {
            return $reportService->buildReportDataFromXml(
                $xmlPayload,
                $request->xmlSourceLabel() ?? 'request xml payload'
            );
        }

        if ($request->is('api/*')) {
            throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
        }

        return $reportService->buildReportData();
    }
}
