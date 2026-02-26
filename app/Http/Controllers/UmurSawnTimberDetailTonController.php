<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateUmurSawnTimberDetailTonReportRequest;
use App\Services\PdfGenerator;
use App\Services\UmurSawnTimberDetailTonReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UmurSawnTimberDetailTonController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.umur-sawn-timber-detail-ton-form');
    }

    public function download(
        GenerateUmurSawnTimberDetailTonReportRequest $request,
        UmurSawnTimberDetailTonReportService $reportService,
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

        $parameters = $request->umurParameters();

        try {
            $rows = $reportService->fetch($parameters);
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.umur-sawn-timber-detail-ton-pdf', [
            'rows' => $rows,
            'parameters' => $parameters,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
        ]);

        $filename = sprintf(
            'Laporan Umur Sawn Timber Detail (Ton) - U%s-U%s-U%s-U%s.pdf',
            $parameters['Umur1'],
            $parameters['Umur2'],
            $parameters['Umur3'],
            $parameters['Umur4'],
        );

        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateUmurSawnTimberDetailTonReportRequest $request,
        UmurSawnTimberDetailTonReportService $reportService,
    ): JsonResponse {
        $parameters = $request->umurParameters();

        try {
            $rows = $reportService->fetch($parameters);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'parameters' => $parameters,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateUmurSawnTimberDetailTonReportRequest $request,
        UmurSawnTimberDetailTonReportService $reportService,
    ): JsonResponse {
        $parameters = $request->umurParameters();

        try {
            $result = $reportService->healthCheck($parameters);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapUmurST valid.'
                : 'Struktur output SPWps_LapUmurST berubah.',
            'meta' => [
                'parameters' => $parameters,
            ],
            'health' => $result,
        ]);
    }
}
