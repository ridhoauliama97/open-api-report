<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\LabelStHidupDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LabelStHidupDetailController extends Controller
{
    private const PREVIEW_ROW_LIMIT = 500;

    public function index(): View
    {
        return view('reports.sawn-timber.label-st-hidup-detail-form');
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
    ) {
        if (!$inline) {
            // This report can be extremely large (10k+ rows). mPDF keeps page buffers in memory
            // until final output, so raise memory limit for download requests.
            @ini_set('memory_limit', (string) env('LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT', '2048M'));
            @set_time_limit(0);
        }

        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        if ($inline) {
            $reportData = $this->limitPreviewRows($reportData);
        }

        $payload = [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            // Use core font to keep memory low for very large tables.
            'pdf_default_font' => 'helvetica',
            'pdf_orientation' => 'portrait',
            // Large tables: these options significantly reduce memory footprint in mPDF.
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => true,
            'pdf_column_count' => 11,
        ];

        $filename = 'Laporan-Label-ST-Hidup-Detail.pdf';

        $dir = storage_path('app/pdf-temp');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $tmpPath = $dir . DIRECTORY_SEPARATOR . uniqid('label-st-hidup-detail-', true) . '.pdf';

        $pdfGenerator->renderToFile('reports.sawn-timber.label-st-hidup-detail-pdf', $payload, $tmpPath);

        $disposition = $inline ? 'inline' : 'attachment';

        return response()
            ->file($tmpPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
            ])
            ->deleteFileAfterSend(true);
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $reportData = $this->limitPreviewRows($reportData);

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => (int) (($reportData['summary']['total_rows'] ?? 0)),
                'preview_row_limit' => self::PREVIEW_ROW_LIMIT,
                'preview_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys(($reportData['rows'][0] ?? []) ?: []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData['rows'] ?? [],
        ]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapLabelSTHidupDetail valid.'
                : 'Struktur output SP_LapLabelSTHidupDetail berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<string, mixed>
     */
    private function limitPreviewRows(array $reportData): array
    {
        $rows = $reportData['rows'] ?? null;
        if (!is_array($rows) || $rows === []) {
            return $reportData;
        }

        $reportData['rows'] = array_slice($rows, 0, self::PREVIEW_ROW_LIMIT);

        return $reportData;
    }
}
