<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PenjualanLokalReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = DB::select($this->query(), [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return array_map(static fn(object $row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = ['No', 'Proses', 'Jenis', 'NamaGrade', 'TonAndm3'];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    private function query(): string
    {
        return <<<'SQL'
DECLARE @TglAwal date = :start_date;
DECLARE @TglAkhir date = :end_date;

Select (1) As No, ('ST') As Proses, A.Jenis, (NULL) As NamaGrade, Sum(Round(A.Ton,4,1)) As TonAndm3
From (
    Select D.Jenis,
    Case When (C.IdUOMTblLebar = 1 And C.IdUOMPanjang = 4) Then
        Floor(E.Tebal*E.Lebar*E.Panjang*E.JmlhBatang*215.2542/100000)/10000
    When (C.IdUOMTblLebar = 3 And C.IdUOMPanjang = 4) Then
        Floor(E.Tebal*E.Lebar*E.Panjang*E.JmlhBatang/7200.8*10000)/10000
    End As Ton
    From Penjualan_h A
    Inner Join PenjualanST B On B.NoJual = A.NoJual
    Inner Join ST_h C On C.NoST = B.NoST
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Left Join ST_d E On E.NoST = C.NoST
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
    Group By D.Jenis, C.IdUOMTblLebar, C.IdUOMPanjang, E.Tebal, E.Lebar, E.Panjang, E.JmlhBatang
) A
Group By A.Jenis

Union

Select (2) As No, ('WIP') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanWIP B On B.NoJual = A.NoJual
    Inner Join WIP_h C On C.NoWIP = B.NoWIP
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join WIP_d F On F.NoWIP = C.NoWIP
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (3) As No, ('S4S') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanS4S B On B.NoJual = A.NoJual
    Inner Join S4S_h C On C.NoS4S = B.NoS4S
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join S4S_d F On F.NoS4S = C.NoS4S
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (4) As No, ('FJ') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanFJ B On B.NoJual = A.NoJual
    Inner Join FJ_h C On C.NoFJ = B.NoFJ
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join FJ_d F On F.NoFJ = C.NoFJ
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (5) As No, ('Moulding') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanMoulding B On B.NoJual = A.NoJual
    Inner Join Moulding_h C On C.NoMoulding = B.NoMoulding
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join Moulding_d F On F.NoMoulding = C.NoMoulding
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (6) As No, ('Laminating') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanLaminating B On B.NoJual = A.NoJual
    Inner Join Laminating_h C On C.NoLaminating = B.NoLaminating
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join Laminating_d F On F.NoLaminating = C.NoLaminating
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (7) As No, ('CCAkhir') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanCCAkhir B On B.NoJual = A.NoJual
    Inner Join CCAkhir_h C On C.NoCCAkhir = B.NoCCAkhir
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join CCAkhir_d F On F.NoCCAkhir = C.NoCCAkhir
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (8) As No, ('Sanding') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanSanding B On B.NoJual = A.NoJual
    Inner Join Sanding_h C On C.NoSanding = B.NoSanding
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join Sanding_d F On F.NoSanding = C.NoSanding
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade

Union

Select (9) As No, ('Reproses') As Proses, A.Jenis, A.NamaGrade, Sum(Round(A.m3,4,1)) As TonAndm3
From (
    Select D.Jenis, E.NamaGrade, (F.Tebal*F.Lebar*F.Panjang*F.JmlhBatang/1000000000*10000)/10000 As m3
    From Penjualan_h A
    Inner Join PenjualanReproses B On B.NoJual = A.NoJual
    Inner Join Reproses_h C On C.NoReproses = B.NoReproses
    Inner Join MstJenisKayu D On D.IdJenisKayu = C.IdJenisKayu
    Inner Join MstGrade E On E.IdGrade = C.IdGrade
    Left Join Reproses_d F On F.NoReproses = C.NoReproses
    Where A.TglJual >= @TglAwal And A.TglJual <= @TglAkhir
    And D.IsLokal = 1
) A
Group By A.Jenis, A.NamaGrade
ORDER BY No, Jenis, NamaGrade
SQL;
    }
}
