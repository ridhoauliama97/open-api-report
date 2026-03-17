<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduksiFingerJointPerJenisPerGradeReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiFingerJointPerJenisPerGradeReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiFingerJointPerJenisPerGradeController extends Controller
{
    public function index(): View
    {
        return view('reports.finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade-form');
    }

    public function download(
        GenerateRekapProduksiFingerJointPerJenisPerGradeReportRequest $request,
        RekapProduksiFingerJointPerJenisPerGradeReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade-pdf', [
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
            'Laporan-Rekap-Produksi-Finger-Joint-Per-Jenis-Per-Grade-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename=\"%s\"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiFingerJointPerJenisPerGradeReportRequest $request,
        RekapProduksiFingerJointPerJenisPerGradeReportService $reportService,
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
        GenerateRekapProduksiFingerJointPerJenisPerGradeReportRequest $request,
        RekapProduksiFingerJointPerJenisPerGradeReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade-pdf', [
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
            'Laporan-Rekap-Produksi-Finger-Joint-Per-Jenis-Per-Grade-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"%s\"', $filename),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{jenis:string, rows:array<int, array<string, mixed>>, totals:array{InS4S:float, InCCAkhir:float, InWIP:float, Output:float}}>
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
            $totInS4S = 0.0;
            $totInCCAkhir = 0.0;
            $totInWIP = 0.0;
            $totOut = 0.0;

            foreach ($jenisRows as $r) {
                $totInS4S += (float) ($r['InS4S'] ?? 0.0);
                $totInCCAkhir += (float) ($r['InCCAkhir'] ?? 0.0);
                $totInWIP += (float) ($r['InWIP'] ?? 0.0);
                $totOut += (float) ($r['Output'] ?? 0.0);
            }

            $result[] = [
                'jenis' => $jenis,
                'rows' => $jenisRows,
                'totals' => [
                    'InS4S' => $totInS4S,
                    'InCCAkhir' => $totInCCAkhir,
                    'InWIP' => $totInWIP,
                    'Output' => $totOut,
                ],
            ];
        }

        return $result;
    }
}
