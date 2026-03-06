<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateRekapProduksiMixerReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\RekapProduksiMixerReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiMixerController extends Controller
{
    public function index(): View
    {
        return view('pps.rekap_produksi.mixer.form');
    }

    public function download(GenerateRekapProduksiMixerReportRequest $request, RekapProduksiMixerReportService $reportService, PdfGenerator $pdfGenerator)
    {
        $reportDate = $request->reportDate();
        $startDate = $reportDate;
        $endDate = $reportDate;
        $generatedBy = $this->resolveGeneratedBy($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $columnCount = count(array_keys($rows[0] ?? [])) + 1;
        if ($columnCount <= 0) {
            $columnCount = 5;
        }

        $pdf = $pdfGenerator->render('pps.rekap_produksi.mixer.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_column_count' => $columnCount,
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Rekap-Produksi-Mixer-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(GenerateRekapProduksiMixerReportRequest $request, RekapProduksiMixerReportService $reportService): JsonResponse
    {
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

    public function health(GenerateRekapProduksiMixerReportRequest $request, RekapProduksiMixerReportService $reportService): JsonResponse
    {
        $reportDate = $request->reportDate();
        $startDate = $reportDate;
        $endDate = $reportDate;

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy'] ? 'Struktur output SP_LapRekapProduksiMixer valid.' : 'Struktur output SP_LapRekapProduksiMixer berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function resolveGeneratedBy(GenerateRekapProduksiMixerReportRequest $request): object
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
