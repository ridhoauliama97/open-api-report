<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateMutasiRejectReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\MutasiRejectReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiRejectController extends Controller
{
    public function index(): View
    {
        return view('pps.reject.mutasi_reject.form');
    }

    public function download(
        GenerateMutasiRejectReportRequest $request,
        MutasiRejectReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        [$startDate, $endDate] = $request->reportDates();
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

        $pdf = $pdfGenerator->render('pps.reject.mutasi_reject.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Mutasi-Reject-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateMutasiRejectReportRequest $request,
        MutasiRejectReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();

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
                'StartDate' => $startDate,
                'EndDate' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateMutasiRejectReportRequest $request,
        MutasiRejectReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PPSLapMutasiReject valid.'
                : 'Struktur output SP_PPSLapMutasiReject berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'StartDate' => $startDate,
                'EndDate' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
