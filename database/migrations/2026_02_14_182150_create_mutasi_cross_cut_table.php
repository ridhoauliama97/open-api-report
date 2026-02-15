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
        Schema::create('mutasi_cross_cut', function (Blueprint $table) {
            $table->id();
            $table->string('jenis');
            $table->decimal('awal', 15, 2)->default(0);
            $table->decimal('adj_out_cca', 15, 2)->default(0);
            $table->decimal('bs_out_cca', 15, 2)->default(0);
            $table->decimal('cca_prod_out', 15, 2)->default(0);
            $table->decimal('total_masuk', 15, 2)->default(0);
            $table->decimal('adj_in_cca', 15, 2)->default(0);
            $table->decimal('bs_in_cca', 15, 2)->default(0);
            $table->decimal('cca_jual', 15, 2)->default(0);
            $table->decimal('fj_prod_input', 15, 2)->default(0);
            $table->decimal('lmt_prod_input', 15, 2)->default(0);
            $table->decimal('mild_prod_input', 15, 2)->default(0);
            $table->decimal('s4s_prod_input', 15, 2)->default(0);
            $table->decimal('sand_prod_input', 15, 2)->default(0);
            $table->decimal('pack_prod_input', 15, 2)->default(0);
            $table->decimal('cca_prod_input', 15, 2)->default(0);
            $table->decimal('total_keluar', 15, 2)->default(0);
            $table->decimal('total_akhir', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_cross_cut');
    }
};
