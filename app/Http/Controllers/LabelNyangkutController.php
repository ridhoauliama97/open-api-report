<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateLabelNyangkutReportRequest;
use App\Services\PdfGenerator;
use App\Services\LabelNyangkutReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LabelNyangkutController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('reports.label-nyangkut-form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateLabelNyangkutReportRequest $request,
        LabelNyangkutReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.label-nyangkut-pdf', [
            'rows' => $rows,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $tglCetak = now()->format('Y-m-d');
        $filename = sprintf('Laporan-Label-Nyangkut-per-%s.pdf', $tglCetak);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateLabelNyangkutReportRequest $request,
        LabelNyangkutReportService $reportService,
    ): JsonResponse {
        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    /**
     * Execute health logic.
     */
    public function health(
        GenerateLabelNyangkutReportRequest $request,
        LabelNyangkutReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapLabelNyangkut valid.'
                : 'Struktur output SPWps_LapLabelNyangkut berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
