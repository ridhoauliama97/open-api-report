<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MutasiBarangJadiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mutasi_barang_jadi')->delete();

        $items = [
            ['kode' => 'BJ-001', 'nama' => 'Plywood 12mm'],
            ['kode' => 'BJ-002', 'nama' => 'Plywood 15mm'],
            ['kode' => 'BJ-003', 'nama' => 'Plywood 18mm'],
            ['kode' => 'BJ-004', 'nama' => 'Blockboard 18mm'],
            ['kode' => 'BJ-005', 'nama' => 'MDF 9mm'],
            ['kode' => 'BJ-006', 'nama' => 'MDF 12mm'],
            ['kode' => 'BJ-007', 'nama' => 'Particle Board 18mm'],
            ['kode' => 'BJ-008', 'nama' => 'Veneer A Grade'],
        ];

        $rows = [];

        foreach ($items as $item) {
            $saldoAwal = random_int(200, 3000);
            $barangMasuk = random_int(50, 700);
            $barangKeluar = random_int(30, 600);
            $saldoAkhir = $saldoAwal + $barangMasuk - $barangKeluar;

            $rows[] = [
                'kode_barang' => $item['kode'],
                'nama_barang' => $item['nama'],
                'saldo_awal' => $saldoAwal,
                'barang_masuk' => $barangMasuk,
                'barang_keluar' => $barangKeluar,
                'saldo_akhir' => $saldoAkhir,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('mutasi_barang_jadi')->insert($rows);
    }
}
