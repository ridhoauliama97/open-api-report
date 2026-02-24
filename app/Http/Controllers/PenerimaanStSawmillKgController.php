<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePenerimaanStSawmillKgReportRequest;
use App\Services\PdfGenerator;
use App\Services\PenerimaanStSawmillKgReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenerimaanStSawmillKgController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.penerimaan-st-dari-sawmill-kg-form');
    }

    public function download(
        GeneratePenerimaanStSawmillKgReportRequest $request,
        PenerimaanStSawmillKgReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GeneratePenerimaanStSawmillKgReportRequest $request,
        PenerimaanStSawmillKgReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GeneratePenerimaanStSawmillKgReportRequest $request,
        PenerimaanStSawmillKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = $reportData['rows'];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'total_suppliers' => $reportData['summary']['total_suppliers'] ?? 0,
                'total_groups' => $reportData['summary']['total_groups'] ?? 0,
                'column_order' => array_keys($rows[0] ?? []),
                'no_penerimaan_column' => $reportData['no_penerimaan_column'] ?? null,
                'supplier_column' => $reportData['supplier_column'] ?? null,
            ],
            'summary' => $reportData['summary'],
            'data' => $rows,
            'grouped_data' => $reportData['grouped_rows'],
        ]);
    }

    public function health(
        GeneratePenerimaanStSawmillKgReportRequest $request,
        PenerimaanStSawmillKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRekapPenerimaanSawmilRp valid.'
                : 'Struktur output SPWps_LapRekapPenerimaanSawmilRp berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GeneratePenerimaanStSawmillKgReportRequest $request,
        PenerimaanStSawmillKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.penerimaan-st-dari-sawmill-kg-pdf', [
            'rows' => $reportData['rows'],
            'groupedRows' => $reportData['grouped_rows'],
            'summary' => $reportData['summary'],
            'noPenerimaanColumn' => $reportData['no_penerimaan_column'] ?? null,
            'supplierColumn' => $reportData['supplier_column'] ?? null,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Penerimaan-ST-Dari-Sawmill-Timbang-KG-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GeneratePenerimaanStSawmillKgReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
