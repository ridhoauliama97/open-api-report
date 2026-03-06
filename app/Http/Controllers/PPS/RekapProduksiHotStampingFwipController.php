<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateRekapProduksiHotStampingFwipReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\RekapProduksiHotStampingFwipReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiHotStampingFwipController extends Controller
{
    public function index(): View
    {
        return view('pps.rekap_produksi.hot_stamping_fwip.form');
    }

    public function download(
        GenerateRekapProduksiHotStampingFwipReportRequest $request,
        RekapProduksiHotStampingFwipReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
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

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $columnCount = count(array_keys($rows[0] ?? [])) + 1; // +1 untuk kolom "No"
        if ($columnCount <= 0) {
            $columnCount = 5; // fallback struktur laporan default
        }

        $pdf = $pdfGenerator->render('pps.rekap_produksi.hot_stamping_fwip.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_column_count' => $columnCount,
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Rekap-Produksi-Hot-Stamping-FWIP-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiHotStampingFwipReportRequest $request,
        RekapProduksiHotStampingFwipReportService $reportService,
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
        GenerateRekapProduksiHotStampingFwipReportRequest $request,
        RekapProduksiHotStampingFwipReportService $reportService,
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
                ? 'Struktur output SP_LapRekapProduksiHotStamping_FWIP valid.'
                : 'Struktur output SP_LapRekapProduksiHotStamping_FWIP berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @return object{name: string}
     */
    private function resolveGeneratedBy(GenerateRekapProduksiHotStampingFwipReportRequest $request): object
    {
        $webUser = $request->user() ?? auth('api')->user();
        if ($webUser !== null) {
            /** @var object{name?: string, Username?: string} $webUser */
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
