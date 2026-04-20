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
    'mutasi_barang_jadi_per_jenis_per_ukuran' => [
        'database_connection' => env('MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_PROCEDURE', 'SP_LapMutasiBJPerJenisPerUkuran'),
        'call_syntax' => env('MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,AwalPcs,AwalM3,MasukPcs,MasukM3,MinusPcs,MinusM3,JualPcs,JualM3,AkhirPcs,AkhirM3'
        )))),
    ],
    'saldo_barang_jadi_hidup_per_jenis_per_produk' => [
        'database_connection' => env('SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_PROCEDURE', 'SP_LapBJHidupPerProduk'),
        'call_syntax' => env('SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_EXPECTED_COLUMNS',
            'Jenis,NamaBarangJadi,Tebal,Lebar,Panjang,Pcs,M3'
        )))),
    ],
    'barang_jadi_hidup_detail' => [
        'database_connection' => env('BARANG_JADI_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('BARANG_JADI_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapBJHidupDetail'),
        'call_syntax' => env('BARANG_JADI_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('BARANG_JADI_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('BARANG_JADI_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'BARANG_JADI_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoBJ,Tanggal,NoSPK,Jenis,Tebal,Lebar,Panjang,JmlhBatang,M3,Lokasi'
        )))),
    ],
    'umur_barang_jadi_detail' => [
        'database_connection' => env('UMUR_BARANG_JADI_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_BARANG_JADI_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurBarangJadi'),
        'call_syntax' => env('UMUR_BARANG_JADI_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_BARANG_JADI_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_BARANG_JADI_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_BARANG_JADI_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'rekap_produksi_barang_jadi_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_BARANG_JADI_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKSI_BARANG_JADI_CONSOLIDATED_REPORT_PROCEDURE', 'SP_LapRekapProduksiBarangJadiConsolidated'),
        'call_syntax' => env('REKAP_PRODUKSI_BARANG_JADI_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_BARANG_JADI_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_BARANG_JADI_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
    ],
    'rekap_produksi_packing_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_PACKING_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKSI_PACKING_PER_JENIS_PER_GRADE_REPORT_PROCEDURE', 'SP_LapRekapProduksiBarangJadiPerJenisPerGrade'),
        'call_syntax' => env('REKAP_PRODUKSI_PACKING_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_PACKING_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_PACKING_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
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
    'mutasi_kayu_bulat_v2b' => [
        'database_connection' => env('MUTASI_KAYU_BULAT_V2B_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_KAYU_BULAT_V2B_REPORT_PROCEDURE', 'SP_Mutasi_KayuBulatV2B'),
        'sub_stored_procedure' => env('MUTASI_KAYU_BULAT_V2B_SUB_REPORT_PROCEDURE', ''),
        'call_syntax' => env('MUTASI_KAYU_BULAT_V2B_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_KAYU_BULAT_V2B_REPORT_QUERY'),
        'sub_query' => env('MUTASI_KAYU_BULAT_V2B_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_V2B_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_KAYU_BULAT_V2B_SUB_REPORT_EXPECTED_COLUMNS', '')))),
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
    'flow_produksi_per_periode' => [
        'database_connection' => env('FLOW_PRODUKSI_PER_PERIODE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('FLOW_PRODUKSI_PER_PERIODE_REPORT_PROCEDURE', 'SPWps_LapFlowProduksiPerPeriode'),
        'call_syntax' => env('FLOW_PRODUKSI_PER_PERIODE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('FLOW_PRODUKSI_PER_PERIODE_REPORT_QUERY'),
        'parameter_count' => (int) env('FLOW_PRODUKSI_PER_PERIODE_REPORT_PARAMETER_COUNT', 2),
    ],
    'dashboard_ru' => [
        'database_connection' => env('DASHBOARD_RU_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_RU_REPORT_PROCEDURE', 'SP_LapProduktivitasDashboard'),
        'call_syntax' => env('DASHBOARD_RU_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_RU_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_RU_REPORT_PARAMETER_COUNT', 1),
    ],
    'rekap_mutasi_cross_tab' => [
        'database_connection' => env('REKAP_MUTASI_CROSS_TAB_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_MUTASI_CROSS_TAB_REPORT_PROCEDURE', 'SP_LapRekapMutasi'),
        'call_syntax' => env('REKAP_MUTASI_CROSS_TAB_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_MUTASI_CROSS_TAB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_MUTASI_CROSS_TAB_REPORT_EXPECTED_COLUMNS',
            'Tanggal,BJadi,CCAkhir,FJ,KB,KBKG,LMT,MLD,S4S,SAND,ST,TotalAkhir'
        )))),
    ],
    'produksi_semua_mesin' => [
        'database_connection' => env('PRODUKSI_SEMUA_MESIN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PRODUKSI_SEMUA_MESIN_REPORT_PROCEDURE', 'SPWps_LapProduksiSemuaMesin'),
        'call_syntax' => env('PRODUKSI_SEMUA_MESIN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PRODUKSI_SEMUA_MESIN_REPORT_QUERY'),
        'parameter_count' => (int) env('PRODUKSI_SEMUA_MESIN_REPORT_PARAMETER_COUNT', 2),
    ],
    'produksi_hulu_hilir' => [
        'database_connection' => env('PRODUKSI_HULU_HILIR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PRODUKSI_HULU_HILIR_REPORT_PROCEDURE', 'SPWps_LapProduksiSemuaMesinV2'),
        'call_syntax' => env('PRODUKSI_HULU_HILIR_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PRODUKSI_HULU_HILIR_REPORT_QUERY'),
        'parameter_count' => (int) env('PRODUKSI_HULU_HILIR_REPORT_PARAMETER_COUNT', 2),
    ],
    'hasil_produksi_mesin_lembur_dan_non_lembur' => [
        'database_connection' => env('HASIL_PRODUKSI_MESIN_LEMBUR_DAN_NON_LEMBUR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('HASIL_PRODUKSI_MESIN_LEMBUR_DAN_NON_LEMBUR_REPORT_PROCEDURE', 'SPWps_LapLemburPerMesin'),
        'call_syntax' => env('HASIL_PRODUKSI_MESIN_LEMBUR_DAN_NON_LEMBUR_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('HASIL_PRODUKSI_MESIN_LEMBUR_DAN_NON_LEMBUR_REPORT_QUERY'),
        'parameter_count' => (int) env('HASIL_PRODUKSI_MESIN_LEMBUR_DAN_NON_LEMBUR_REPORT_PARAMETER_COUNT', 2),
    ],
    'label_perhari' => [
        'database_connection' => env('LABEL_PERHARI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LABEL_PERHARI_REPORT_PROCEDURE', 'SPWps_LapLabelPerhari'),
        'call_syntax' => env('LABEL_PERHARI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LABEL_PERHARI_REPORT_QUERY'),
        'parameter_count' => (int) env('LABEL_PERHARI_REPORT_PARAMETER_COUNT', 2),
    ],
    'rekap_stock_on_hand_sub_bj' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_BJ_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_BJ_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubBJ'),
    ],
    'rekap_stock_on_hand_sub_cca_akhir' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_CCA_AKHIR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_CCA_AKHIR_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubCCAkhir'),
    ],
    'rekap_stock_on_hand_sub_fj' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_FJ_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_FJ_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubFJ'),
    ],
    'rekap_stock_on_hand_sub_kb' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_KB_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_KB_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubKB'),
    ],
    'rekap_stock_on_hand_sub_lmt' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_LMT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_LMT_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubLMT'),
    ],
    'rekap_stock_on_hand_sub_moulding' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_MOULDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_MOULDING_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubMoulding'),
    ],
    'rekap_stock_on_hand_sub_reproses' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_REPROSES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_REPROSES_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubReproses'),
    ],
    'rekap_stock_on_hand_sub_s4s' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_S4S_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_S4S_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubS4S'),
    ],
    'rekap_stock_on_hand_sub_sanding' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_SANDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_SANDING_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubSanding'),
    ],
    'rekap_stock_on_hand_sub_st' => [
        'database_connection' => env('REKAP_STOCK_ON_HAND_SUB_ST_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_STOCK_ON_HAND_SUB_ST_REPORT_PROCEDURE', 'SPWps_LapRekapStockOnHand_SubST'),
    ],
    'rangkuman_bongkar_susun' => [
        'database_connection' => env('RANGKUMAN_BONGKAR_SUSUN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('RANGKUMAN_BONGKAR_SUSUN_REPORT_PROCEDURE', 'SPWps_LapRangkumanBongkarSusun'),
        'call_syntax' => env('RANGKUMAN_BONGKAR_SUSUN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('RANGKUMAN_BONGKAR_SUSUN_REPORT_QUERY'),
        'parameter_count' => (int) env('RANGKUMAN_BONGKAR_SUSUN_REPORT_PARAMETER_COUNT', 1),
    ],
    'bahan_yang_dihasilkan' => [
        'database_connection' => env('BAHAN_YANG_DIHASILKAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('BAHAN_YANG_DIHASILKAN_REPORT_PROCEDURE', 'SPWps_LapBahanYangDihasilkan'),
        'call_syntax' => env('BAHAN_YANG_DIHASILKAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('BAHAN_YANG_DIHASILKAN_REPORT_QUERY'),
        'parameter_count' => (int) env('BAHAN_YANG_DIHASILKAN_REPORT_PARAMETER_COUNT', 1),
    ],
    'kapasitas_racip_kayu_bulat_hidup_non_rambung' => [
        'database_connection' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_NON_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_NON_RAMBUNG_REPORT_PROCEDURE', 'SP_KapasitasRacipKayuBulatHidup'),
        'call_syntax' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_NON_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_NON_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_NON_RAMBUNG_REPORT_PARAMETER_COUNT', 0),
    ],
    'kapasitas_racip_kayu_bulat_hidup_jmlh_hk' => [
        'database_connection' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_HK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_HK_REPORT_PROCEDURE', 'SP_KapasitasRacipKayuBulatHidupJmlhHK'),
        'call_syntax' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_HK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_HK_REPORT_QUERY'),
        'parameter_count' => (int) env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_HK_REPORT_PARAMETER_COUNT', 2),
    ],
    'kapasitas_racip_kayu_bulat_hidup_jmlh_meja' => [
        'database_connection' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_MEJA_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_MEJA_REPORT_PROCEDURE', 'SP_KapasitasRacipKayuBulatHidupJmlhMeja'),
        'call_syntax' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_MEJA_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_MEJA_REPORT_QUERY'),
        'parameter_count' => (int) env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_JMLH_MEJA_REPORT_PARAMETER_COUNT', 2),
    ],
    'kapasitas_racip_kayu_bulat_hidup_rambung' => [
        'database_connection' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_RAMBUNG_REPORT_PROCEDURE', 'SP_KapasitasRacipKayuBulatHidupKG'),
        'call_syntax' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('KAPASITAS_RACIP_KAYU_BULAT_HIDUP_RAMBUNG_REPORT_PARAMETER_COUNT', 0),
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
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PEMBELIAN_KAYU_BULAT_REPORT_EXPECTED_COLUMNS',
            'Tahun,Bulan,Ton'
        )))),
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
    'rekap_rendemen_non_rambung' => [
        'database_connection' => env('REKAP_RENDEMEN_NON_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_RENDEMEN_NON_RAMBUNG_REPORT_PROCEDURE', 'SP_LapRekapRendemenNonRambung'),
        'call_syntax' => env('REKAP_RENDEMEN_NON_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_RENDEMEN_NON_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_RENDEMEN_NON_RAMBUNG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_RENDEMEN_NON_RAMBUNG_REPORT_EXPECTED_COLUMNS',
            'Tahun,Bulan,KBKeluarTon,STMasukTon,STKeluarTon,WIPMasukM3,WIPPemakaianNetM3,BJMasukM3'
        )))),
    ],
    'rekap_rendemen_rambung' => [
        'database_connection' => env('REKAP_RENDEMEN_RAMBUNG_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_RENDEMEN_RAMBUNG_REPORT_PROCEDURE', 'SP_LapRekapRendemenRambung'),
        'call_syntax' => env('REKAP_RENDEMEN_RAMBUNG_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_RENDEMEN_RAMBUNG_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_RENDEMEN_RAMBUNG_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_RENDEMEN_RAMBUNG_REPORT_EXPECTED_COLUMNS',
            'Tahun,Bulan,KBKeluarTon,STMasukTon,STKeluarTon,WIPMasukM3,WIPPemakaianNetM3,BJMasukM3'
        )))),
    ],
    'rendemen_semua_proses' => [
        'database_connection' => env('RENDEMEN_SEMUA_PROSES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('RENDEMEN_SEMUA_PROSES_REPORT_PROCEDURE', 'SP_LapRekapRendemenSemuaProses'),
        'call_syntax' => env('RENDEMEN_SEMUA_PROSES_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('RENDEMEN_SEMUA_PROSES_REPORT_QUERY'),
        'parameter_count' => (int) env('RENDEMEN_SEMUA_PROSES_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'RENDEMEN_SEMUA_PROSES_REPORT_EXPECTED_COLUMNS',
            'Tanggal,Input,Output,GRP'
        )))),
    ],
    'produksi_per_spk' => [
        'database_connection' => env('PRODUKSI_PER_SPK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PRODUKSI_PER_SPK_REPORT_PROCEDURE', 'SP_LapProduksiPerSPK'),
        'call_syntax' => env('PRODUKSI_PER_SPK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PRODUKSI_PER_SPK_REPORT_QUERY'),
        'parameter_count' => (int) env('PRODUKSI_PER_SPK_REPORT_PARAMETER_COUNT', 1),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PRODUKSI_PER_SPK_REPORT_EXPECTED_COLUMNS',
            'Group,Output,Input,Rend,RendGlobal'
        )))),
    ],
    'rekap_penjualan_per_produk' => [
        'database_connection' => env('REKAP_PENJUALAN_PER_PRODUK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PENJUALAN_PER_PRODUK_REPORT_PROCEDURE', 'SP_LapJualPerProduk'),
        'call_syntax' => env('REKAP_PENJUALAN_PER_PRODUK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PENJUALAN_PER_PRODUK_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PENJUALAN_PER_PRODUK_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PENJUALAN_PER_PRODUK_REPORT_EXPECTED_COLUMNS',
            'Product,Tebal,Lebar,Panjang,JmlhBatang,M3,BJM3'
        )))),
    ],
    'timeline_rekap_penjualan_per_produk' => [
        'database_connection' => env('TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_PROCEDURE', 'SP_LapJualPerProdukTimeLine'),
        'call_syntax' => env('TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_EXPECTED_COLUMNS',
            'Product,Tebal,Lebar,Panjang,JmlhBatang,M3,BJM3,TglJual'
        )))),
    ],
    'rekap_penjualan_ekspor_per_produk_per_buyer' => [
        'database_connection' => env('REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_PROCEDURE', 'SP_LapJualPerProdukPerBuyer'),
        'call_syntax' => env('REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_EXPECTED_COLUMNS',
            'Product,Pembeli,Tebal,Lebar,Panjang,JmlhBatang,BJM3,PembeliBJM3,M3'
        )))),
    ],
    'rekap_penjualan_ekspor_per_buyer_per_produk' => [
        'database_connection' => env('REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_PROCEDURE', 'SP_LapJualPerBuyerPerProduk'),
        'call_syntax' => env('REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_EXPECTED_COLUMNS',
            'Pembeli,Product,Tebal,Lebar,Panjang,JmlhBatang,PembeliM3,PembeliBJM3,M3'
        )))),
    ],
    'koordinat_tanah' => [
        'database_connection' => env('KOORDINAT_TANAH_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KOORDINAT_TANAH_REPORT_PROCEDURE', 'SP_PrintCariKoordinatTanah'),
        'call_syntax' => env('KOORDINAT_TANAH_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KOORDINAT_TANAH_REPORT_QUERY'),
        'parameter_count' => (int) env('KOORDINAT_TANAH_REPORT_PARAMETER_COUNT', 1),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KOORDINAT_TANAH_REPORT_EXPECTED_COLUMNS',
            'NoSPK,Tanggal,Buyer,Tujuan,Jenis,NamaBarangJadi,Tebal,Lebar,Panjang,Bundle,PcsPerBundle,Keterangan,NamaTanah,NamaPemilik,DesaKelurahan,KabupatenKota,Provinsi,NoSuratTanah,Luas,Koordinat,Periode'
        )))),
        'expected_percentage_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KOORDINAT_TANAH_REPORT_EXPECTED_PERCENTAGE_COLUMNS',
            'Jenis,Total,Persen,Koordinat,NoSPK,Buyer,Tujuan,NamaPemilik,Tahun'
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
            'Status,No.KB,Tanggal,Nama Supplier,Jenis Kayu,Truk,Ton,Tanggal Racip,Tanggal Lama Racip,Lama Racip,Lama Tunggu'
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
            'Status,No.KB,Tanggal,Nama Supplier,Jenis Kayu,Truk,Ton,Tanggal Racip,Lama Racip,Lama Tunggu'
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
    'dashboard_reproses' => [
        'database_connection' => env('DASHBOARD_REPROSES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DASHBOARD_REPROSES_REPORT_PROCEDURE', 'SPWps_LapDashboardReproses'),
        'call_syntax' => env('DASHBOARD_REPROSES_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DASHBOARD_REPROSES_REPORT_QUERY'),
        'parameter_count' => (int) env('DASHBOARD_REPROSES_REPORT_PARAMETER_COUNT', 2),
        'ctr_divisor' => (float) env('DASHBOARD_REPROSES_CTR_DIVISOR', '65'),
        'column_order' => array_filter(array_map('trim', explode(',', (string) env(
            'DASHBOARD_REPROSES_COLUMN_ORDER',
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
    'stock_hidup_per_nospk' => [
        'database_connection' => env('STOCK_HIDUP_PER_NOSPK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_HIDUP_PER_NOSPK_REPORT_PROCEDURE', 'SP_LapSemuaStockHidupPerSPK'),
        'call_syntax' => env('STOCK_HIDUP_PER_NOSPK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_HIDUP_PER_NOSPK_REPORT_QUERY'),
        'using_mode' => (int) env('STOCK_HIDUP_PER_NOSPK_REPORT_USING_MODE', 3),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'STOCK_HIDUP_PER_NOSPK_REPORT_EXPECTED_COLUMNS',
            'Kategori,NoSPK,Jenis,Tebal,Lebar,Panjang,Pcs,Umur,Total,NoContract,Tujuan,Buyer'
        )))),
    ],
    'stock_hidup_per_nospk_discrepancy' => [
        'database_connection' => env('STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_PROCEDURE', 'SP_LapSemuaStockHidupPerSPK'),
        'call_syntax' => env('STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_QUERY'),
        'using_mode' => (int) env('STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_USING_MODE', 1),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_EXPECTED_COLUMNS',
            'Kategori,NoSPK,Jenis,Tebal,Lebar,Panjang,Pcs,Umur,Total,NoContract,Tujuan,Buyer'
        )))),
    ],
    'discrepancy_rekap_mutasi' => [
        'database_connection' => env('DISCREPANCY_REKAP_MUTASI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('DISCREPANCY_REKAP_MUTASI_REPORT_PROCEDURE', 'SP_LapRekapMutasiV2'),
        'call_syntax' => env('DISCREPANCY_REKAP_MUTASI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('DISCREPANCY_REKAP_MUTASI_REPORT_QUERY'),
        'parameter_count' => (int) env('DISCREPANCY_REKAP_MUTASI_REPORT_PARAMETER_COUNT', 3),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'DISCREPANCY_REKAP_MUTASI_REPORT_EXPECTED_COLUMNS',
            'Tanggal,BJadi,CCAkhir,FJ,KB,KBKG,LMT,MLD,S4S,SAND,ST,TotalAkhir'
        )))),
    ],
    'rekap_mutasi' => [
        'database_connection' => env('REKAP_MUTASI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_MUTASI_REPORT_PROCEDURE', 'SP_LapRekapMutasi'),
        'call_syntax' => env('REKAP_MUTASI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_MUTASI_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_MUTASI_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_MUTASI_REPORT_EXPECTED_COLUMNS',
            'Tanggal,BJadi,CCAkhir,FJ,KB,KBKG,LMT,MLD,S4S,SAND,ST,TotalAkhir'
        )))),
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
    'kd_keluar_masuk' => [
        'database_connection' => env('KD_KELUAR_MASUK_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KD_KELUAR_MASUK_REPORT_PROCEDURE', 'SP_LapKDKeluarMasuk'),
        'call_syntax' => env('KD_KELUAR_MASUK_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KD_KELUAR_MASUK_REPORT_QUERY'),
        'parameter_count' => (int) env('KD_KELUAR_MASUK_REPORT_PARAMETER_COUNT', 3),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KD_KELUAR_MASUK_REPORT_EXPECTED_COLUMNS',
            'NoKamarKD,TglMasuk,TglKeluar,JmlhHari,Group,AveTebal,Ton'
        )))),
    ],
    'rekap_kamar_kd' => [
        'database_connection' => env('REKAP_KAMAR_KD_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_KAMAR_KD_REPORT_PROCEDURE', 'SP_LapRekapKamarKD'),
        'sub1_stored_procedure' => env('REKAP_KAMAR_KD_SUB1_REPORT_PROCEDURE', 'SP_LapRekapKamarKD_Sub1'),
        'sub2_stored_procedure' => env('REKAP_KAMAR_KD_SUB2_REPORT_PROCEDURE', 'SP_LapRekapKamarKD_Sub2'),
        'call_syntax' => env('REKAP_KAMAR_KD_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_KAMAR_KD_REPORT_QUERY'),
        'sub1_query' => env('REKAP_KAMAR_KD_SUB1_REPORT_QUERY'),
        'sub2_query' => env('REKAP_KAMAR_KD_SUB2_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_KAMAR_KD_REPORT_PARAMETER_COUNT', 2),
        'sub1_parameter_count' => (int) env('REKAP_KAMAR_KD_SUB1_REPORT_PARAMETER_COUNT', 2),
        'sub2_parameter_count' => (int) env('REKAP_KAMAR_KD_SUB2_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_KAMAR_KD_REPORT_EXPECTED_COLUMNS',
            'NoRuangKD,TglMasuk,TglKeluar,Hari,Jenis,Tebal,Lebar,Ton,AveTebal,AvePanjang'
        )))),
        'expected_sub1_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_KAMAR_KD_SUB1_REPORT_EXPECTED_COLUMNS',
            'NoRuangKD,Jenis,Tebal,Ton,m3'
        )))),
        'expected_sub2_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_KAMAR_KD_SUB2_REPORT_EXPECTED_COLUMNS',
            'NoRuangKD,Jenis'
        )))),
    ],
    'mutasi_kd' => [
        'database_connection' => env('MUTASI_KD_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_KD_REPORT_PROCEDURE', 'SP_LapMutasiKD'),
        'call_syntax' => env('MUTASI_KD_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_KD_REPORT_QUERY'),
        'parameter_count' => (int) env('MUTASI_KD_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'MUTASI_KD_REPORT_EXPECTED_COLUMNS',
            'NoRuangKD,TglMasuk,TonIn,TglKeluar,TonOut'
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
    'umur_s4s_detail' => [
        'database_connection' => env('UMUR_S4S_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_S4S_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurS4S'),
        'call_syntax' => env('UMUR_S4S_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_S4S_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_S4S_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_S4S_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'umur_finger_joint_detail' => [
        'database_connection' => env('UMUR_FINGER_JOINT_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_FINGER_JOINT_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurFingerJoint'),
        'call_syntax' => env('UMUR_FINGER_JOINT_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_FINGER_JOINT_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_FINGER_JOINT_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_FINGER_JOINT_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'umur_laminating_detail' => [
        'database_connection' => env('UMUR_LAMINATING_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_LAMINATING_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurLaminating'),
        'call_syntax' => env('UMUR_LAMINATING_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_LAMINATING_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_LAMINATING_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_LAMINATING_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'umur_moulding_detail' => [
        'database_connection' => env('UMUR_MOULDING_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_MOULDING_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurMoulding'),
        'call_syntax' => env('UMUR_MOULDING_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_MOULDING_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_MOULDING_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_MOULDING_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'umur_reproses_detail' => [
        'database_connection' => env('UMUR_REPROSES_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_REPROSES_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurReproses'),
        'call_syntax' => env('UMUR_REPROSES_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_REPROSES_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_REPROSES_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_REPROSES_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'reproses_hidup_detail' => [
        'database_connection' => env('REPROSES_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REPROSES_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapReprosesHidupDetail'),
        'call_syntax' => env('REPROSES_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REPROSES_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('REPROSES_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REPROSES_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoReproses,DateCreate,NoSPK,Jenis,NamaGrade,Tebal,Lebar,Panjang,JmlhBatang,Kubik,IdLokasi'
        )))),
    ],
    'ketahanan_barang_reproses' => [
        'database_connection' => env('KETAHANAN_BARANG_REPROSES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_REPROSES_REPORT_PROCEDURE', 'SP_LapKetahananBarangReproses'),
        'call_syntax' => env('KETAHANAN_BARANG_REPROSES_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_REPROSES_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_REPROSES_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_REPROSES_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
        )))),
    ],
    'umur_cross_cut_akhir_detail' => [
        'database_connection' => env('UMUR_CROSS_CUT_AKHIR_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_CROSS_CUT_AKHIR_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurCrossCutAkhir'),
        'call_syntax' => env('UMUR_CROSS_CUT_AKHIR_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_CROSS_CUT_AKHIR_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_CROSS_CUT_AKHIR_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_CROSS_CUT_AKHIR_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'cross_cut_akhir_hidup_detail' => [
        'database_connection' => env('CROSS_CUT_AKHIR_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('CROSS_CUT_AKHIR_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapCCAkhirHidupDetail'),
        'call_syntax' => env('CROSS_CUT_AKHIR_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('CROSS_CUT_AKHIR_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('CROSS_CUT_AKHIR_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'CROSS_CUT_AKHIR_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoCCAkhir,DateCreate,NoSPK,Jenis,NamaGrade,Tebal,Lebar,Panjang,JmlhBatang,Kubik,IdLokasi'
        )))),
    ],
    'rekap_produksi_cross_cut_akhir_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_CONSOLIDATED_REPORT_PROCEDURE', 'SP_LapRekapProduksiCrossCutAkhirConsolidated'),
        'call_syntax' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_CROSS_CUT_AKHIR_CONSOLIDATED_REPORT_EXPECTED_COLUMNS',
            'Tanggal,Shift,NamaMesin,JamKerja,JmlhAnggota,BJ,FJ,Laminating,Moulding,Reproses,Wip,OutputCCAkhir'
        )))),
    ],
    'umur_sanding_detail' => [
        'database_connection' => env('UMUR_SANDING_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('UMUR_SANDING_DETAIL_REPORT_PROCEDURE', 'SP_LapUmurSanding'),
        'call_syntax' => env('UMUR_SANDING_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('UMUR_SANDING_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('UMUR_SANDING_DETAIL_REPORT_PARAMETER_COUNT', 4),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'UMUR_SANDING_DETAIL_REPORT_EXPECTED_COLUMNS',
            'Jenis,Tebal,Lebar,Panjang,Period1,Period2,Period3,Period4,Period5,Total'
        )))),
    ],
    'sanding_hidup_detail' => [
        'database_connection' => env('SANDING_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SANDING_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapSandingHidupDetail'),
        'call_syntax' => env('SANDING_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('SANDING_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('SANDING_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'SANDING_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoSanding,DateCreate,NoSPK,Jenis,NamaGrade,Tebal,Lebar,Panjang,JmlhBatang,Kubik,IdLokasi'
        )))),
    ],
    'rekap_produksi_sanding_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_SANDING_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKSI_SANDING_CONSOLIDATED_REPORT_PROCEDURE', 'SP_LapRekapProduksiSandingConsolidated'),
        'call_syntax' => env('REKAP_PRODUKSI_SANDING_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_SANDING_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_SANDING_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_SANDING_CONSOLIDATED_REPORT_EXPECTED_COLUMNS',
            'Tanggal,Shift,NamaMesin,JamKerja,JmlhAnggota,BJ,CCAkhir,FJ,Moulding,Reproses,Wip,OutputSanding'
        )))),
    ],
    'rekap_produksi_laminating_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_LAMINATING_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_LAMINATING_CONSOLIDATED_REPORT_PROCEDURE',
            'SP_LapRekapProduksiLaminatingConsolidated'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_LAMINATING_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_LAMINATING_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_LAMINATING_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_LAMINATING_CONSOLIDATED_REPORT_EXPECTED_COLUMNS',
            'Tanggal,Shift,NamaMesin,JamKerja,JmlhAnggota,BJ,CCAkhir,Moulding,Reproses,Sanding,OutputLaminating'
        )))),
    ],
    'laminating_hidup_detail' => [
        'database_connection' => env('LAMINATING_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LAMINATING_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapLaminatingHidupDetail'),
        'call_syntax' => env('LAMINATING_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LAMINATING_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('LAMINATING_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'LAMINATING_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoLaminating,DateCreate,NoSPK,Jenis,Tebal,Lebar,Panjang,JmlhBatang,Kubik,Lokasi'
        )))),
    ],
    'rekap_produksi_laminating_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_LAMINATING_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_LAMINATING_PER_JENIS_PER_GRADE_REPORT_PROCEDURE',
            'SP_LapRekapProduksiLaminatingPerJenisPerGrade'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_LAMINATING_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_LAMINATING_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_LAMINATING_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_LAMINATING_PER_JENIS_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'Jenis,NamaGrade,Moulding,Sanding,WIP,Reproses,Output'
        )))),
    ],
    'ketahanan_barang_laminating' => [
        'database_connection' => env('KETAHANAN_BARANG_LAMINATING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_LAMINATING_REPORT_PROCEDURE', 'SP_LapKetahananBarangLaminating'),
        'call_syntax' => env('KETAHANAN_BARANG_LAMINATING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_LAMINATING_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_LAMINATING_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_LAMINATING_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
        )))),
    ],
    'rekap_produksi_moulding_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_MOULDING_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_MOULDING_CONSOLIDATED_REPORT_PROCEDURE',
            'SP_LapRekapProduksiMouldingConsolidated'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_MOULDING_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_MOULDING_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_MOULDING_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_MOULDING_CONSOLIDATED_REPORT_EXPECTED_COLUMNS',
            'Tanggal,Shift,NamaMesin,JamKerja,JmlhAnggota,BJ,CCAkhir,FJ,Laminating,Moulding,Reproses,S4S,OutputMoulding,OutputReproses'
        )))),
    ],
    'moulding_hidup_detail' => [
        'database_connection' => env('MOULDING_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MOULDING_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapMouldingHidupDetail'),
        'call_syntax' => env('MOULDING_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MOULDING_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('MOULDING_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'MOULDING_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoMoulding,Tanggal,NoSPK,Jenis,Tebal,Lebar,Panjang,JmlhBatang,M3,Lokasi'
        )))),
    ],
    'rekap_produksi_moulding_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_MOULDING_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_MOULDING_PER_JENIS_PER_GRADE_REPORT_PROCEDURE',
            'SP_LapRekapProduksiMouldingPerJenisPerGrade'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_MOULDING_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_MOULDING_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_MOULDING_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_MOULDING_PER_JENIS_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'Jenis,NamaGrade,S4S,FJ,Moulding,Laminating,CCAkhir,WIP,Reproses,Output,OutputReproses'
        )))),
    ],
    'ketahanan_barang_moulding' => [
        'database_connection' => env('KETAHANAN_BARANG_MOULDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_MOULDING_REPORT_PROCEDURE', 'SP_LapKetahananBarangMoulding'),
        'call_syntax' => env('KETAHANAN_BARANG_MOULDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_MOULDING_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_MOULDING_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_MOULDING_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
        )))),
    ],
    'rekap_produksi_finger_joint_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_PROCEDURE',
            'SP_LapRekapProduksiFingerJointConsolidated'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_EXPECTED_COLUMNS',
            'Tanggal,Shift,NamaMesin,JamKerja,JmlhAnggota,CCAkhir,S4S,OutputFJ'
        )))),
    ],
    'rekap_produksi_finger_joint_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_FINGER_JOINT_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_FINGER_JOINT_PER_JENIS_PER_GRADE_REPORT_PROCEDURE',
            'SP_LapRekapProduksiFingerJointPerJenisPerGrade'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_FINGER_JOINT_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_FINGER_JOINT_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_FINGER_JOINT_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_FINGER_JOINT_PER_JENIS_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'JenisKayu,NamaGrade,InS4S,InCCAkhir,InWIP,Output'
        )))),
    ],
    'finger_joint_hidup_detail' => [
        'database_connection' => env('FINGER_JOINT_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('FINGER_JOINT_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapFingerJointHidupDetail'),
        'call_syntax' => env('FINGER_JOINT_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('FINGER_JOINT_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('FINGER_JOINT_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'FINGER_JOINT_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoFJ,DateCreate,NoSPK,Jenis,Tebal,Lebar,Panjang,JmlhBatang,M3,Lokasi'
        )))),
    ],
    'ketahanan_barang_finger_joint' => [
        'database_connection' => env('KETAHANAN_BARANG_FINGER_JOINT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_FINGER_JOINT_REPORT_PROCEDURE', 'SP_LapKetahananBarangFingerJoint'),
        'call_syntax' => env('KETAHANAN_BARANG_FINGER_JOINT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_FINGER_JOINT_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_FINGER_JOINT_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_FINGER_JOINT_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
        )))),
    ],
    's4s_hidup_detail' => [
        'database_connection' => env('S4S_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('S4S_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapS4SHidupDetail'),
        'call_syntax' => env('S4S_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('S4S_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('S4S_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'S4S_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoS4S,DateCreate,NoSPK,Jenis,Tebal,Lebar,Panjang,JmlhBatang,Kubik,Lokasi'
        )))),
    ],
    'label_s4s_hidup_per_jenis_kayu' => [
        'database_connection' => env('LABEL_S4S_HIDUP_PER_JENIS_KAYU_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LABEL_S4S_HIDUP_PER_JENIS_KAYU_REPORT_PROCEDURE', 'SP_LapS4SPerJenisKayu'),
        'call_syntax' => env('LABEL_S4S_HIDUP_PER_JENIS_KAYU_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LABEL_S4S_HIDUP_PER_JENIS_KAYU_REPORT_QUERY'),
        'parameter_count' => (int) env('LABEL_S4S_HIDUP_PER_JENIS_KAYU_REPORT_PARAMETER_COUNT', 0),
    ],
    'label_s4s_hidup_per_produk_per_jenis_kayu' => [
        'database_connection' => env('LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_PROCEDURE',
            'SP_LapS4SHidupPerProdukdanPerJenis'
        ),
        'call_syntax' => env('LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_QUERY'),
        'parameter_count' => (int) env('LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_PARAMETER_COUNT', 0),
    ],
    'rekap_produksi_s4s_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_S4S_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_S4S_PER_JENIS_PER_GRADE_REPORT_PROCEDURE',
            'SP_LapRekapProduksiS4SPerJenisPerGrade'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_S4S_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_S4S_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_S4S_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
    ],
    'rekap_produksi_s4s_consolidated' => [
        'database_connection' => env('REKAP_PRODUKSI_S4S_CONSOLIDATED_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKSI_S4S_CONSOLIDATED_REPORT_PROCEDURE', 'SP_LapRekapProduksiS4SConsolidated'),
        'call_syntax' => env('REKAP_PRODUKSI_S4S_CONSOLIDATED_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_S4S_CONSOLIDATED_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_S4S_CONSOLIDATED_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_S4S_CONSOLIDATED_REPORT_EXPECTED_COLUMNS',
            'NoProduksi,Tanggal,Shift,NamaMesin,JamKerja,JmlhAnggota,CCAkhir,Reproses,S4S,ST,WIP,OutputS4S'
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
    'rekap_produksi_cross_cut_akhir_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_CROSS_CUT_AKHIR_PER_JENIS_PER_GRADE_REPORT_PROCEDURE',
            'SP_LapRekapProduksiCCAkhirPerJenisPerGrade'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_CROSS_CUT_AKHIR_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_CROSS_CUT_AKHIR_PER_JENIS_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'Jenis,NamaGrade,FJ,Laminating,WIP,Reproses,Output'
        )))),
    ],
    'rekap_produksi_sanding_per_jenis_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env(
            'REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_PROCEDURE',
            'SP_LapRekapProduksiSandingPerJenisPerGrade'
        ),
        'call_syntax' => env('REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'Jenis,NamaGrade,FJ,Moulding,CCAkhir,WIP,Reproses,Output'
        )))),
    ],
    'ketahanan_barang_cross_cut_akhir' => [
        'database_connection' => env('KETAHANAN_BARANG_CROSS_CUT_AKHIR_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_CROSS_CUT_AKHIR_REPORT_PROCEDURE', 'SP_LapKetahananBarangCCAkhir'),
        'call_syntax' => env('KETAHANAN_BARANG_CROSS_CUT_AKHIR_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_CROSS_CUT_AKHIR_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_CROSS_CUT_AKHIR_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_CROSS_CUT_AKHIR_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
        )))),
    ],
    'ketahanan_barang_sanding' => [
        'database_connection' => env('KETAHANAN_BARANG_SANDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_SANDING_REPORT_PROCEDURE', 'SP_LapKetahananBarangSanding'),
        'call_syntax' => env('KETAHANAN_BARANG_SANDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_SANDING_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_SANDING_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_SANDING_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
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
    'pps_washing_produksi_harian' => [
        'database_connection' => env('PPS_WASHING_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_WASHING_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianWashing'),
        'call_syntax' => env('PPS_WASHING_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_WASHING_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_WASHING_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_WASHING_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_WASHING_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,NoProduksi,NoLabel,Jenis,NamaMesin,TglProduksi,Shift,CreateBy,CheckBy1,CheckBy2,ApproveBy,Brt,Stat,JmlhAnggota,Hadir'
        )))),
    ],
    'pps_broker_produksi_harian' => [
        'database_connection' => env('PPS_BROKER_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_BROKER_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianBroker'),
        'call_syntax' => env('PPS_BROKER_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_BROKER_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_BROKER_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_BROKER_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_BROKER_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,NoProduksi,NoLabel,Jenis,NamaMesin,TglProduksi,Shift,CreateBy,CheckBy1,CheckBy2,ApproveBy,Brt,Stat,JmlhAnggota,Hadir'
        )))),
    ],
    'pps_crusher_produksi_harian' => [
        'database_connection' => env('PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianCrusher'),
        'call_syntax' => env('PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoCrusherProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_CRUSHER_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoCrusherProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Berat,CreateBy,CheckBy1,CheckBy2,ApproveBy,JmlhAnggota,Hadir,Stat'
        )))),
    ],
    'pps_gilingan_produksi_harian' => [
        'database_connection' => env('PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianGilingan'),
        'call_syntax' => env('PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_GILINGAN_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Berat,CreateBy,CheckBy1,CheckBy2,ApproveBy,JmlhAnggota,Hadir,Stat'
        )))),
    ],
    'pps_mixer_produksi_harian' => [
        'database_connection' => env('PPS_MIXER_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_MIXER_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianMixer'),
        'call_syntax' => env('PPS_MIXER_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_MIXER_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_MIXER_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_MIXER_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_MIXER_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Berat,CreateBy,CheckBy1,CheckBy2,ApproveBy,JmlhAnggota,Hadir,Stat'
        )))),
    ],
    'pps_packing_produksi_harian' => [
        'database_connection' => env('PPS_PACKING_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_PACKING_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianPacking'),
        'call_syntax' => env('PPS_PACKING_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_PACKING_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_PACKING_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_PACKING_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoPacking'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_PACKING_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Total,Total2,CreateBy,CheckBy1,CheckBy2,ApproveBy'
        )))),
    ],
    'pps_spanner_produksi_harian' => [
        'database_connection' => env('PPS_SPANNER_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_SPANNER_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianSpanner'),
        'call_syntax' => env('PPS_SPANNER_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_SPANNER_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_SPANNER_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_SPANNER_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_SPANNER_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Total,CreateBy,CheckBy1,CheckBy2,ApproveBy'
        )))),
    ],
    'pps_pasang_kunci_produksi_harian' => [
        'database_connection' => env('PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianPasangKunci'),
        'call_syntax' => env('PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_PASANG_KUNCI_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Total,CreateBy,CheckBy1,CheckBy2,ApproveBy'
        )))),
    ],
    'pps_hot_stamping_produksi_harian' => [
        'database_connection' => env('PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianHotStamping'),
        'call_syntax' => env('PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_HOT_STAMPING_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Total,Total2,CreateBy,CheckBy1,CheckBy2,ApproveBy'
        )))),
    ],
    'pps_inject_produksi_harian' => [
        'database_connection' => env('PPS_INJECT_PRODUKSI_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_INJECT_PRODUKSI_HARIAN_REPORT_PROCEDURE', 'SP_LapHasilProduksiHarianInject'),
        'call_syntax' => env('PPS_INJECT_PRODUKSI_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_INJECT_PRODUKSI_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_INJECT_PRODUKSI_HARIAN_REPORT_PARAMETER_COUNT', 1),
        'single_parameter_name' => env('PPS_INJECT_PRODUKSI_HARIAN_REPORT_SINGLE_PARAMETER_NAME', 'NoProduksi'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_INJECT_PRODUKSI_HARIAN_REPORT_EXPECTED_COLUMNS',
            'Tipe,Group,NoProduksi,NoLabel,Tanggal,NamaMesin,Shift,Jenis,Total,Total2,CreateBy,CheckBy1,CheckBy2,ApproveBy,JmlhAnggota,Hadir'
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
    'pps_stock_bonggolan_v2' => [
        'database_connection' => env('PPS_STOCK_BONGGOLAN_V2_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_BONGGOLAN_V2_REPORT_PROCEDURE', 'SP_LaporanStockLabelBonggolan'),
        'call_syntax' => env('PPS_STOCK_BONGGOLAN_V2_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_BONGGOLAN_V2_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_BONGGOLAN_V2_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_BONGGOLAN_V2_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_BONGGOLAN_V2_REPORT_EXPECTED_COLUMNS',
            ''
        )))),
    ],
    'pps_stock_broker' => [
        'database_connection' => env('PPS_STOCK_BROKER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_BROKER_REPORT_PROCEDURE', 'SP_LaporanStockLabelBroker'),
        'call_syntax' => env('PPS_STOCK_BROKER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_BROKER_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_BROKER_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_BROKER_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_BROKER_REPORT_EXPECTED_COLUMNS',
            'DateCreate,Jenis,NoBroker,JmlhSak,Berat,NamaWarehouse,IdLokasi,Blok'
        )))),
    ],
    'pps_stock_crusher' => [
        'database_connection' => env('PPS_STOCK_CRUSHER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_CRUSHER_REPORT_PROCEDURE', 'SP_LaporanStockLabelCrusher'),
        'call_syntax' => env('PPS_STOCK_CRUSHER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_CRUSHER_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_CRUSHER_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_CRUSHER_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_CRUSHER_REPORT_EXPECTED_COLUMNS',
            'DateCreate,NoCrusher,NamaCrusher,Berat,NamaWarehouse,Blok,IdLokasi'
        )))),
    ],
    'pps_stock_gilingan' => [
        'database_connection' => env('PPS_STOCK_GILINGAN_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_GILINGAN_REPORT_PROCEDURE', 'SP_LaporanStockLabelGilingan'),
        'call_syntax' => env('PPS_STOCK_GILINGAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_GILINGAN_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_GILINGAN_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_GILINGAN_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_GILINGAN_REPORT_EXPECTED_COLUMNS',
            'DateCreate,NoGilingan,NamaGilingan,Berat,NamaWarehouse,Blok,IdLokasi'
        )))),
    ],
    'pps_stock_mixer' => [
        'database_connection' => env('PPS_STOCK_MIXER_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_MIXER_REPORT_PROCEDURE', 'SP_LaporanStockLabelMixer'),
        'call_syntax' => env('PPS_STOCK_MIXER_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_MIXER_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_MIXER_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_MIXER_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_MIXER_REPORT_EXPECTED_COLUMNS',
            'DateCreate,Jenis,NoMixer,JmlhSak,Berat,NamaWarehouse,IdLokasi,Blok'
        )))),
    ],
    'pps_stock_reject' => [
        'database_connection' => env('PPS_STOCK_REJECT_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_REJECT_REPORT_PROCEDURE', 'SP_LaporanStockLabelReject'),
        'call_syntax' => env('PPS_STOCK_REJECT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_REJECT_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_REJECT_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_REJECT_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_REJECT_REPORT_EXPECTED_COLUMNS',
            'DateCreate,NoReject,NamaReject,Berat,NamaWarehouse,Blok,IdLokasi'
        )))),
    ],
    'pps_stock_washing' => [
        'database_connection' => env('PPS_STOCK_WASHING_REPORT_DB_CONNECTION', env('DB_CONNECTION_PPS', 'sqlsrv_pps')),
        'stored_procedure' => env('PPS_STOCK_WASHING_REPORT_PROCEDURE', 'SP_LaporanStockLabelWashing'),
        'call_syntax' => env('PPS_STOCK_WASHING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PPS_STOCK_WASHING_REPORT_QUERY'),
        'parameter_count' => (int) env('PPS_STOCK_WASHING_REPORT_PARAMETER_COUNT', 2),
        'single_parameter_name' => env('PPS_STOCK_WASHING_REPORT_SINGLE_PARAMETER_NAME', 'TglAkhir'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PPS_STOCK_WASHING_REPORT_EXPECTED_COLUMNS',
            'DateCreate,Jenis,NoWashing,JmlhSak,Berat,IdLokasi,Blok,NamaWarehouse'
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

    // Sawn timber: Rekap ST Penjualan
    'rekap_st_penjualan' => [
        'database_connection' => env('REKAP_ST_PENJUALAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_ST_PENJUALAN_REPORT_PROCEDURE', 'SP_LapRekapSTPenjualan'),
        'call_syntax' => env('REKAP_ST_PENJUALAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_ST_PENJUALAN_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_ST_PENJUALAN_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_ST_PENJUALAN_REPORT_EXPECTED_COLUMNS',
            'Pembeli,NoSTJual,DateCreate,Jenis,Tebal,Lebar,IdUOMTblLebar,Panjang,IdUOMPanjang,JmlhBatang,Ton'
        )))),
    ],

    // Sawn timber: Pembelian ST per supplier (Ton)
    'pembelian_st_per_supplier_ton' => [
        'database_connection' => env('PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_PROCEDURE', 'SP_LapPembelianSTPerSupplier'),
        'call_syntax' => env('PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_QUERY'),
        'parameter_count' => (int) env('PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_EXPECTED_COLUMNS',
            'NmSupplier,Jenis,TglLaporan,STTon'
        )))),
    ],

    // Sawn timber: Pembelian ST time line (Ton)
    'pembelian_st_timeline_ton' => [
        'database_connection' => env('PEMBELIAN_ST_TIMELINE_TON_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('PEMBELIAN_ST_TIMELINE_TON_REPORT_PROCEDURE', 'SP_LapPembelianSTTimeline'),
        'call_syntax' => env('PEMBELIAN_ST_TIMELINE_TON_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('PEMBELIAN_ST_TIMELINE_TON_REPORT_QUERY'),
        'parameter_count' => (int) env('PEMBELIAN_ST_TIMELINE_TON_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'PEMBELIAN_ST_TIMELINE_TON_REPORT_EXPECTED_COLUMNS',
            'TglLaporan,Jenis,STTon'
        )))),
    ],

    // Sawn timber: Label ST (Hidup) Detail
    'label_st_hidup_detail' => [
        'database_connection' => env('LABEL_ST_HIDUP_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('LABEL_ST_HIDUP_DETAIL_REPORT_PROCEDURE', 'SP_LapLabelSTHidupDetail'),
        'call_syntax' => env('LABEL_ST_HIDUP_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('LABEL_ST_HIDUP_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('LABEL_ST_HIDUP_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'LABEL_ST_HIDUP_DETAIL_REPORT_EXPECTED_COLUMNS',
            'NoST,DateCreate,NoSPK,Jenis,Tebal,Lebar,Panjang,JmlhBatang,Awal,IdLokasi'
        )))),
    ],

    // Sawn timber: Ketahanan Barang Dagang ST
    'ketahanan_barang_st' => [
        'database_connection' => env('KETAHANAN_BARANG_ST_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_ST_REPORT_PROCEDURE', 'SP_LapKetahananBarangST'),
        'call_syntax' => env('KETAHANAN_BARANG_ST_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_ST_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_ST_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_ST_REPORT_EXPECTED_COLUMNS',
            'Jenis,StockTon,Ton'
        )))),
    ],

    // S4S: Ketahanan Barang Dagang S4S
    'ketahanan_barang_s4s' => [
        'database_connection' => env('KETAHANAN_BARANG_S4S_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('KETAHANAN_BARANG_S4S_REPORT_PROCEDURE', 'SP_LapKetahananBarangS4S'),
        'call_syntax' => env('KETAHANAN_BARANG_S4S_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('KETAHANAN_BARANG_S4S_REPORT_QUERY'),
        'parameter_count' => (int) env('KETAHANAN_BARANG_S4S_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'KETAHANAN_BARANG_S4S_REPORT_EXPECTED_COLUMNS',
            'Jenis,Stockm3,m3'
        )))),
    ],

    // S4S: Output Produksi S4S Per Grade
    'output_produksi_s4s_per_grade' => [
        'database_connection' => env('OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_PROCEDURE', 'SPWps_LapProduksiOutputS4SPerGrade'),
        'call_syntax' => env('OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'NoProduksi,Tanggal,Hari,NamaMesin,Jenis,Target,Output,Jns'
        )))),
    ],

    // S4S: Grade ABC Harian
    'grade_abc_harian' => [
        'database_connection' => env('GRADE_ABC_HARIAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('GRADE_ABC_HARIAN_REPORT_PROCEDURE', 'SPWps_LapGradeABCHarian'),
        'call_syntax' => env('GRADE_ABC_HARIAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('GRADE_ABC_HARIAN_REPORT_QUERY'),
        'parameter_count' => (int) env('GRADE_ABC_HARIAN_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'GRADE_ABC_HARIAN_REPORT_EXPECTED_COLUMNS',
            'DATE,IdGradeAbc,NamaGrade,JmlhBatang'
        )))),
    ],

    // S4S: Rekap Produksi Rambung per Grade
    'rekap_produksi_s4s_rambung_per_grade' => [
        'database_connection' => env('REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_PROCEDURE', 'SP_LapRekapProduksiS4SRambungPerGrade'),
        'call_syntax' => env('REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_QUERY'),
        'parameter_count' => (int) env('REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_EXPECTED_COLUMNS',
            'Group,Type,Tanggal,Jenis,Total,GrandTotalPerGroup,RatioDecimal,Ratio'
        )))),
    ],

    // Sawn timber: ST Rambung MC1 & MC2 (Detail)
    'st_rambung_mc1_mc2_detail' => [
        'database_connection' => env('ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_PROCEDURE', 'SP_LapSTRambungMC1danMC2Detail'),
        'call_syntax' => env('ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_EXPECTED_COLUMNS',
            'IdJenisKayu,StartKering,JenisKayu,IsKering,NoST,Tebal,Lebar,Panjang,Pcs,Ton,Kubik'
        )))),
    ],

    // Sawn timber: ST Rambung MC1 & MC2 (Rangkuman)
    'st_rambung_mc1_mc2_rangkuman' => [
        'database_connection' => env('ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_PROCEDURE', 'SP_LapSTRambungMC1danMC2Rangkuman'),
        'call_syntax' => env('ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_PARAMETER_COUNT', 0),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_EXPECTED_COLUMNS',
            'IdJenisKayu,StartKering,JenisKayu,IsKering,Tebal,Lebar,Panjang,Pcs,Ton,Kubik'
        )))),
    ],

    // Sawn timber: ST Hidup Kering
    'st_hidup_kering' => [
        'database_connection' => env('ST_HIDUP_KERING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('ST_HIDUP_KERING_REPORT_PROCEDURE', 'SP_LapSTHidupKering'),
        'call_syntax' => env('ST_HIDUP_KERING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('ST_HIDUP_KERING_REPORT_QUERY'),
        'parameter_count' => (int) env('ST_HIDUP_KERING_REPORT_PARAMETER_COUNT', 2),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env(
            'ST_HIDUP_KERING_REPORT_EXPECTED_COLUMNS',
            'NoST,Tebal,Lebar,JmlhBatang,IdLokasi,UsiaHari,Jenis,BB'
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
