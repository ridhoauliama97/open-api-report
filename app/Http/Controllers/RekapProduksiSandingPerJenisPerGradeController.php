<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduksiSandingPerJenisPerGradeReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiSandingPerJenisPerGradeReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiSandingPerJenisPerGradeController extends Controller
{
    public function index(): View
    {
        return view('reports.sanding.rekap-produksi-sanding-per-jenis-per-grade-form');
    }

    public function download(
        GenerateRekapProduksiSandingPerJenisPerGradeReportRequest $request,
        RekapProduksiSandingPerJenisPerGradeReportService $reportService,
        PdfGenerator $pdfGenerator,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();

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

        $grouped = $this->groupByJenis($rows);

        $pdf = $pdfGenerator->render('reports.sanding.rekap-produksi-sanding-per-jenis-per-grade-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'groups' => $grouped,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Produksi-Sanding-Per-Jenis-Per-Grade-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiSandingPerJenisPerGradeReportRequest $request,
        RekapProduksiSandingPerJenisPerGradeReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

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
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateRekapProduksiSandingPerJenisPerGradeReportRequest $request,
        RekapProduksiSandingPerJenisPerGradeReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $grouped = $this->groupByJenis($rows);

        $pdf = $pdfGenerator->render('reports.sanding.rekap-produksi-sanding-per-jenis-per-grade-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'groups' => $grouped,
            ],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Produksi-Sanding-Per-Jenis-Per-Grade-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateRekapProduksiSandingPerJenisPerGradeReportRequest $request,
        RekapProduksiSandingPerJenisPerGradeReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapRekapProduksiSandingPerJenisPerGrade valid.'
                : 'Struktur output SP_LapRekapProduksiSandingPerJenisPerGrade berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{jenis:string, rows:array<int, array<string, mixed>>, totals:array<string, float>}>
     */
    private function groupByJenis(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $jenis = (string) ($row['Jenis'] ?? '');
            if ($jenis === '') {
                $jenis = 'JENIS';
            }
            $groups[$jenis][] = $row;
        }

        ksort($groups, SORT_STRING);

        $columns = ['InFJ', 'InMoulding', 'InCCAkhir', 'InWIP', 'InReproses', 'Output'];

        $result = [];
        foreach ($groups as $jenis => $jenisRows) {
            $totals = array_fill_keys($columns, 0.0);

            foreach ($jenisRows as $r) {
                foreach ($columns as $column) {
                    $totals[$column] += (float) ($r[$column] ?? 0.0);
                }
            }

            $result[] = [
                'jenis' => $jenis,
                'rows' => $jenisRows,
                'totals' => $totals,
            ];
        }

        return $result;
    }
}
