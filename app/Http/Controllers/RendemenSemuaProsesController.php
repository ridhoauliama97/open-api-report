<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRendemenSemuaProsesReportRequest;
use App\Services\PdfGenerator;
use App\Services\RendemenSemuaProsesReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RendemenSemuaProsesController extends Controller
{
    public function index(): View
    {
        return view('reports.rendemen-kayu.rendemen-semua-proses-form');
    }

    public function download(
        GenerateRendemenSemuaProsesReportRequest $request,
        RendemenSemuaProsesReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateRendemenSemuaProsesReportRequest $request,
        RendemenSemuaProsesReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateRendemenSemuaProsesReportRequest $request,
        RendemenSemuaProsesReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $rows = [];
        foreach ($groups as $group) {
            foreach (($group['rows'] ?? []) as $row) {
                $rows[] = $row;
            }
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_rows' => count($rows),
                'total_groups' => count($groups),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateRendemenSemuaProsesReportRequest $request,
        RendemenSemuaProsesReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapRekapRendemenSemuaProses valid.'
                : 'Struktur output SP_LapRekapRendemenSemuaProses berubah.',
            'meta' => ['start_date' => $startDate, 'end_date' => $endDate],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateRendemenSemuaProsesReportRequest $request,
        RendemenSemuaProsesReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()->withInput()->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.rendemen-kayu.rendemen-semua-proses-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Rendemen-Semua-Proses-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
