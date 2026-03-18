<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduksiLaminatingPerJenisPerGradeReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiLaminatingPerJenisPerGradeReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiLaminatingPerJenisPerGradeController extends Controller
{
    public function index(): View
    {
        return view('reports.laminating.rekap-produksi-laminating-per-jenis-per-grade-form');
    }

    public function download(
        GenerateRekapProduksiLaminatingPerJenisPerGradeReportRequest $request,
        RekapProduksiLaminatingPerJenisPerGradeReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.laminating.rekap-produksi-laminating-per-jenis-per-grade-pdf', [
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
            'Laporan-Rekap-Produksi-Laminating-Per-Jenis-Per-Grade-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiLaminatingPerJenisPerGradeReportRequest $request,
        RekapProduksiLaminatingPerJenisPerGradeReportService $reportService,
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
        GenerateRekapProduksiLaminatingPerJenisPerGradeReportRequest $request,
        RekapProduksiLaminatingPerJenisPerGradeReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.laminating.rekap-produksi-laminating-per-jenis-per-grade-pdf', [
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
            'Laporan-Rekap-Produksi-Laminating-Per-Jenis-Per-Grade-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateRekapProduksiLaminatingPerJenisPerGradeReportRequest $request,
        RekapProduksiLaminatingPerJenisPerGradeReportService $reportService,
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
                ? 'Struktur output SP_LapRekapProduksiLaminatingPerJenisPerGrade valid.'
                : 'Struktur output SP_LapRekapProduksiLaminatingPerJenisPerGrade berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{jenis:string, rows:array<int, array<string, mixed>>, totals:array{InMoulding:float, InSanding:float, InWIP:float, InReproses:float, Output:float}}>
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

        $result = [];
        foreach ($groups as $jenis => $jenisRows) {
            $totInMoulding = 0.0;
            $totInSanding = 0.0;
            $totInWIP = 0.0;
            $totInReproses = 0.0;
            $totOut = 0.0;

            foreach ($jenisRows as $r) {
                $totInMoulding += (float) ($r['InMoulding'] ?? 0.0);
                $totInSanding += (float) ($r['InSanding'] ?? 0.0);
                $totInWIP += (float) ($r['InWIP'] ?? 0.0);
                $totInReproses += (float) ($r['InReproses'] ?? 0.0);
                $totOut += (float) ($r['Output'] ?? 0.0);
            }

            $result[] = [
                'jenis' => $jenis,
                'rows' => $jenisRows,
                'totals' => [
                    'InMoulding' => $totInMoulding,
                    'InSanding' => $totInSanding,
                    'InWIP' => $totInWIP,
                    'InReproses' => $totInReproses,
                    'Output' => $totOut,
                ],
            ];
        }

        return $result;
    }
}
