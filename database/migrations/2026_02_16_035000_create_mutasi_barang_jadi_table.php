<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Definisikan struktur tabel sumber data laporan mutasi barang jadi.
        Schema::create('mutasi_barang_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang');
            $table->string('nama_barang');
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('barang_masuk', 15, 2)->default(0);
            $table->decimal('barang_keluar', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_barang_jadi');
    }
};
