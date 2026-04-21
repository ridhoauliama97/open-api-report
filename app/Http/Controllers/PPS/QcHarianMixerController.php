<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateQcHarianMixerReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\QcHarianMixerReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class QcHarianMixerController extends Controller
{
    public function index(): View
    {
        return view('pps.qc.qc_harian_mixer.form');
    }

    public function download(
        GenerateQcHarianMixerReportRequest $request,
        QcHarianMixerReportService $reportService,
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

        $pdf = $pdfGenerator->render('pps.qc.qc_harian_mixer.pdf', [
            'rows' => $rows,
            'reportDate' => $reportDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-QC-Harian-Mixer-%s.pdf', $reportDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateQcHarianMixerReportRequest $request,
        QcHarianMixerReportService $reportService,
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
                'EndDate' => $reportDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateQcHarianMixerReportRequest $request,
        QcHarianMixerReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LaporanHasilQCHarianMixer valid.'
                : 'Struktur output SP_LaporanHasilQCHarianMixer berubah.',
            'meta' => [
                'report_date' => $reportDate,
                'EndDate' => $reportDate,
            ],
            'health' => $result,
        ]);
    }

    private function resolveGeneratedBy(GenerateQcHarianMixerReportRequest $request): object
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
