<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MutasiCrossCutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mutasi_cross_cut')->delete();

        $jenisList = [
            'CCA-KAYU-A',
            'CCA-KAYU-B',
            'CCA-KAYU-C',
            'CCA-BALOK-01',
            'CCA-BALOK-02',
            'CCA-PANEL-01',
            'CCA-PANEL-02',
            'CCA-LOG-01',
            'CCA-LOG-02',
            'CCA-LOG-03',
        ];

        $rows = [];

        foreach ($jenisList as $jenis) {
            $awal = random_int(500, 5000);

            $adjOutCca = random_int(10, 250);
            $bsOutCca = random_int(5, 200);
            $ccaProdOut = random_int(100, 700);
            $totalMasuk = $adjOutCca + $bsOutCca + $ccaProdOut;

            $adjInCca = random_int(10, 150);
            $bsInCca = random_int(5, 120);
            $ccaJual = random_int(50, 600);
            $fjProdInput = random_int(10, 220);
            $lmtProdInput = random_int(10, 220);
            $mildProdInput = random_int(10, 220);
            $s4sProdInput = random_int(10, 220);
            $sandProdInput = random_int(10, 220);
            $packProdInput = random_int(10, 220);
            $ccaProdInput = random_int(20, 320);

            $totalKeluar = $adjInCca
                + $bsInCca
                + $ccaJual
                + $fjProdInput
                + $lmtProdInput
                + $mildProdInput
                + $s4sProdInput
                + $sandProdInput
                + $packProdInput
                + $ccaProdInput;

            $totalAkhir = $awal + $totalMasuk - $totalKeluar;

            $rows[] = [
                'jenis' => $jenis,
                'awal' => $awal,
                'adj_out_cca' => $adjOutCca,
                'bs_out_cca' => $bsOutCca,
                'cca_prod_out' => $ccaProdOut,
                'total_masuk' => $totalMasuk,
                'adj_in_cca' => $adjInCca,
                'bs_in_cca' => $bsInCca,
                'cca_jual' => $ccaJual,
                'fj_prod_input' => $fjProdInput,
                'lmt_prod_input' => $lmtProdInput,
                'mild_prod_input' => $mildProdInput,
                's4s_prod_input' => $s4sProdInput,
                'sand_prod_input' => $sandProdInput,
                'pack_prod_input' => $packProdInput,
                'cca_prod_input' => $ccaProdInput,
                'total_keluar' => $totalKeluar,
                'total_akhir' => $totalAkhir,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('mutasi_cross_cut')->insert($rows);
    }
}
