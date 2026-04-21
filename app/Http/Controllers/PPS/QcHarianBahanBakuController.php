<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateQcHarianBahanBakuReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\QcHarianBahanBakuReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class QcHarianBahanBakuController extends Controller
{
    public function index(): View
    {
        return view('pps.qc.qc_harian_bahan_baku.form');
    }

    public function download(
        GenerateQcHarianBahanBakuReportRequest $request,
        QcHarianBahanBakuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $reportDate = $request->reportDate();
        $generatedBy = $this->resolveGeneratedBy($request);

        try {
            $rows = $reportService->fetchByDate($reportDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('pps.qc.qc_harian_bahan_baku.pdf', [
            'rows' => $rows,
            'reportDate' => $reportDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-QC-Harian-Bahan-Baku-%s.pdf', $reportDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateQcHarianBahanBakuReportRequest $request,
        QcHarianBahanBakuReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $rows = $reportService->fetchByDate($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'report_date' => $reportDate,
                'Tanggal' => $reportDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateQcHarianBahanBakuReportRequest $request,
        QcHarianBahanBakuReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LabelHasilQCBB valid.'
                : 'Struktur output SP_LabelHasilQCBB berubah.',
            'meta' => [
                'report_date' => $reportDate,
                'Tanggal' => $reportDate,
            ],
            'health' => $result,
        ]);
    }

    private function resolveGeneratedBy(GenerateQcHarianBahanBakuReportRequest $request): object
    {
        $webUser = $request->user() ?? auth('api')->user();
        if ($webUser !== null) {
            $name = (string) ($webUser->name ?? $webUser->Username ?? 'sistem');

            return (object) ['name' => $name];
        }

        $claims = $request->attributes->get('report_token_claims');
        if (is_array($claims)) {
            $name = (string) ($claims['name'] ?? $claims['username'] ?? 'api');

            return (object) ['name' => $name];
        }

        return (object) ['name' => 'sistem'];
    }
}
