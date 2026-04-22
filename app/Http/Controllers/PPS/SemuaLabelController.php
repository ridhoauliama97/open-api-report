<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateSemuaLabelReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\SemuaLabelReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SemuaLabelController extends Controller
{
    public function index(): View
    {
        return view('pps.semua_label.form');
    }

    public function download(
        GenerateSemuaLabelReportRequest $request,
        SemuaLabelReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        @ini_set('memory_limit', (string) env('SEMUA_LABEL_PDF_MEMORY_LIMIT', '2048M'));
        @set_time_limit(0);

        $reportDate = $request->reportDate();
        $startDate = $reportDate;
        $endDate = $reportDate;
        $generatedBy = $this->resolveReportGeneratedBy($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $payload = [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ];

        $dir = storage_path('app/pdf-temp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $tmpPath = $dir.DIRECTORY_SEPARATOR.uniqid('semua-label-', true).'.pdf';

        $pdfGenerator->renderToFile('pps.semua_label.pdf', $payload, $tmpPath);

        $filename = sprintf('Laporan-Semua-Label-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response()
            ->file($tmpPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
            ])
            ->deleteFileAfterSend(true);
    }

    public function preview(
        GenerateSemuaLabelReportRequest $request,
        SemuaLabelReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();
        $startDate = $reportDate;
        $endDate = $reportDate;

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateSemuaLabelReportRequest $request,
        SemuaLabelReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();
        $startDate = $reportDate;
        $endDate = $reportDate;

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LaporanSemuaLabel valid.'
                : 'Struktur output SP_LaporanSemuaLabel berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
