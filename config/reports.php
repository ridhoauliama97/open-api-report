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
    'kb_khusus_bangkang' => [
        'database_connection' => env('KB_KHUSUS_BANGKANG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KB_KHUSUS_BANGKANG_REPORT_PROCEDURE', 'SP_LapKBKhususBangkang'),
        'call_syntax' => env('KB_KHUSUS_BANGKANG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KB_KHUSUS_BANGKANG_REPORT_QUERY'),
        'parameter_count' => (int) env('KB_KHUSUS_BANGKANG_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KB_KHUSUS_BANGKANG_REPORT_EXPECTED_COLUMNS',
            ''
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
    'stock_st_basah' => [
        'database_connection' => env('STOCK_ST_BASAH_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_ST_BASAH_REPORT_PROCEDURE', 'SP_LapStockSTBasah'),
        'call_syntax' => env('STOCK_ST_BASAH_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_ST_BASAH_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('STOCK_ST_BASAH_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'penerimaan_st_dari_sawmill_kg' => [
        'database_connection' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_PROCEDURE', 'SPWps_LapRekapPenerimaanSawmilRp'),
        'call_syntax' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_QUERY'),
        'parameter_count' => (int) env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'lembar_tally_hasil_sawmill' => [
        'database_connection' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_PROCEDURE', 'SPWps_LapUpahSawmill'),
        'call_syntax' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_QUERY'),
        'parameter_count' => (int) env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_PARAMETER_COUNT', 1),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('LEMBAR_TALLY_HASIL_SAWMILL_REPORT_EXPECTED_COLUMNS', '')))),
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
        'clock_skew_seconds' => (int) env('REPORT_API_JWT_CLOCK_SKEW_SECONDS', 30),
        'scope_claim' => env('REPORT_API_JWT_SCOPE_CLAIM', env('REPORT_JWT_SCOPE_CLAIM', 'scope')),
        'subject_claim' => env('REPORT_API_JWT_SUBJECT_CLAIM', env('REPORT_JWT_SUBJECT_CLAIM', 'sub')),
        'name_claim' => env('REPORT_API_JWT_NAME_CLAIM', env('REPORT_JWT_NAME_CLAIM', 'name')),
        'username_claim' => env('REPORT_API_JWT_USERNAME_CLAIM', env('REPORT_JWT_USERNAME_CLAIM', 'username')),
        'email_claim' => env('REPORT_API_JWT_EMAIL_CLAIM', env('REPORT_JWT_EMAIL_CLAIM', 'email')),
    ],
];
