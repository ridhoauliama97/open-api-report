<?php

return [

    // Laporan Seluruh Mutasi :
    'mutasi_barang_jadi' => [
        'database_connection' => env('MUTASI_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_BARANG_JADI_REPORT_PROCEDURE', 'SP_Mutasi_BarangJadi'),
        'sub_stored_procedure' => env('MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_BarangJadi'),
        'call_syntax' => env('MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_BARANG_JADI_REPORT_QUERY'),
        'sub_query' => env('MUTASI_BARANG_JADI_SUB_REPORT_QUERY'),
    ],
    'mutasi_finger_joint' => [
        'database_connection' => env('MUTASI_FINGER_JOINT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_FINGER_JOINT_REPORT_PROCEDURE', 'SP_Mutasi_FingerJoint'),
        'sub_stored_procedure' => env('MUTASI_FINGER_JOINT_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_FingerJoint'),
        'call_syntax' => env('MUTASI_FINGER_JOINT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_FINGER_JOINT_REPORT_QUERY'),
        'sub_query' => env('MUTASI_FINGER_JOINT_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_FINGER_JOINT_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_FINGER_JOINT_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_moulding' => [
        'database_connection' => env('MUTASI_MOULDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_MOULDING_REPORT_PROCEDURE', 'SP_Mutasi_Moulding'),
        'sub_stored_procedure' => env('MUTASI_MOULDING_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_Moulding'),
        'call_syntax' => env('MUTASI_MOULDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_MOULDING_REPORT_QUERY'),
        'sub_query' => env('MUTASI_MOULDING_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_MOULDING_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_MOULDING_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_laminating' => [
        'database_connection' => env('MUTASI_LAMINATING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_LAMINATING_REPORT_PROCEDURE', 'SP_Mutasi_Laminating'),
        'sub_stored_procedure' => env('MUTASI_LAMINATING_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_Laminating'),
        'call_syntax' => env('MUTASI_LAMINATING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_LAMINATING_REPORT_QUERY'),
        'sub_query' => env('MUTASI_LAMINATING_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_LAMINATING_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_LAMINATING_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_sanding' => [
        'database_connection' => env('MUTASI_SANDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_SANDING_REPORT_PROCEDURE', 'SP_Mutasi_Sanding'),
        'sub_stored_procedure' => env('MUTASI_SANDING_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_Sanding'),
        'call_syntax' => env('MUTASI_SANDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_SANDING_REPORT_QUERY'),
        'sub_query' => env('MUTASI_SANDING_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_SANDING_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_SANDING_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_s4s' => [
        'database_connection' => env('MUTASI_S4S_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_S4S_REPORT_PROCEDURE', 'SP_Mutasi_S4S'),
        'sub_stored_procedure' => env('MUTASI_S4S_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_S4S'),
        'call_syntax' => env('MUTASI_S4S_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_S4S_REPORT_QUERY'),
        'sub_query' => env('MUTASI_S4S_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_S4S_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_S4S_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_st' => [
        'database_connection' => env('MUTASI_ST_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_ST_REPORT_PROCEDURE', 'SP_Mutasi_ST'),
        'sub_stored_procedure' => env('MUTASI_ST_SUB_REPORT_PROCEDURE', ''),
        'call_syntax' => env('MUTASI_ST_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_ST_REPORT_QUERY'),
        'sub_query' => env('MUTASI_ST_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_ST_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_ST_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_cca_akhir' => [
        'database_connection' => env('MUTASI_CCA_AKHIR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_CCA_AKHIR_REPORT_PROCEDURE', 'SP_Mutasi_CCAkhir'),
        'sub_stored_procedure' => env('MUTASI_CCA_AKHIR_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_CCAkhir'),
        'call_syntax' => env('MUTASI_CCA_AKHIR_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_CCA_AKHIR_REPORT_QUERY'),
        'sub_query' => env('MUTASI_CCA_AKHIR_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_CCA_AKHIR_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_CCA_AKHIR_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_reproses' => [
        'database_connection' => env('MUTASI_REPROSES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_REPROSES_REPORT_PROCEDURE', 'SP_Mutasi_Reproses'),
        'sub_stored_procedure' => env('MUTASI_REPROSES_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_Reproses'),
        'call_syntax' => env('MUTASI_REPROSES_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_REPROSES_REPORT_QUERY'),
        'sub_query' => env('MUTASI_REPROSES_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_REPROSES_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_REPROSES_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_kayu_bulat' => [
        'database_connection' => env('MUTASI_KAYU_BULAT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_KAYU_BULAT_REPORT_PROCEDURE', 'SP_Mutasi_KayuBulat'),
        'sub_stored_procedure' => env('MUTASI_KAYU_BULAT_SUB_REPORT_PROCEDURE', ''),
        'call_syntax' => env('MUTASI_KAYU_BULAT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_KAYU_BULAT_REPORT_QUERY'),
        'sub_query' => env('MUTASI_KAYU_BULAT_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_kayu_bulat_v2' => [
        'database_connection' => env('MUTASI_KAYU_BULAT_V2_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_KAYU_BULAT_V2_REPORT_PROCEDURE', 'SP_Mutasi_KayuBulatV2'),
        'sub_stored_procedure' => env('MUTASI_KAYU_BULAT_V2_SUB_REPORT_PROCEDURE', ''),
        'call_syntax' => env('MUTASI_KAYU_BULAT_V2_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_KAYU_BULAT_V2_REPORT_QUERY'),
        'sub_query' => env('MUTASI_KAYU_BULAT_V2_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_V2_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_V2_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_kayu_bulat_kgv2' => [
        'database_connection' => env('MUTASI_KAYU_BULAT_KGV2_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_KAYU_BULAT_KGV2_REPORT_PROCEDURE', 'SP_Mutasi_KayuBulatKGV2'),
        'sub_stored_procedure' => env('MUTASI_KAYU_BULAT_KGV2_SUB_REPORT_PROCEDURE', ''),
        'call_syntax' => env('MUTASI_KAYU_BULAT_KGV2_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_KAYU_BULAT_KGV2_REPORT_QUERY'),
        'sub_query' => env('MUTASI_KAYU_BULAT_KGV2_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_KGV2_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_KGV2_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_kayu_bulat_kg' => [
        'database_connection' => env('MUTASI_KAYU_BULAT_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_KAYU_BULAT_KG_REPORT_PROCEDURE', 'SP_Mutasi_KayuBulatKG'),
        'sub_stored_procedure' => env('MUTASI_KAYU_BULAT_KG_SUB_REPORT_PROCEDURE', ''),
        'call_syntax' => env('MUTASI_KAYU_BULAT_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_KAYU_BULAT_KG_REPORT_QUERY'),
        'sub_query' => env('MUTASI_KAYU_BULAT_KG_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_KG_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_KG_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_hasil_racip' => [
        'database_connection' => env('MUTASI_HASIL_RACIP_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_HASIL_RACIP_REPORT_PROCEDURE', 'SPWps_LapMutasiHasilRacip'),
        'call_syntax' => env('MUTASI_HASIL_RACIP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_HASIL_RACIP_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_HASIL_RACIP_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_racip_detail' => [
        'database_connection' => env('MUTASI_RACIP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_RACIP_DETAIL_REPORT_PROCEDURE', 'SPWps_LapMutasiRacipanDetail'),
        'call_syntax' => env('MUTASI_RACIP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_RACIP_DETAIL_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'MUTASI_RACIP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Sawal,Sawal1,SawalJlhBtg,Masuk,MskJlhBtg,Keluar,KeluarJlhBtg,AdjusmentInput,AdjInJlhBtg,AdjusmentOutput,AdjOutJlhBtg,Akhir,AkhirJlhBtg'
        )))),
    ],

    // Kayu Bulat
    'saldo_kayu_bulat' => [
        'database_connection' => env('SALDO_KAYU_BULAT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SALDO_KAYU_BULAT_REPORT_PROCEDURE', 'SPWps_LapSaldoKayuBulat'),
        'call_syntax' => env('SALDO_KAYU_BULAT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('SALDO_KAYU_BULAT_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('SALDO_KAYU_BULAT_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'rekap_pembelian_kayu_bulat' => [
        'database_connection' => env('REKAP_PEMBELIAN_KAYU_BULAT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PEMBELIAN_KAYU_BULAT_REPORT_PROCEDURE', 'SPWps_LapRekapPembelianKayuBulat'),
        'call_syntax' => env('REKAP_PEMBELIAN_KAYU_BULAT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PEMBELIAN_KAYU_BULAT_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PEMBELIAN_KAYU_BULAT_REPORT_PARAMETER_COUNT', 2),
    ],
    'target_masuk_bb' => [
        'database_connection' => env('TARGET_MASUK_BB_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TARGET_MASUK_BB_REPORT_PROCEDURE', 'SP_LapTargetMasukBB'),
        'call_syntax' => env('TARGET_MASUK_BB_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TARGET_MASUK_BB_REPORT_QUERY'),
        'parameter_count' => (int) env('TARGET_MASUK_BB_REPORT_PARAMETER_COUNT', 2),
    ],
    'target_masuk_bb_bulanan' => [
        'database_connection' => env('TARGET_MASUK_BB_BULANAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TARGET_MASUK_BB_BULANAN_REPORT_PROCEDURE', 'SP_LapTargetMasukBBBulanan'),
        'call_syntax' => env('TARGET_MASUK_BB_BULANAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TARGET_MASUK_BB_BULANAN_REPORT_QUERY'),
        'parameter_count' => (int) env('TARGET_MASUK_BB_BULANAN_REPORT_PARAMETER_COUNT', 2),
    ],
    'stock_racip_kayu_lat' => [
        'database_connection' => env('STOCK_RACIP_KAYU_LAT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_RACIP_KAYU_LAT_REPORT_PROCEDURE', 'sp_LapStockRacipKayuLat'),
        'call_syntax' => env('STOCK_RACIP_KAYU_LAT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_RACIP_KAYU_LAT_REPORT_QUERY'),
        'parameter_count' => (int) env('STOCK_RACIP_KAYU_LAT_REPORT_PARAMETER_COUNT', 1),
    ],
    'hasil_output_racip_harian' => [
        'database_connection' => env('HASIL_OUTPUT_RACIP_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('HASIL_OUTPUT_RACIP_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilOutputRacipHarian'),
        'call_syntax' => env('HASIL_OUTPUT_RACIP_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('HASIL_OUTPUT_RACIP_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('HASIL_OUTPUT_RACIP_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'HASIL_OUTPUT_RACIP_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Jenis,Masuk,Tebal,Lebar,Panjang,JlhBtg'
        )))),
    ],
    'penerimaan_kayu_bulat_bulanan_per_supplier' => [
        'database_connection' => env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_PROCEDURE', 'SP_LaPenerimaanKayuBulatBulananPerSupplier'),
        'sub_stored_procedure' => env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_SUB_REPORT_PROCEDURE', 'SP_SubLaPenerimaanKayuBulatBulananPerSupplier'),
        'call_syntax' => env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_QUERY'),
        'sub_query' => env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'penerimaan_kayu_bulat_per_supplier_group' => [
        'database_connection' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_PROCEDURE', 'SP_LapPenerimaanKBPerSupplierGroup'),
        'call_syntax' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'penerimaan_kayu_bulat_per_supplier_kg' => [
        'database_connection' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_KG_REPORT_PROCEDURE', 'SP_LapPenerimaanKBPerSupplier'),
        'call_syntax' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_KG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_KG_REPORT_EXPECTED_COLUMNS',
            'No Kayu Bulat,Tanggal,Nama Supplier,Jenis,No Truk,Berat'
        )))),
    ],
    'saldo_hidup_kayu_bulat_kg' => [
        'database_connection' => env('SALDO_HIDUP_KAYU_BULAT_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SALDO_HIDUP_KAYU_BULAT_KG_REPORT_PROCEDURE', 'SP_LapSaldoHidupKayuBulatKG'),
        'sub_stored_procedure' => env('SALDO_HIDUP_KAYU_BULAT_KG_SUB_REPORT_PROCEDURE', 'SP_LapSaldoHidupKayuBulatKGSub'),
        'call_syntax' => env('SALDO_HIDUP_KAYU_BULAT_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('SALDO_HIDUP_KAYU_BULAT_KG_REPORT_QUERY'),
        'sub_query' => env('SALDO_HIDUP_KAYU_BULAT_KG_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'SALDO_HIDUP_KAYU_BULAT_KG_REPORT_EXPECTED_COLUMNS',
            'NoKayuBulat,DateCreate,JenisKayu,NoTruk,Suket,NmSupplier,Bruto,Tara,NamaGrade,Berat'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'SALDO_HIDUP_KAYU_BULAT_KG_SUB_REPORT_EXPECTED_COLUMNS',
            'NamaGrade,Berat'
        )))),
    ],
    'rekap_penerimaan_st_dari_sawmill_kg' => [
        'database_connection' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_PROCEDURE', 'SP_LapRekapPenerimaanSTDariSawmill'),
        'call_syntax' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_EXPECTED_COLUMNS',
            'Tanggal,NamaGrade,InOut,Berat'
        )))),
    ],
    'rekap_produktivitas_sawmill_rp' => [
        'database_connection' => env('REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_PROCEDURE', 'SPWps_LapRekapPenerimaanSawmilRp'),
        'sub_stored_procedure' => env('REKAP_PRODUKTIVITAS_SAWMILL_RP_SUB_REPORT_PROCEDURE', 'SPWps_LapSubRekapPenerimaanSawmilRp'),
        'call_syntax' => env('REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_QUERY'),
        'sub_query' => env('REKAP_PRODUKTIVITAS_SAWMILL_RP_SUB_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_PARAMETER_COUNT', 2),
        'sub_parameter_count' => (int) env('REKAP_PRODUKTIVITAS_SAWMILL_RP_SUB_REPORT_PARAMETER_COUNT', 2),
        // If the SP doesn't return Upah (Rp), approximate: Upah = ST(Kg) * upah_per_kg.
        'upah_per_kg' => (float) env('REKAP_PRODUKTIVITAS_SAWMILL_RP_UPAH_PER_KG', '450'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_EXPECTED_COLUMNS',
            'Tanggal,NamaGrade,InOut,Rp'
        )))),
        'sub_expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKTIVITAS_SAWMILL_RP_SUB_REPORT_EXPECTED_COLUMNS',
            'Tanggal,NamaGrade,InOut,Rp'
        )))),
    ],
    'rekap_pembelian_kayu_bulat_kg' => [
        'database_connection' => env('REKAP_PEMBELIAN_KAYU_BULAT_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PEMBELIAN_KAYU_BULAT_KG_REPORT_PROCEDURE', 'SP_LapRekapPembelianKayuBulat'),
        'call_syntax' => env('REKAP_PEMBELIAN_KAYU_BULAT_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PEMBELIAN_KAYU_BULAT_KG_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PEMBELIAN_KAYU_BULAT_KG_REPORT_EXPECTED_COLUMNS',
            'Tahun,Bulan,Ton'
        )))),
    ],
    'timeline_kayu_bulat_bulanan_kg' => [
        'database_connection' => env('TIMELINE_KAYU_BULAT_BULANAN_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TIMELINE_KAYU_BULAT_BULANAN_KG_REPORT_PROCEDURE', 'SP_LapTimelineKBBulananKG'),
        'call_syntax' => env('TIMELINE_KAYU_BULAT_BULANAN_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TIMELINE_KAYU_BULAT_BULANAN_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('TIMELINE_KAYU_BULAT_BULANAN_KG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'TIMELINE_KAYU_BULAT_BULANAN_KG_REPORT_EXPECTED_COLUMNS',
            'Tahun,Bulan,NmSupplier,TonBerat,Ranking'
        )))),
    ],
    'timeline_kayu_bulat_harian_kg' => [
        'database_connection' => env('TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_PROCEDURE', 'SP_LapTimelineKBHarianKG'),
        'call_syntax' => env('TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_EXPECTED_COLUMNS',
            'Tanggal,NmSupplier,TonBerat,Ranking'
        )))),
    ],
    'stock_opname_kayu_bulat' => [
        'database_connection' => env('STOCK_OPNAME_KAYU_BULAT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_OPNAME_KAYU_BULAT_REPORT_PROCEDURE', 'sp_LapStockOpnameKB'),
        'call_syntax' => env('STOCK_OPNAME_KAYU_BULAT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_OPNAME_KAYU_BULAT_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'STOCK_OPNAME_KAYU_BULAT_REPORT_EXPECTED_COLUMNS',
            'NoKayuBulat,Tanggal,JenisKayu,Supplier,NoSuket,NoPlat,NoTruk,Tebal,Lebar,Panjang,Pcs,JmlhTon'
        )))),
    ],
    'rekap_hasil_sawmill_per_meja_upah_borongan_v2' => [
        'database_connection' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_PROCEDURE', 'dbo.SPWps_LapRekapHasilSawmillPerMejaUpahBoronganV2'),
        'sub_stored_procedure' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_SUB_REPORT_PROCEDURE', 'dbo.SPWps_LapRekapHasilSawmillPerMejaUpahBoronganV2_Sub'),
        'call_syntax' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_QUERY'),
        'sub_query' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_SUB_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_EXPECTED_COLUMNS',
            'NoMeja,TglSawmill,Jenis,Operator,Tebal,Lebar,UOM,TonRacip,IdSawmillSpecialCondition,Condition,IsBorongan,NamaMeja'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_SUB_REPORT_EXPECTED_COLUMNS',
            'NoMeja,NamaMeja,TglSawmill,Jenis,Operator,Tebal,Lebar,UOM,TonRacip,IdSawmillSpecialCondition,Condition,SM,IsBorongan'
        )))),
    ],
    'rekap_hasil_sawmill_per_meja_upah_borongan' => [
        'database_connection' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_REPORT_PROCEDURE', 'dbo.SPWps_LapRekapHasilSawmillPerMejaUpahBorongan'),
        'sub_stored_procedure' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_SUB_REPORT_PROCEDURE', 'dbo.SPWps_LapRekapHasilSawmillPerMejaUpahBorongan_Sub'),
        'call_syntax' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_REPORT_QUERY'),
        'sub_query' => env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_SUB_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_REPORT_EXPECTED_COLUMNS',
            'NoMeja,TglSawmill,Jenis,Operator,Tebal,Lebar,UOM,TonRacip,IdSawmillSpecialCondition,Condition,IsBorongan,NamaMeja'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_SUB_REPORT_EXPECTED_COLUMNS',
            'NoMeja,NamaMeja,TglSawmill,Jenis,Operator,Tebal,Lebar,UOM,TonRacip,IdSawmillSpecialCondition,Condition,SM,IsBorongan'
        )))),
    ],
    'rekap_hasil_sawmill_per_meja' => [
        'database_connection' => env('REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_PROCEDURE', 'SPWps_LapRekapHasilSawmillPerMeja'),
        'call_syntax' => env('REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_EXPECTED_COLUMNS',
            'NoMeja,TglSawmill,Tebal,UOM,TonRacip'
        )))),
    ],
    'rekap_produktivitas_sawmill' => [
        'database_connection' => env('REKAP_PRODUKTIVITAS_SAWMILL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKTIVITAS_SAWMILL_REPORT_PROCEDURE', 'SPWps_LapRekapProduktivitasSawmill'),
        'call_syntax' => env('REKAP_PRODUKTIVITAS_SAWMILL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKTIVITAS_SAWMILL_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKTIVITAS_SAWMILL_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKTIVITAS_SAWMILL_REPORT_EXPECTED_COLUMNS',
            'Tanggal,JumlahMeja,JABON,RAMBUNG KAYU L,RAMBUNG MC 1,RAMBUNG MC 2,RAMBUNG STD,Total'
        )))),
    ],
    'pemakaian_obat_vacuum' => [
        'database_connection' => env('PEMAKAIAN_OBAT_VACUUM_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PEMAKAIAN_OBAT_VACUUM_REPORT_PROCEDURE', 'SP_LapPemakaianObatVacuum'),
        'call_syntax' => env('PEMAKAIAN_OBAT_VACUUM_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PEMAKAIAN_OBAT_VACUUM_REPORT_QUERY'),
        'parameter_count' => (int) env('PEMAKAIAN_OBAT_VACUUM_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PEMAKAIAN_OBAT_VACUUM_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'hidup_kb_per_group' => [
        'database_connection' => env('HIDUP_KB_PER_GROUP_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('HIDUP_KB_PER_GROUP_REPORT_PROCEDURE', 'sp_LapHidupKBPerGroup'),
        'call_syntax' => env('HIDUP_KB_PER_GROUP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('HIDUP_KB_PER_GROUP_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'HIDUP_KB_PER_GROUP_REPORT_EXPECTED_COLUMNS',
            'Group,Ton'
        )))),
    ],
    'kayu_bulat_hidup' => [
        'database_connection' => env('KAYU_BULAT_HIDUP_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KAYU_BULAT_HIDUP_REPORT_PROCEDURE', 'SPWps_LapkayuBulatHidup'),
        'call_syntax' => env('KAYU_BULAT_HIDUP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KAYU_BULAT_HIDUP_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KAYU_BULAT_HIDUP_REPORT_EXPECTED_COLUMNS',
            'NoKayuBulat,Tanggal,Supplier,NoTruk,Jenis,Pcs,BlkTepakai,BatangBalokMasuk,BatangBalokTerpakai,FisikBatangBalokDiLapangan'
        )))),
    ],
    'perbandingan_kb_masuk_periode_1_dan_2' => [
        'database_connection' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_PROCEDURE', 'SP_LapPerbandinganKbMasukPeriode1dan2'),
        'call_syntax' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_QUERY'),
        'parameter_count' => (int) env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'perbandingan_kb_masuk_periode_1_dan_2_kg' => [
        'database_connection' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_PROCEDURE', 'sp_LapPerbandinganKBMasukPeriode1dan2KG'),
        'call_syntax' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'kb_khusus_bangkang' => [
        'database_connection' => env('KB_KHUSUS_BANGKANG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KB_KHUSUS_BANGKANG_REPORT_PROCEDURE', 'SP_LapKBKhususBangkang'),
        'call_syntax' => env('KB_KHUSUS_BANGKANG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KB_KHUSUS_BANGKANG_REPORT_QUERY'),
        'parameter_count' => (int) env('KB_KHUSUS_BANGKANG_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KB_KHUSUS_BANGKANG_REPORT_EXPECTED_COLUMNS',
            'No Kayu Bulat, Tanggal , Nama Supplier, Jenis, No Truk, Berat'
        )))),
    ],
    'balok_sudah_semprot' => [
        'database_connection' => env('BALOK_SUDAH_SEMPROT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('BALOK_SUDAH_SEMPROT_REPORT_PROCEDURE', 'SP_LapBalokSudahSemprot'),
        'call_syntax' => env('BALOK_SUDAH_SEMPROT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('BALOK_SUDAH_SEMPROT_REPORT_QUERY'),
        'parameter_count' => (int) env('BALOK_SUDAH_SEMPROT_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'BALOK_SUDAH_SEMPROT_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'timeline_kayu_bulat_harian' => [
        'database_connection' => env('TIMELINE_KAYU_BULAT_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TIMELINE_KAYU_BULAT_HARIAN_REPORT_PROCEDURE', 'SP_LapTimelineKBHarian'),
        'call_syntax' => env('TIMELINE_KAYU_BULAT_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TIMELINE_KAYU_BULAT_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('TIMELINE_KAYU_BULAT_HARIAN_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('TIMELINE_KAYU_BULAT_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'EndDate'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'TIMELINE_KAYU_BULAT_HARIAN_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'timeline_kayu_bulat_bulanan' => [
        'database_connection' => env('TIMELINE_KAYU_BULAT_BULANAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TIMELINE_KAYU_BULAT_BULANAN_REPORT_PROCEDURE', 'SP_LapTimelineKBBulanan'),
        'call_syntax' => env('TIMELINE_KAYU_BULAT_BULANAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TIMELINE_KAYU_BULAT_BULANAN_REPORT_QUERY'),
        'parameter_count' => (int) env('TIMELINE_KAYU_BULAT_BULANAN_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'TIMELINE_KAYU_BULAT_BULANAN_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'umur_kayu_bulat_non_rambung' => [
        'database_connection' => env('UMUR_KAYU_BULAT_NON_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_KAYU_BULAT_NON_RAMBUNG_REPORT_PROCEDURE', 'SPWps_LapUmurKayuBulat'),
        'call_syntax' => env('UMUR_KAYU_BULAT_NON_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_KAYU_BULAT_NON_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_KAYU_BULAT_NON_RAMBUNG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_KAYU_BULAT_NON_RAMBUNG_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'umur_kayu_bulat_rambung' => [
        'database_connection' => env('UMUR_KAYU_BULAT_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_KAYU_BULAT_RAMBUNG_REPORT_PROCEDURE', 'SPWps_LapUmurKayuBulatRambung'),
        'call_syntax' => env('UMUR_KAYU_BULAT_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_KAYU_BULAT_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_KAYU_BULAT_RAMBUNG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_KAYU_BULAT_RAMBUNG_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'supplier_intel' => [
        'database_connection' => env('SUPPLIER_INTEL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SUPPLIER_INTEL_REPORT_PROCEDURE', 'SP_LapSupplierIntel'),
        'call_syntax' => env('SUPPLIER_INTEL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('SUPPLIER_INTEL_REPORT_QUERY'),
        'parameter_count' => (int) env('SUPPLIER_INTEL_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('SUPPLIER_INTEL_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'SUPPLIER_INTEL_REPORT_EXPECTED_COLUMNS',
            'NamaSupplier,DateIn,JlhTruk,TonKB,M3ST'
        )))),
    ],
    // Dashboard
    'dashboard_sawn_timber' => [
        'database_connection' => env('DASHBOARD_SAWN_TIMBER_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_SAWN_TIMBER_REPORT_PROCEDURE', 'SPWps_LapDashboardSawnTimber'),
        'call_syntax' => env('DASHBOARD_SAWN_TIMBER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_SAWN_TIMBER_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_SAWN_TIMBER_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_SAWN_TIMBER_CTR_DIVISOR', '75'),
        'type_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_SAWN_TIMBER_TYPE_ORDER',
            'JABON,JABON TG,KAYU LAT JABON,KAYU LAT RAMBUNG,PULAI,RAMBUNG - MC 1,RAMBUNG - MC 2,RAMBUNG - STD,SEMBARANG'
        )))),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('DASHBOARD_SAWN_TIMBER_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'dashboard_barang_jadi' => [
        'database_connection' => env('DASHBOARD_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_BARANG_JADI_REPORT_PROCEDURE', 'SPWps_LapDashboardBJ'),
        'call_syntax' => env('DASHBOARD_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_BARANG_JADI_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_BARANG_JADI_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_BARANG_JADI_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_BARANG_JADI_COLUMN_ORDER',
            'JABON FILB A/A,JABON FILB B/C,JABON ISOBO,PULAI ISOBO,RAMBUNG FILB A/A,RAMBUNG FILB A/B,RAMBUNG FILB A/C,RAMBUNG FILB C/C'
        )))),
    ],
    'dashboard_cross_cut_akhir' => [
        'database_connection' => env('DASHBOARD_CROSS_CUT_AKHIR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_CROSS_CUT_AKHIR_REPORT_PROCEDURE', 'SPWps_LapDashboardCCAkhir'),
        'call_syntax' => env('DASHBOARD_CROSS_CUT_AKHIR_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_CROSS_CUT_AKHIR_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_CROSS_CUT_AKHIR_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_CROSS_CUT_AKHIR_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_CROSS_CUT_AKHIR_COLUMN_ORDER',
            'JABON FILB A/A,JABON FILB C/C,JABON ISOBO,JABON NISOBO,PULAI ISOBO,PULAI NISOBO,PULAI TASOBO,RAMBUNG A/B,RAMBUNG C/C,RAMBUNG FILB A/A,RAMBUNG FILB A/B,RAMBUNG FILB A/C,RAMBUNG FILB C/C'
        )))),
    ],
    'dashboard_finger_joint' => [
        'database_connection' => env('DASHBOARD_FINGER_JOINT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_FINGER_JOINT_REPORT_PROCEDURE', 'SPWps_LapDashboardFJ'),
        'call_syntax' => env('DASHBOARD_FINGER_JOINT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_FINGER_JOINT_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_FINGER_JOINT_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_FINGER_JOINT_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_FINGER_JOINT_COLUMN_ORDER',
            'JABON A/A,JABON ISOBO,JABON NISOBO,JABON TG A/A,PULAI ISOBO,PULAI NISOBO,RAMBUNG A/A,RAMBUNG A/B,RAMBUNG C/C'
        )))),
    ],
    'dashboard_laminating' => [
        'database_connection' => env('DASHBOARD_LAMINATING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_LAMINATING_REPORT_PROCEDURE', 'SPWps_LapDashboardLaminating'),
        'call_syntax' => env('DASHBOARD_LAMINATING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_LAMINATING_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_LAMINATING_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_LAMINATING_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_LAMINATING_COLUMN_ORDER',
            'JABON FILB A/A,JABON FILB C/C,JABON TASOBO,PULAI NISOBO,PULAI TASOBO,RAMBUNG FILB A/A,RAMBUNG FILB A/B,RAMBUNG FILB C/C'
        )))),
    ],
    'dashboard_moulding' => [
        'database_connection' => env('DASHBOARD_MOULDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_MOULDING_REPORT_PROCEDURE', 'SPWps_LapDashboardMoulding'),
        'call_syntax' => env('DASHBOARD_MOULDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_MOULDING_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_MOULDING_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_MOULDING_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_MOULDING_COLUMN_ORDER',
            'JABON A/A,JABON ISOBO,JABON NISOBO,JABON TASOBO,PULAI ISOBO,PULAI NISOBO,PULAI TASOBO,RAMBUNG A/A,RAMBUNG A/B,RAMBUNG C/C'
        )))),
    ],
    'dashboard_sanding' => [
        'database_connection' => env('DASHBOARD_SANDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_SANDING_REPORT_PROCEDURE', 'SPWps_LapDashboardSanding'),
        'call_syntax' => env('DASHBOARD_SANDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_SANDING_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_SANDING_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_SANDING_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_SANDING_COLUMN_ORDER',
            'JABON FILB A/A,JABON ISOBO,JABON NISOBO,JABON TASOBO,PULAI ISOBO,PULAI NISOBO,PULAI TASOBO,RAMBUNG FILB A/A,RAMBUNG FILB A/B,RAMBUNG FILB C/C'
        )))),
    ],
    'dashboard_s4s' => [
        'database_connection' => env('DASHBOARD_S4S_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_S4S_REPORT_PROCEDURE', 'SPWps_LapDashboardS4S'),
        'call_syntax' => env('DASHBOARD_S4S_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_S4S_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_S4S_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_S4S_CTR_DIVISOR', '65'),
    ],
    'dashboard_s4s_v2' => [
        'database_connection' => env('DASHBOARD_S4S_V2_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_S4S_V2_REPORT_PROCEDURE', 'SPWps_LapDashboardS4S2'),
        'call_syntax' => env('DASHBOARD_S4S_V2_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_S4S_V2_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_S4S_V2_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_S4S_V2_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_S4S_V2_COLUMN_ORDER',
            'JABON A/A,JABON BELAH,JABON ISOBO,JABON MISS TEBAL,JABON NISOBO,JABON TASOBO,JABON TG A/A,JABON TG MISS TEBAL,PULAI BELAH,PULAI ISOBO,PULAI MISS TEBAL,PULAI NISOBO,PULAI TASOBO,RAMBUNG A/A,RAMBUNG A/B,RAMBUNG A/C,RAMBUNG BELAH,RAMBUNG C/C,RAMBUNG MISS TEBAL'
        )))),
    ],
    'stock_st_basah' => [
        'database_connection' => env('STOCK_ST_BASAH_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_ST_BASAH_REPORT_PROCEDURE', 'SP_LapStockSTBasah'),
        'call_syntax' => env('STOCK_ST_BASAH_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_ST_BASAH_REPORT_QUERY'),
        'cache_ttl_seconds' => (int) env('STOCK_ST_BASAH_REPORT_CACHE_TTL_SECONDS', 60),
        'max_sort_rows' => (int) env('STOCK_ST_BASAH_REPORT_MAX_SORT_ROWS', 3000),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('STOCK_ST_BASAH_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'stock_st_kering' => [
        'database_connection' => env('STOCK_ST_KERING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_ST_KERING_REPORT_PROCEDURE', 'SP_LapStockSTKering'),
        'call_syntax' => env('STOCK_ST_KERING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_ST_KERING_REPORT_QUERY'),
        'cache_ttl_seconds' => (int) env('STOCK_ST_KERING_REPORT_CACHE_TTL_SECONDS', 60),
        'max_sort_rows' => (int) env('STOCK_ST_KERING_REPORT_MAX_SORT_ROWS', 3000),
        'preview_json_max_rows' => (int) env('STOCK_ST_KERING_PREVIEW_JSON_MAX_ROWS', 100),
        'preview_pdf_max_rows' => (int) env('STOCK_ST_KERING_PREVIEW_PDF_MAX_ROWS', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('STOCK_ST_KERING_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'st_basah_hidup_per_umur_kayu_ton' => [
        'database_connection' => env('ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_PROCEDURE', 'SP_LapSTBasahHidupPerUmurKayu'),
        'call_syntax' => env('ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_EXPECTED_COLUMNS',
            'Group,Ton2WkLess,Ton2to4Wk,Ton4to6Wk,Ton6to8Wk,Ton8WkMore'
        )))),
    ],
    'penerimaan_st_dari_sawmill_kg' => [
        'database_connection' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_PROCEDURE', 'SPWps_LapRekapPenerimaanSawmilRp'),
        'call_syntax' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'rekap_penerimaan_st_dari_sawmill_non_rambung' => [
        'database_connection' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_PROCEDURE', 'SPWps_LapRekapPenSTDariSawmill'),
        'call_syntax' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'lembar_tally_hasil_sawmill' => [
        'database_connection' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_PROCEDURE', 'SPWps_LapUpahSawmill'),
        'call_syntax' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_QUERY'),
        'parameter_count' => (int) env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_PARAMETER_COUNT', 1),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'umur_sawn_timber_detail_ton' => [
        'database_connection' => env('UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_PROCEDURE', 'SPWps_LapUmurST'),
        'call_syntax' => env('UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5'
        )))),
    ],
    'st_sawmill_masuk_per_group' => [
        'database_connection' => env('ST_SAWMILL_MASUK_PER_GROUP_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_SAWMILL_MASUK_PER_GROUP_REPORT_PROCEDURE', 'SPWps_LapSTMasukPerGroup'),
        'call_syntax' => env('ST_SAWMILL_MASUK_PER_GROUP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_SAWMILL_MASUK_PER_GROUP_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_SAWMILL_MASUK_PER_GROUP_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_SAWMILL_MASUK_PER_GROUP_REPORT_EXPECTED_COLUMNS',
            'Group,Jenis,Tebal,STTon'
        )))),
    ],
    'st_sawmill_masuk_per_group_meja' => [
        'database_connection' => env('ST_SAWMILL_MASUK_PER_GROUP_MEJA_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_SAWMILL_MASUK_PER_GROUP_MEJA_REPORT_PROCEDURE', 'SP_LapSTSawmillMasukPerGroup'),
        'call_syntax' => env('ST_SAWMILL_MASUK_PER_GROUP_MEJA_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_SAWMILL_MASUK_PER_GROUP_MEJA_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_SAWMILL_MASUK_PER_GROUP_MEJA_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_SAWMILL_MASUK_PER_GROUP_MEJA_REPORT_EXPECTED_COLUMNS',
            'Group,Jenis,Tebal,NoMeja,MasukTon'
        )))),
    ],
    'saldo_st_hidup_per_produk' => [
        'database_connection' => env('SALDO_ST_HIDUP_PER_PRODUK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SALDO_ST_HIDUP_PER_PRODUK_REPORT_PROCEDURE', 'SPWps_LapSTHidupPerProduk'),
        'call_syntax' => env('SALDO_ST_HIDUP_PER_PRODUK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('SALDO_ST_HIDUP_PER_PRODUK_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'SALDO_ST_HIDUP_PER_PRODUK_REPORT_EXPECTED_COLUMNS',
            'Group,Produk,Tebal,Lebar,UOM,BasahTon,KDTon,KeringTon,TotalTon'
        )))),
    ],
    'st_hidup_per_spk' => [
        'database_connection' => env('ST_HIDUP_PER_SPK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_HIDUP_PER_SPK_REPORT_PROCEDURE', 'SPWps_LapSTHidupPerProdukV2'),
        'call_syntax' => env('ST_HIDUP_PER_SPK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_HIDUP_PER_SPK_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_HIDUP_PER_SPK_REPORT_EXPECTED_COLUMNS',
            'Group,Produk,NoSPK,Tebal,Lebar,UOM,BasahTon,KDTon,KeringTon,TotalTon'
        )))),
    ],
    'st_sawmill_hari_tebal_lebar' => [
        'database_connection' => env('ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_PROCEDURE', 'SPWps_LapSTSawmillPerHariPerTebalPerLebar'),
        'call_syntax' => env('ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_PARAMETER_COUNT', 2),
        'max_dates_per_table' => (int) env('ST_SAWMILL_HARI_TEBAL_LEBAR_MAX_DATES_PER_TABLE', 10),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_EXPECTED_COLUMNS',
            'TglSawmill,Group,Tebal,Lebar,STton,IsGroup'
        )))),
    ],

    // Laporan Verifikasi :
    'rangkuman_jlh_label_input' => [
        'database_connection' => env('RANGKUMAN_LABEL_INPUT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('RANGKUMAN_LABEL_INPUT_REPORT_PROCEDURE', 'SPWps_LapRangkumanJlhLabelInput'),
        'call_syntax' => env('RANGKUMAN_LABEL_INPUT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('RANGKUMAN_LABEL_INPUT_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('RANGKUMAN_LABEL_INPUT_REPORT_EXPECTED_COLUMNS', '')))),
    ],

    'label_nyangkut' => [
        'database_connection' => env('LABEL_NYANGKUT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LABEL_NYANGKUT_REPORT_PROCEDURE', 'SPWps_LapLabelNyangkut'),
        'call_syntax' => env('LABEL_NYANGKUT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LABEL_NYANGKUT_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('LABEL_NYANGKUT_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'bahan_terpakai' => [
        'database_connection' => env('BAHAN_TERPAKAI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('BAHAN_TERPAKAI_REPORT_PROCEDURE', 'SPWps_LapBahanTerpakai'),
        'sub_stored_procedure' => env('BAHAN_TERPAKAI_SUB_REPORT_PROCEDURE', 'SPWps_LapSubBahanTerpakai'),
        'call_syntax' => env('BAHAN_TERPAKAI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('BAHAN_TERPAKAI_REPORT_QUERY'),
        'sub_query' => env('BAHAN_TERPAKAI_SUB_REPORT_QUERY'),
        'ton_to_m3_factor' => (float) env('BAHAN_TERPAKAI_TON_TO_M3_FACTOR', '1.416'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('BAHAN_TERPAKAI_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('BAHAN_TERPAKAI_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],

    // PPS
    'pps_rekap_produksi_inject' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_INJECT_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_INJECT_REPORT_PROCEDURE', 'SP_LapRekapProduksiInject_FWIP'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_INJECT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_INJECT_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_INJECT_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_INJECT_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_INJECT_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_inject_bj' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_PROCEDURE', 'SP_LapRekapProduksiInject_BJ'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_INJECT_BJ_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_hot_stamping_fwip' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_PROCEDURE', 'SP_LapRekapProduksiHotStamping_FWIP'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_HOT_STAMPING_FWIP_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_packing_bj' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_PROCEDURE', 'SP_LapRekapProduksiPacking_BJ'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_PACKING_BJ_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_pasang_kunci_fwip' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_PROCEDURE', 'SP_LapRekapProduksiPKunci_FWIP'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_PASANG_KUNCI_FWIP_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_spanner_fwip' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_PROCEDURE', 'SP_LapRekapProduksiSpanner_FWIP'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_SPANNER_FWIP_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_broker' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_BROKER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_BROKER_REPORT_PROCEDURE', 'SP_LapRekapProduksiBroker'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_BROKER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_BROKER_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_BROKER_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_BROKER_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_BROKER_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_washing' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_WASHING_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_WASHING_REPORT_PROCEDURE', 'SP_LapRekapProduksiWashing'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_WASHING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_WASHING_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_WASHING_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_WASHING_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_WASHING_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_mixer' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_MIXER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_MIXER_REPORT_PROCEDURE', 'SP_LapRekapProduksiMixer'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_MIXER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_MIXER_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_MIXER_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_MIXER_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_MIXER_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_gilingan' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_GILINGAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_GILINGAN_REPORT_PROCEDURE', 'SP_LapRekapProduksiGilingan'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_GILINGAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_GILINGAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_GILINGAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_GILINGAN_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_GILINGAN_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_rekap_produksi_crusher' => [
        'database_connection' => env('PPS_REKAP_PRODUKSI_CRUSHER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_REKAP_PRODUKSI_CRUSHER_REPORT_PROCEDURE', 'SP_LapRekapProduksiCrusher'),
        'call_syntax' => env('PPS_REKAP_PRODUKSI_CRUSHER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_REKAP_PRODUKSI_CRUSHER_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_REKAP_PRODUKSI_CRUSHER_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_REKAP_PRODUKSI_CRUSHER_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_REKAP_PRODUKSI_CRUSHER_REPORT_EXPECTED_COLUMNS',
            'DimType,ItemCode,Jenis,Pcs,Berat,IdWarehouse'
        )))),
    ],
    'pps_semua_label' => [
        'database_connection' => env('PPS_SEMUA_LABEL_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_SEMUA_LABEL_REPORT_PROCEDURE', 'SP_LaporanSemuaLabel'),
        'call_syntax' => env('PPS_SEMUA_LABEL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_SEMUA_LABEL_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_SEMUA_LABEL_REPORT_PARAMETER_COUNT', 0),
        'single_parameter_name' => env('PPS_SEMUA_LABEL_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_SEMUA_LABEL_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'pps_mutasi_bahan_baku' => [
        'database_connection' => env('PPS_MUTASI_BAHAN_BAKU_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_BAHAN_BAKU_REPORT_PROCEDURE', 'SP_PPSLapMutasiBahanBaku'),
        'call_syntax' => env('PPS_MUTASI_BAHAN_BAKU_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_BAHAN_BAKU_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_MUTASI_BAHAN_BAKU_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_MUTASI_BAHAN_BAKU_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BAHAN_BAKU_REPORT_EXPECTED_COLUMNS',
            'Jenis,Awal,MasukProd,Keluar,Akhir,BSUOutput,BSUInput,BrokerInputBahanBaku,MixerInputBahanBaku,WashInput,Masuk'
        )))),
    ],
    'pps_mutasi_barang_jadi' => [
        'database_connection' => env('PPS_MUTASI_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_BARANG_JADI_REPORT_PROCEDURE', 'SP_PPSLapMutasiBarangJadi'),
        'sub_stored_procedure' => env('PPS_MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiBarangJadi'),
        'waste_stored_procedure' => env('PPS_MUTASI_BARANG_JADI_WASTE_REPORT_PROCEDURE', 'SP_PPSLapWasteMutasiBarangJadi'),
        'call_syntax' => env('PPS_MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_BARANG_JADI_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_BARANG_JADI_SUB_REPORT_QUERY'),
        'waste_query' => env('PPS_MUTASI_BARANG_JADI_WASTE_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BARANG_JADI_REPORT_EXPECTED_COLUMNS',
            'NamaBJ,Awal,Masuk,Keluar,Akhir,PackOutput,InjectOutput,BSUOutput,BSUInput,BJJual,BSortInput,ReturOutput'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BARANG_JADI_SUB_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
        'expected_waste_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BARANG_JADI_WASTE_REPORT_EXPECTED_COLUMNS',
            'Jenis,Berat'
        )))),
    ],
    'pps_mutasi_broker' => [
        'database_connection' => env('PPS_MUTASI_BROKER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_BROKER_REPORT_PROCEDURE', 'SP_PPSLapMutasiBroker'),
        'sub_stored_procedure' => env('PPS_MUTASI_BROKER_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiBroker'),
        'waste_stored_procedure' => env('PPS_MUTASI_BROKER_WASTE_REPORT_PROCEDURE', 'SP_PPSLapWasteMutasiBroker'),
        'call_syntax' => env('PPS_MUTASI_BROKER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_BROKER_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_BROKER_SUB_REPORT_QUERY'),
        'waste_query' => env('PPS_MUTASI_BROKER_WASTE_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BROKER_REPORT_EXPECTED_COLUMNS',
            'Jenis,BeratAwal,BeratMasuk,BeratKeluar,BeratAkhir,OutputBSU,OutputBroker,InputBSU,InputBroker,InputInject,InputMixer'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BROKER_SUB_REPORT_EXPECTED_COLUMNS',
            'DimType,Jenis,InputBroker,InputBahanBaku,InputCrusher,InputGilingan,InputMixer,InputWashing,InputReject,OutputWaste'
        )))),
        'expected_waste_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BROKER_WASTE_REPORT_EXPECTED_COLUMNS',
            'Jenis,OutputWaste'
        )))),
    ],
    'pps_mutasi_bonggolan' => [
        'database_connection' => env('PPS_MUTASI_BONGGOLAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_BONGGOLAN_REPORT_PROCEDURE', 'SP_PPSLapMutasiBonggolan'),
        'sub_stored_procedure' => env('PPS_MUTASI_BONGGOLAN_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiBonggolan'),
        'call_syntax' => env('PPS_MUTASI_BONGGOLAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_BONGGOLAN_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_BONGGOLAN_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BONGGOLAN_REPORT_EXPECTED_COLUMNS',
            'NamaBonggolan,BeratAwal,BeratMasuk,BeratKeluar,BeratAkhir,BeratADJKeluar,BeratBSUKeluar,BeratCRUSKeluar,BeratBROKMasuk,BeratINJCMasuk,BeratADJMasuk,BeratBSUMasuk,BeratGILKeluar,KeluarNot,MasukNot'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_BONGGOLAN_SUB_REPORT_EXPECTED_COLUMNS',
            'NamaBonggolan,Berat'
        )))),
    ],
    'pps_mutasi_crusher' => [
        'database_connection' => env('PPS_MUTASI_CRUSHER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_CRUSHER_REPORT_PROCEDURE', 'SP_PPSLapMutasiCrusher'),
        'sub_stored_procedure' => env('PPS_MUTASI_CRUSHER_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiCrusher'),
        'call_syntax' => env('PPS_MUTASI_CRUSHER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_CRUSHER_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_CRUSHER_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_CRUSHER_REPORT_EXPECTED_COLUMNS',
            'NamaCrusher,BeratAwal,BeratMasuk,BeratKeluar,BeratAkhir,BeratBSUInput,BeratBROKInput,BeratGILInput,BeratProdOutput,BeratBSUOutput'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_CRUSHER_SUB_REPORT_EXPECTED_COLUMNS',
            'Jenis,Berat'
        )))),
    ],
    'pps_mutasi_gilingan' => [
        'database_connection' => env('PPS_MUTASI_GILINGAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_GILINGAN_REPORT_PROCEDURE', 'SP_PPSLapMutasiGilingan'),
        'sub_stored_procedure' => env('PPS_MUTASI_GILINGAN_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiGilingan'),
        'call_syntax' => env('PPS_MUTASI_GILINGAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_GILINGAN_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_GILINGAN_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_GILINGAN_REPORT_EXPECTED_COLUMNS',
            'NamaGilingan,Awal,BeratMasuk,BeratKeluar,Akhir,BeratProdOutput,BeratBSUOutput,BrokInput,InjectInput,MixInput,BeratBSUInput,WashInput'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_GILINGAN_SUB_REPORT_EXPECTED_COLUMNS',
            'Jenis,BeratBong,BeratCrsh,BeratRejc'
        )))),
    ],
    'pps_mutasi_mixer' => [
        'database_connection' => env('PPS_MUTASI_MIXER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_MIXER_REPORT_PROCEDURE', 'SP_PPSLapMutasiMixer'),
        'sub_stored_procedure' => env('PPS_MUTASI_MIXER_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiMixer'),
        'call_syntax' => env('PPS_MUTASI_MIXER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_MIXER_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_MIXER_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_MIXER_REPORT_EXPECTED_COLUMNS',
            'Jenis,BeratAwal,BeratMasuk,MixProdOutput,MixInjectOutput,MixBSUOutput,BeratKeluar,BeratAkhir,InjectInput,BrokInput,MixerInput,BSUInput'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_MIXER_SUB_REPORT_EXPECTED_COLUMNS',
            'Jenis,MixInputBroker,MixInputMix,MixInputGil,MixInputBB'
        )))),
    ],
    'pps_mutasi_furniture_wip' => [
        'database_connection' => env('PPS_MUTASI_FURNITURE_WIP_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MUTASI_FURNITURE_WIP_REPORT_PROCEDURE', 'SP_PPSLapMutasiFurnitureWIP'),
        'sub_stored_procedure' => env('PPS_MUTASI_FURNITURE_WIP_SUB_REPORT_PROCEDURE', 'SP_PPSLapSubMutasiFurnitureWIP'),
        'waste_stored_procedure' => env('PPS_MUTASI_FURNITURE_WIP_WASTE_REPORT_PROCEDURE', 'SP_PPSLapWasteMutasiFurnitureWIP'),
        'call_syntax' => env('PPS_MUTASI_FURNITURE_WIP_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MUTASI_FURNITURE_WIP_REPORT_QUERY'),
        'sub_query' => env('PPS_MUTASI_FURNITURE_WIP_SUB_REPORT_QUERY'),
        'waste_query' => env('PPS_MUTASI_FURNITURE_WIP_WASTE_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_FURNITURE_WIP_REPORT_EXPECTED_COLUMNS',
            'Nama,Awal,Masuk,Keluar,Akhir,OutputInjc,OutHStamp,OutputPKunci,OutputSpan,InputBJSort,InputHStamp,InputPack,InputPKunci,InputSpaner,InputBSU'
        )))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_FURNITURE_WIP_SUB_REPORT_EXPECTED_COLUMNS',
            'DimType,Jenis,BeratInjctBroker,BeratInjctMixer,BeratInjcGili,PcsInjcFWIP,PcsHStamFWIP,PcsPKunciFWIP,PcsSpanFWIP,PcsHStampMaterial,PcsPkncMaterial,PcsSPNMaterial,PcsINJCMaterial'
        )))),
        'expected_waste_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MUTASI_FURNITURE_WIP_WASTE_REPORT_EXPECTED_COLUMNS',
            'Jenis,Berat'
        )))),
    ],


    // Pengaturan otentikasi JWT untuk endpoint laporan
    'report_auth' => [
        'issuers' => array_filter(array_map(
            'trim',
            explode(',', (string) env('REPORT_API_TRUSTED_ISSUERS', env('REPORT_JWT_TRUSTED_ISSUERS', env('REPORT_JWT_TRUSTED_ISSUER', ''))))
        )),
        'audiences' => array_filter(array_map(
            'trim',
            explode(',', (string) env('REPORT_API_TRUSTED_AUDIENCES', env('REPORT_JWT_TRUSTED_AUDIENCES', env('REPORT_JWT_TRUSTED_AUDIENCE', ''))))
        )),
        'required_scope' => env('REPORT_API_REQUIRED_SCOPE', env('REPORT_JWT_REQUIRED_SCOPE')),
        'enforce_scope' => filter_var(env('REPORT_API_ENFORCE_SCOPE', false), FILTER_VALIDATE_BOOL),
        'issued_scope' => env('REPORT_API_ISSUED_SCOPE', env('REPORT_JWT_ISSUED_SCOPE')),
        'jwt_secret' => env('REPORT_API_JWT_SECRET', env('REPORT_JWT_SECRET', env('SECRET_KEY', ''))),
        'jwt_secrets' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'REPORT_API_JWT_SECRETS',
                implode(',', array_filter([
                    (string) env('REPORT_API_JWT_SECRET', ''),
                    (string) env('REPORT_JWT_SECRET', ''),
                    (string) env('SECRET_KEY', ''),
                    (string) env('LEGACY_PASSWORD_KEY', ''),
                    (string) env('APP_KEY', ''),
                ]))
            ))
        ))),
        'jwt_allowed_algs' => array_values(array_filter(array_map(
            static fn(string $alg): string => strtoupper(trim($alg)),
            explode(',', (string) env('REPORT_API_JWT_ALLOWED_ALGS', 'HS256,HS384,HS512'))
        ))),
        'clock_skew_seconds' => (int) env('REPORT_API_JWT_CLOCK_SKEW_SECONDS', 30),
        'scope_claim' => env('REPORT_API_JWT_SCOPE_CLAIM', env('REPORT_JWT_SCOPE_CLAIM', 'scope')),
        'subject_claim' => env('REPORT_API_JWT_SUBJECT_CLAIM', env('REPORT_JWT_SUBJECT_CLAIM', 'sub')),
        'name_claim' => env('REPORT_API_JWT_NAME_CLAIM', env('REPORT_JWT_NAME_CLAIM', 'name')),
        'username_claim' => env('REPORT_API_JWT_USERNAME_CLAIM', env('REPORT_JWT_USERNAME_CLAIM', 'username')),
        'email_claim' => env('REPORT_API_JWT_EMAIL_CLAIM', env('REPORT_JWT_EMAIL_CLAIM', 'email')),
    ],
];
