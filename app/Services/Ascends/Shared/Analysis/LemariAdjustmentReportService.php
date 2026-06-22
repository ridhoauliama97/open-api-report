<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LemariAdjustmentReportService
{
    private const TITLE = 'Laporan Adjustment Selisih Lemari';

    private const TEMP_ALM_MAP = [
        'CARS --> MOSQUE' => 'A1',
        'CARS --> AVENGER' => 'A2',
        'CARS --> BARBIE' => 'A3',
        'CARS --> PANORAMA' => 'A4',
        'CARS --> SNOW' => 'A5',
        'CARS --> KITTY' => 'A6',
        'CARS --> DORAEMON' => 'A7',
        'CARS --> LAKE TOBA' => 'A8',
        'CARS --> MEKKAH' => 'A9',
        'CARS --> BATIK FLOWER' => 'AA',
        'CARS --> ANIMAL PRINT' => 'AB',
        'CARS --> PEACOCK' => 'AC',
        'CARS --> ITALY' => 'AD',
        'CARS --> SANTORINI' => 'AE',
        'CARS --> MIKI' => 'AF',
        'CARS --> ZOO KIDS' => 'AG',
        'CARS --> GREEN FOREST' => 'AH',
        'CARS --> PRINCESS' => 'AI',
        'AVENGER --> BARBIE' => 'B1',
        'AVENGER --> MOSQUE' => 'B2',
        'AVENGER --> PANORAMA' => 'B3',
        'AVENGER --> SNOW' => 'B4',
        'AVENGER --> CARS' => 'B5',
        'AVENGER --> KITTY' => 'B6',
        'AVENGER --> DORAEMON' => 'B7',
        'AVENGER --> LAKE TOBA' => 'B8',
        'AVENGER --> MEKKAH' => 'B9',
        'AVENGER --> BATIK FLOWER' => 'BA',
        'AVENGER --> ANIMAL PRINT' => 'BB',
        'AVENGER --> PEACOCK' => 'BC',
        'AVENGER --> ITALY' => 'BD',
        'AVENGER --> SANTORINI' => 'BE',
        'AVENGER --> MIKI' => 'BF',
        'AVENGER --> ZOO KIDS' => 'BG',
        'AVENGER --> GREEN FOREST' => 'BH',
        'AVENGER --> PRINCESS' => 'BI',
        'BARBIE --> AVENGER' => 'C1',
        'BARBIE --> MOSQUE' => 'C2',
        'BARBIE --> PANORAMA' => 'C3',
        'BARBIE --> SNOW' => 'C4',
        'BARBIE --> CARS' => 'C5',
        'BARBIE --> KITTY' => 'C6',
        'BARBIE --> DORAEMON' => 'C7',
        'BARBIE --> LAKE TOBA' => 'C8',
        'BARBIE --> MEKKAH' => 'C9',
        'BARBIE --> BATIK FLOWER' => 'CA',
        'BARBIE --> ANIMAL PRINT' => 'CB',
        'BARBIE --> PEACOCK' => 'CC',
        'BARBIE --> ITALY' => 'CD',
        'BARBIE --> SANTORINI' => 'CE',
        'BARBIE --> MIKI' => 'CF',
        'BARBIE --> ZOO KIDS' => 'CG',
        'BARBIE --> GREEN FOREST' => 'CH',
        'BARBIE --> PRINCESS' => 'CI',
        'BARBIE --> WHALE BOY' => 'CK',
        'BARBIE --> BIRDS' => 'CL',
        'BARBIE --> MARBLE GREY' => 'CM',
        'BARBIE --> EMON & FRIENDS' => 'CN',
        'BARBIE --> CATTY' => 'CO',
        'MOSQUE --> AVENGER' => 'D1',
        'MOSQUE --> PANORAMA' => 'D2',
        'MOSQUE --> SNOW' => 'D3',
        'MOSQUE --> CARS' => 'D4',
        'MOSQUE --> BARBIE' => 'D5',
        'MOSQUE --> KITTY' => 'D6',
        'MOSQUE --> DORAEMON' => 'D7',
        'MOSQUE --> LAKE TOBA' => 'D8',
        'MOSQUE --> MEKKAH' => 'D9',
        'MOSQUE --> BATIK FLOWER' => 'DA',
        'MOSQUE --> ANIMAL PRINT' => 'DB',
        'MOSQUE --> PEACOCK' => 'DC',
        'MOSQUE --> ITALY' => 'DD',
        'MOSQUE --> SANTORINI' => 'DE',
        'MOSQUE --> MIKI' => 'DF',
        'MOSQUE --> ZOO KIDS' => 'DG',
        'MOSQUE --> GREEN FOREST' => 'DH',
        'MOSQUE --> PRINCESS' => 'DI',
        'PANORAMA --> AVENGER' => 'E1',
        'PANORAMA --> AVNGR' => 'E1',
        'PANORAMA --> MOSQUE' => 'E2',
        'PANORAMA --> SNOW' => 'E3',
        'PANORAMA --> BARBIE' => 'E4',
        'PANORAMA --> CARS' => 'E5',
        'PANORAMA --> KITTY' => 'E6',
        'PANORAMA --> DORAEMON' => 'E7',
        'PANORAMA --> LAKE TOBA' => 'E8',
        'PANORAMA --> MEKKAH' => 'E9',
        'PANORAMA --> BATIK FLOWER' => 'EA',
        'PANORAMA --> ANIMAL PRINT' => 'EB',
        'PANORAMA --> PEACOCK' => 'EC',
        'PANORAMA --> ITALY' => 'ED',
        'PANORAMA --> SANTORINI' => 'EE',
        'PANORAMA --> MIKI' => 'EF',
        'PANORAMA --> ZOO KIDS' => 'EG',
        'PANORAMA --> GREEN FOREST' => 'EH',
        'PANORAMA --> PRINCESS' => 'EI',
        'PANORAMA --> BALLERINA' => 'EJ',
        'PANORAMA --> WHALE BOY' => 'EK',
        'PANORAMA --> BIRDS' => 'EL',
        'PANORAMA --> MARBLE GREY' => 'EM',
        'PANORAMA --> EMON & FRIENDS' => 'EN',
        'PANORAMA --> BATIQ' => 'EO',
        'PANORAMA --> CATTY' => 'EP',
        'PANORAMA --> DINOSAUR' => 'EQ',
        'PANORAMA --> RATTAN' => 'ER',
        'PANORAMA --> HIGHLAND' => 'ES',
        'PANORAMA --> SOCCER' => 'ET',
        'PANORAMA --> ONYX' => 'EU',
        'SNOW --> MOSQUE' => 'F1',
        'SNOW --> AVENGER' => 'F2',
        'SNOW --> BARBIE' => 'F3',
        'SNOW --> CARS' => 'F4',
        'SNOW --> PANORAMA' => 'F5',
        'SNOW --> KITTY' => 'F6',
        'SNOW --> DORAEMON' => 'F7',
        'SNOW --> LAKE TOBA' => 'F8',
        'SNOW --> MEKKAH' => 'F9',
        'SNOW --> BATIK FLOWER' => 'FA',
        'SNOW --> ANIMAL PRINT' => 'FB',
        'SNOW --> PEACOCK' => 'FC',
        'SNOW --> ITALY' => 'FD',
        'SNOW --> SANTORINI' => 'FE',
        'SNOW --> MIKI' => 'FF',
        'SNOW --> ZOO KIDS' => 'FG',
        'SNOW --> GREEN FOREST' => 'FH',
        'SNOW --> PRINCESS' => 'FI',
        'KITTY --> MOSQUE' => 'G1',
        'KITTY --> AVENGER' => 'G2',
        'KITTY --> BARBIE' => 'G3',
        'KITTY--> BARBIE' => 'G3',
        'KITTY --> CARS' => 'G4',
        'KITTY --> PANORAMA' => 'G5',
        'KITTY --> DORAEMON' => 'G6',
        'KITTY--> DORAEMON' => 'G6',
        'KITTY --> LAKE TOBA' => 'G7',
        'KITTY--> LAKE TOBA' => 'G7',
        'KITTY --> SNOW' => 'G8',
        'KITTY --> MEKKAH' => 'G9',
        'KITTY --> BATIK FLOWER' => 'GA',
        'KITTY --> ANIMAL PRINT' => 'GB',
        'KITTY --> PEACOCK' => 'GC',
        'KITTY --> ITALY' => 'GD',
        'KITTY --> SANTORINI' => 'GE',
        'KITTY --> MIKI' => 'GF',
        'KITTY --> ZOO KIDS' => 'GG',
        'KITTY --> GREEN FOREST' => 'GH',
        'KITTY --> PRINCESS' => 'GI',
        'DORAEMON --> SNOW' => 'H1',
        'DORAEMON --> MOSQUE' => 'H2',
        'DORAEMON --> AVENGER' => 'H3',
        'DORAEMON --> BARBIE' => 'H4',
        'DORAEMON --> CARS' => 'H5',
        'DORAEMON --> PANORAMA' => 'H6',
        'DORAEMON --> LAKE TOBA' => 'H7',
        'DORAEMON --> KITTY' => 'H8',
        'DORAEMON --> MEKKAH' => 'H9',
        'DORAEMON --> BATIK FLOWER' => 'HA',
        'DORAEMON --> ANIMAL PRINT' => 'HB',
        'DORAEMON --> PEACOCK' => 'HC',
        'DORAEMON --> ITALY' => 'HD',
        'DORAEMON --> SANTORINI' => 'HE',
        'DORAEMON --> MAKI' => 'HF',
        'DORAEMON --> ZOO KIDS' => 'HG',
        'DORAEMON --> GREEN FOREST' => 'HH',
        'DORAEMON --> PRINCESS' => 'HI',
        'LAKE TOBA --> KITTY' => 'I1',
        'LAKE TOBA FULL COLOUR --> KITTY FULL COLOUR' => 'I1',
        'LAKE TOBA --> SNOW' => 'I2',
        'LAKE TOBA FULL COLOUR --> SNOW FULL COLOUR' => 'I2',
        'LAKE TOBA --> MOSQUE' => 'I3',
        'LAKE TOBA FULL COLOUR --> MOSQUE FULL COLOUR' => 'I3',
        'LAKE TOBA --> AVENGER' => 'I4',
        'LAKE TOBA FULL COLOUR --> AVENGER FULL COLOUR' => 'I4',
        'LAKE TOBA --> BARBIE' => 'I5',
        'LAKE TOBA FULL COLOUR --> BARBIE FULL COLOUR' => 'I5',
        'LAKE TOBA --> CARS' => 'I6',
        'LAKE TOBA FULL COLOUR --> CARS FULL COLOUR' => 'I6',
        'LAKE TOBA --> PANORAMA' => 'I7',
        'LAKE TOBA FULL COLOUR --> PANORAMA FULL COLOUR' => 'I7',
        'LAKE TOBA --> DORAEMON' => 'I8',
        'LAKE TOBA FULL COLOUR --> DORAEMON FULL COLOUR' => 'I8',
        'LAKE TOBA --> MEKKAH' => 'I9',
        'LAKE TOBA FULL COLOUR --> MEKKAH' => 'I9',
        'LAKE TOBA --> BATIK FLOWER' => 'IA',
        'LAKE TOBA FULL COLOUR --> BATIK FLOWER' => 'IA',
        'LAKE TOBA --> ANIMAL PRINT' => 'IB',
        'LAKE TOBA FULL COLOUR --> ANIMAL PRINT' => 'IB',
        'LAKE TOBA --> PEACOCK' => 'IC',
        'LAKE TOBA FULL COLOUR --> PEACOCK' => 'IC',
        'LAKE TOBA --> ITALY' => 'ID',
        'LAKE TOBA FULL COLOUR --> ITALY' => 'ID',
        'LAKE TOBA --> SANTORINI' => 'IE',
        'LAKE TOBA FULL COLOUR --> SANTORINI' => 'IE',
        'LAKE TOBA --> MIKI' => 'IF',
        'LAKE TOBA FULL COLOUR --> MIKI' => 'IF',
        'LAKE TOBA --> ZOO KIDS' => 'IG',
        'LAKE TOBA FULL COLOUR --> ZOO KIDS' => 'IG',
        'LAKE TOBA --> GREEN FOREST' => 'IH',
        'LAKE TOBA FULL COLOUR --> GREEN FOREST' => 'IH',
        'LAKE TOBA --> PRINCESS' => 'II',
        'LAKE TOBA FULL COLOUR --> PRINCESS' => 'II',
        'MEKKAH --> KITTY' => 'J1',
        'MEKKAH --> SNOW' => 'J2',
        'MEKKAH --> MOSQUE' => 'J3',
        'MEKKAH --> AVENGER' => 'J4',
        'MEKKAH --> BARBIE' => 'J5',
        'MEKKAH --> CARS' => 'J6',
        'MEKKAH --> PANORAMA' => 'J7',
        'MEKKAH --> DORAEMON' => 'J8',
        'MEKKAH --> LAKE TOBA' => 'J9',
        'MEKKAH --> BATIK FLOWER' => 'JA',
        'MEKKAH --> ANIMAL PRINT' => 'JB',
        'MEKKAH --> PEACOCK' => 'JC',
        'MEKKAH --> ITALY' => 'JD',
        'MEKKAH --> SANTORINI' => 'JE',
        'MEKKAH --> MIKI' => 'JF',
        'MEKKAH --> ZOO KIDS' => 'JG',
        'MEKKAH --> GREEN FOREST' => 'JH',
        'MEKKAH --> PRINCESS' => 'JI',
        'BATIK FLOWER --> KITTY' => 'K1',
        'BATIK FLOWER --> SNOW' => 'K2',
        'BATIK FLOWER --> MOSQUE' => 'K3',
        'BATIK FLOWER --> AVENGER' => 'K4',
        'BATIK FLOWER --> BARBIE' => 'K5',
        'BATIK FLOWER --> CARS' => 'K6',
        'BATIK FLOWER --> PANORAMA' => 'K7',
        'BATIK FLOWER --> DORAEMON' => 'K8',
        'BATIK FLOWER --> LAKE TOBA' => 'K9',
        'BATIK FLOWER --> MEKKAH' => 'KA',
        'BATIK FLOWER --> ANIMAL PRINT' => 'KB',
        'BATIK FLOWER --> PEACOCK' => 'KC',
        'BATIK FLOWER --> ITALY' => 'KD',
        'BATIK FLOWER --> SANTORINI' => 'KE',
        'BATIK FLOWER --> MIKI' => 'KF',
        'BATIK FLOWER --> ZOO KIDS' => 'KG',
        'BATIK FLOWER --> GREEN FOREST' => 'KH',
        'BATIK FLOWER --> PRINCESS' => 'KI',
        'ANIMAL PRINT --> KITTY' => 'L1',
        'ANIMAL PRINT --> SNOW' => 'L2',
        'ANIMAL PRINT --> MOSQUE' => 'L3',
        'ANIMAL PRINT --> AVENGER' => 'L4',
        'ANIMAL PRINT --> BARBIE' => 'L5',
        'ANIMAL PRINT --> CARS' => 'L6',
        'ANIMAL PRINT --> PANORAMA' => 'L7',
        'ANIMAL PRINT --> DORAEMON' => 'L8',
        'ANIMAL PRINT --> LAKE TOBA' => 'L9',
        'ANIMAL PRINT --> MEKKAH' => 'LA',
        'ANIMAL PRINT --> BATIK FLOWER' => 'LB',
        'ANIMAL PRINT --> PEACOCK' => 'LC',
        'ANIMAL PRINT --> ITALY' => 'LD',
        'ANIMAL PRINT --> SANTORINI' => 'LE',
        'ANIMAL PRINT --> MIKI' => 'LF',
        'ANIMAL PRINT --> ZOO KIDS' => 'LG',
        'ANIMAL PRINT --> GREEN FOREST' => 'LH',
        'ANIMAL PRINT --> PRINCESS' => 'LI',
        'ANIMAL PRINT --> BALLERINA' => 'LJ',
        'ANIMAL PRINT --> CATTY' => 'LK',
        'ANIMAL PRINT --> AVNGR' => 'LL',
        'ANIMAL PRINT --> EMON & FRIENDS' => 'LM',
        'ANIMAL PRINT --> RATTAN' => 'LN',
        'ANIMAL PRINT --> DINOSAUR' => 'LO',
        'ANIMAL PRINT --> ONYX' => 'LP',
        'ANIMAL PRINT --> HIGHLAND' => 'LQ',
        'ANIMAL PRINT --> SOCCER' => 'LR',
        'ANIMAL PRINT --> LAKE TOBA' => 'LS',
        'ANIMAL PRINT --> SUNGKAI' => 'LT',
        'PEACOCK --> KITTY' => 'M1',
        'PEACOCK --> SNOW' => 'M2',
        'PEACOCK --> MOSQUE' => 'M3',
        'PEACOCK --> AVENGER' => 'M4',
        'PEACOCK --> BARBIE' => 'M5',
        'PEACOCK --> CARS' => 'M6',
        'PEACOCK --> PANORAMA' => 'M7',
        'PEACOCK --> DORAEMON' => 'M8',
        'PEACOCK --> LAKE TOBA' => 'M9',
        'PEACOCK --> BATIK FLOWER' => 'MA',
        'PEACOCK --> ANIMAL PRINT' => 'MB',
        'PEACOCK --> MEKKAH' => 'MC',
        'PEACOCK --> ITALY' => 'MD',
        'PEACOCK --> SANTORINI' => 'ME',
        'PEACOCK --> MIKI' => 'MF',
        'PEACOCK --> ZOO KIDS' => 'MG',
        'PEACOCK --> GREEN FOREST' => 'MH',
        'PEACOCK --> PRINCESS' => 'MI',
        'ITALY --> KITTY' => 'N1',
        'ITALY --> SNOW' => 'N2',
        'ITALY --> MOSQUE' => 'N3',
        'ITALY --> AVENGER' => 'N4',
        'ITALY --> BARBIE' => 'N5',
        'ITALY --> CARS' => 'N6',
        'ITALY --> PANORAMA' => 'N7',
        'ITALY --> DORAEMON' => 'N8',
        'ITALY --> LAKE TOBA' => 'N9',
        'ITALY --> BATIK FLOWER' => 'NA',
        'ITALY --> ANIMAL PRINT' => 'NB',
        'ITALY --> MEKKAH' => 'NC',
        'ITALY --> PEACOCK' => 'ND',
        'ITALY --> SANTORINI' => 'NE',
        'ITALY --> MIKI' => 'NF',
        'ITALY --> ZOO KIDS' => 'NG',
        'ITALY --> GREEN FOREST' => 'NH',
        'ITALY --> PRINCESS' => 'NI',
        'MIKI --> KITTY' => 'O1',
        'MIKI --> SNOW' => 'O2',
        'MIKI --> MOSQUE' => 'O3',
        'MIKI --> AVENGER' => 'O4',
        'MIKI --> BARBIE' => 'O5',
        'MIKI --> CARS' => 'O6',
        'MIKI --> PANORAMA' => 'O7',
        'MIKI --> DORAEMON' => 'O8',
        'MIKI --> LAKE TOBA' => 'O9',
        'MIKI --> BATIK FLOWER' => 'OA',
        'MIKI --> ANIMAL PRINT' => 'OB',
        'MIKI --> MEKKAH' => 'OC',
        'MIKI --> PEACOCK' => 'OD',
        'MIKI --> SANTORINI' => 'OE',
        'MIKI --> ITALY' => 'OF',
        'MIKI --> ZOO KIDS' => 'OG',
        'MIKI --> GREEN FOREST' => 'OH',
        'MIKI --> PRINCESS' => 'OI',
        'SANTORINI --> KITTY' => 'P1',
        'SANTORINI --> SNOW' => 'P2',
        'SANTORINI --> MOSQUE' => 'P3',
        'SANTORINI --> AVENGER' => 'P4',
        'SANTORINI --> BARBIE' => 'P5',
        'SANTORINI --> CARS' => 'P6',
        'SANTORINI --> PANORAMA' => 'P7',
        'SANTORINI --> DORAEMON' => 'P8',
        'SANTORINI --> LAKE TOBA' => 'P9',
        'SANTORINI --> BATIK FLOWER' => 'PA',
        'SANTORINI --> ANIMAL PRINT' => 'PB',
        'SANTORINI --> MEKKAH' => 'PC',
        'SANTORINI --> PEACOCK' => 'PD',
        'SANTORINI --> ITALY' => 'PE',
        'SANTORINI --> MIKI' => 'PF',
        'SANTORINI --> ZOO KIDS' => 'PG',
        'SANTORINI --> GREEN FOREST' => 'PH',
        'SANTORINI --> PRINCESS' => 'PI',
        'ZOO KIDS --> KITTY' => 'Q1',
        'ZOO KIDS --> SNOW' => 'Q2',
        'ZOO KIDS --> MOSQUE' => 'Q3',
        'ZOO KIDS --> AVENGER' => 'Q4',
        'ZOO KIDS --> BARBIE' => 'Q5',
        'ZOO KIDS --> CARS' => 'Q6',
        'ZOO KIDS --> PANORAMA' => 'Q7',
        'ZOO KIDS --> DORAEMON' => 'Q8',
        'ZOO KIDS --> LAKE TOBA' => 'Q9',
        'ZOO KIDS --> BATIK FLOWER' => 'QA',
        'ZOO KIDS --> ANIMAL PRINT' => 'QB',
        'ZOO KIDS --> MEKKAH' => 'QC',
        'ZOO KIDS --> PEACOCK' => 'QD',
        'ZOO KIDS --> ITALY' => 'QE',
        'ZOO KIDS --> MIKI' => 'QF',
        'ZOO KIDS --> SANTORINI' => 'QG',
        'ZOO KIDS --> GREEN FOREST' => 'QH',
        'ZOO KIDS --> PRINCESS' => 'QI',
        'GREEN FOREST --> KITTY' => 'R1',
        'GREEN FOREST --> SNOW' => 'R2',
        'GREEN FOREST --> MOSQUE' => 'R3',
        'GREEN FOREST --> AVENGER' => 'R4',
        'GREEN FOREST --> BARBIE' => 'R5',
        'GREEN FOREST --> CARS' => 'R6',
        'GREEN FOREST --> PANORAMA' => 'R7',
        'GREEN FOREST --> DORAEMON' => 'R8',
        'GREEN FOREST --> LAKE TOBA' => 'R9',
        'GREEN FOREST --> BATIK FLOWER' => 'RA',
        'GREEN FOREST --> ANIMAL PRINT' => 'RB',
        'GREEN FOREST --> MEKKAH' => 'RC',
        'GREEN FOREST --> PEACOCK' => 'RD',
        'GREEN FOREST --> ITALY' => 'RE',
        'GREEN FOREST --> MIKI' => 'RF',
        'GREEN FOREST --> ZOO KIDS' => 'RG',
        'GREEN FOREST --> SANTORINI' => 'RH',
        'GREEN FOREST --> PRINCESS' => 'RI',
        'PRINCESS --> KITTY' => 'S1',
        'PRINCESS --> SNOW' => 'S2',
        'PRINCESS --> MOSQUE' => 'S3',
        'PRINCESS --> AVENGER' => 'S4',
        'PRINCESS --> BARBIE' => 'S5',
        'PRINCESS --> CARS' => 'S6',
        'PRINCESS --> PANORAMA' => 'S7',
        'PRINCESS --> DORAEMON' => 'S8',
        'PRINCESS --> LAKE TOBA' => 'S9',
        'PRINCESS --> BATIK FLOWER' => 'SA',
        'PRINCESS --> ANIMAL PRINT' => 'SB',
        'PRINCESS --> MEKKAH' => 'SC',
        'PRINCESS --> PEACOCK' => 'SD',
        'PRINCESS --> ITALY' => 'SE',
        'PRINCESS --> MIKI' => 'SF',
        'PRINCESS --> ZOO KIDS' => 'SG',
        'PRINCESS --> SANTORINI' => 'SH',
        'PRINCESS --> GREEN FOREST' => 'SI',
        'BALLERINA --> KITTY' => 'T1',
        'BALLERINA --> SNOW' => 'T2',
        'BALLERINA --> MOSQUE' => 'T3',
        'BALLERINA --> AVENGER' => 'T4',
        'BALLERINA --> BARBIE' => 'T5',
        'BALLERINA --> CARS' => 'T6',
        'BALLERINA --> PANORAMA' => 'T7',
        'BALLERINA --> DORAEMON' => 'T8',
        'BALLERINA --> LAKE TOBA' => 'T9',
        'BALLERINA --> BATIK FLOWER' => 'TA',
        'BALLERINA --> ANIMAL PRINT' => 'TB',
        'BALLERINA --> MEKKAH' => 'TC',
        'BALLERINA --> PEACOCK' => 'TD',
        'BALLERINA --> ITALY' => 'TE',
        'BALLERINA --> MIKI' => 'TF',
        'BALLERINA --> ZOO KIDS' => 'TG',
        'BALLERINA --> SANTORINI' => 'TH',
        'BALLERINA --> PRINCESS' => 'TI',
        'MARBLE GREY --> BIRDS' => 'UA',
        'MARBLE GREY --> PANORAMA' => 'UB',
        'MARBLE GREY --> ZOO KIDS' => 'UC',
        'MARBLE GREY --> CATTY' => 'UD',
        'MARBLE GREY --> MOSQUE' => 'UE',
        'MARBLE GREY --> BARBIE' => 'UF',
        'MARBLE GREY --> WHALE BOY' => 'UG',
        'MARBLE GREY --> EMON & FRIENDS' => 'UH',
    ];

    private const NAME_GROUP_MAP = [
        'A1' => 'CARS --> MOSQUE',
        'A2' => 'CARS --> AVENGER',
        'A3' => 'CARS --> BARBIE',
        'A4' => 'CARS --> PANORAMA',
        'A5' => 'CARS --> SNOW',
        'A6' => 'CARS --> KITTY',
        'A7' => 'CARS --> DORAEMON',
        'A8' => 'CARS --> LAKE TOBA',
        'A9' => 'CARS --> MEKKAH',
        'AA' => 'CARS --> BATIK FLOWER',
        'AB' => 'CARS --> ANIMAL PRINT',
        'AC' => 'CARS --> PEACOOK',
        'AD' => 'CARS --> ITALY',
        'AE' => 'CARS --> SANTORINI',
        'AF' => 'CARS --> MIKI',
        'AG' => 'CARS --> ZOO KIDS',
        'AH' => 'CARS --> GREEN FOREST',
        'AI' => 'CARS --> PRINCESS',
        'B1' => 'AVENGER --> BARBIE',
        'B2' => 'AVENGER --> MOSQUE',
        'B3' => 'AVENGER --> PANORAMA',
        'B4' => 'AVENGER --> SNOW',
        'B5' => 'AVENGER --> CARS',
        'B6' => 'AVENGER --> KITTY',
        'B7' => 'AVENGER --> DORAEMON',
        'B8' => 'AVENGER --> LAKE TOBA',
        'B9' => 'AVENGER --> MEKKAH',
        'BA' => 'AVENGER --> BATIK FLOWER',
        'BB' => 'AVENGER --> ANIMAL PRINT',
        'BC' => 'AVENGER --> PEACOCK',
        'BD' => 'AVENGER --> ITALY',
        'BE' => 'AVENGER --> SANTORINI',
        'BF' => 'AVENGER --> MIKI',
        'BG' => 'AVENGER --> ZOO KIDS',
        'BH' => 'AVENGER --> GREEN FOREST',
        'BI' => 'AVENGER --> PRINCESS',
        'C1' => 'BARBIE --> AVENGER',
        'C2' => 'BARBIE --> MOSQUE',
        'C3' => 'BARBIE --> PANORAMA',
        'C4' => 'BARBIE --> SNOW',
        'C5' => 'BARBIE --> CARS',
        'C6' => 'BARBIE --> KITTY',
        'C7' => 'BARBIE --> DORAEMON',
        'C8' => 'BARBIE --> LAKE TOBA',
        'C9' => 'BARBIE --> MEKKAH',
        'CA' => 'BARBIE --> BATIK FLOWER',
        'CB' => 'BARBIE --> ANIMAL PRINT',
        'CC' => 'BARBIE --> PEACOCK',
        'CD' => 'BARBIE --> ITALY',
        'CE' => 'BARBIE --> SANTORINI',
        'CF' => 'BARBIE --> MIKI',
        'CG' => 'BARBIE --> ZOO KIDS',
        'CH' => 'BARBIE --> GREEN FOREST',
        'CI' => 'BARBIE --> PRINCESS',
        'CK' => 'BARBIE --> WHALE BOY',
        'CL' => 'BARBIE --> BIRDS',
        'CM' => 'BARBIE --> MARBLE GREY',
        'CN' => 'BARBIE --> EMON & FRIENDS',
        'CO' => 'BARBIE --> CATTY',
        'D1' => 'MOSQUE --> AVENGER',
        'D2' => 'MOSQUE --> PANORAMA',
        'D3' => 'MOSQUE --> SNOW',
        'D4' => 'MOSQUE --> CARS',
        'D5' => 'MOSQUE --> BARBIE',
        'D6' => 'MOSQUE --> KITTY',
        'D7' => 'MOSQUE --> DORAEMON',
        'D8' => 'MOSQUE --> LAKE TOBA',
        'D9' => 'MOSQUE --> MEKKAH',
        'DA' => 'MOSQUE --> BATIK FLOWER',
        'DB' => 'MOSQUE --> ANIMAL PRINT',
        'DC' => 'MOSQUE --> PEACOCK',
        'DD' => 'MOSQUE --> ITALY',
        'DE' => 'MOSQUE --> SANTORINI',
        'DF' => 'MOSQUE --> MIKI',
        'DG' => 'MOSQUE --> ZOO KIDS',
        'DH' => 'MOSQUE --> GREEN FOREST',
        'DI' => 'MOSQUE --> PRINCESS',
        'E1' => 'PANORAMA --> AVENGER',
        'E2' => 'PANORAMA --> MOSQUE',
        'E3' => 'PANORAMA --> SNOW',
        'E4' => 'PANORAMA --> BARBIE',
        'E5' => 'PANORAMA --> CARS',
        'E6' => 'PANORAMA --> KITTY',
        'E7' => 'PANORAMA --> DORAEMON',
        'E8' => 'PANORAMA --> LAKE TOBA',
        'E9' => 'PANORAMA --> MEKKAH',
        'EA' => 'PANORAMA --> BATIK FLOWER',
        'EB' => 'PANORAMA --> ANIMAL PRINT',
        'EC' => 'PANORAMA --> PEACOCK',
        'ED' => 'PANORAMA --> ITALY',
        'EE' => 'PANORAMA --> SANTORINI',
        'EF' => 'PANORAMA --> MIKI',
        'EG' => 'PANORAMA --> ZOO KIDS',
        'EH' => 'PANORAMA --> GREEN FOREST',
        'EI' => 'PANORAMA --> PRINCESS',
        'EJ' => 'PANORAMA --> BALLERINA',
        'EK' => 'PANORAMA --> WHALE BOY',
        'EL' => 'PANORAMA --> BIRDS',
        'EM' => 'PANORAMA --> MARBLE GREY',
        'EN' => 'PANORAMA --> EMON & FRIENDS',
        'EO' => 'PANORAMA --> BATIQ',
        'EP' => 'PANORAMA --> CATTY',
        'EQ' => 'PANORAMA --> DINOSAUR',
        'ER' => 'PANORAMA --> RATTAN',
        'ES' => 'PANORAMA --> HIGHLAND',
        'ET' => 'PANORAMA --> SOCCER',
        'EU' => 'PANORAMA --> ONYX',
        'F1' => 'SNOW --> MOSQUE',
        'F2' => 'SNOW --> AVENGER',
        'F3' => 'SNOW --> BARBIE',
        'F4' => 'SNOW --> CARS',
        'F5' => 'SNOW --> PANORAMA',
        'F6' => 'SNOW --> KITTY',
        'F7' => 'SNOW --> DORAEMON',
        'F8' => 'SNOW --> LAKE TOBA',
        'F9' => 'SNOW --> MEKKAH',
        'FA' => 'SNOW --> BATIK FLOWER',
        'FB' => 'SNOW --> ANIMAL PRINT',
        'FC' => 'SNOW --> PEACOCK',
        'FD' => 'SNOW --> ITALY',
        'FE' => 'SNOW --> SANTORINI',
        'FF' => 'SNOW --> MIKI',
        'FG' => 'SNOW --> ZOO KIDS',
        'FH' => 'SNOW --> GREEN FOREST',
        'FI' => 'SNOW --> PRINCESS',
        'G1' => 'KITTY --> MOSQUE',
        'G2' => 'KITTY --> AVENGER',
        'G3' => 'KITTY --> BARBIE',
        'G4' => 'KITTY --> CARS',
        'G5' => 'KITTY --> PANORAMA',
        'G6' => 'KITTY --> DORAEMON',
        'G7' => 'KITTY --> LAKE TOBA',
        'G8' => 'KITTY --> SNOW',
        'G9' => 'KITTY --> MEKKAH',
        'GA' => 'KITTY --> BATIK FLOWER',
        'GB' => 'KITTY --> ANIMAL PRINT',
        'GC' => 'KITTY --> PEACOCK',
        'GD' => 'KITTY --> ITALY',
        'GE' => 'KITTY --> SANTORINI',
        'GF' => 'KITTY --> MIKI',
        'GG' => 'KITTY --> ZOO KIDS',
        'GH' => 'KITTY --> GREEN FOREST',
        'GI' => 'KITTY --> PRINCESS',
        'H1' => 'DORAEMON --> SNOW',
        'H2' => 'DORAEMON --> MOSQUE',
        'H3' => 'DORAEMON --> AVENGER',
        'H4' => 'DORAEMON --> BARBIE',
        'H5' => 'DORAEMON --> CARS',
        'H6' => 'DORAEMON --> PANORAMA',
        'H7' => 'DORAEMON --> LAKE TOBA',
        'H8' => 'DORAEMON --> KITTY',
        'H9' => 'DORAEMON --> MEKKAH',
        'HA' => 'DORAEMON --> BATIK FLOWER',
        'HB' => 'DORAEMON --> ANIMAL PRINT',
        'HC' => 'DORAEMON --> PEACOCK',
        'HD' => 'DORAEMON --> ITALY',
        'HE' => 'DORAEMON --> SANTORINI',
        'HF' => 'DORAEMON --> MIKI',
        'HG' => 'DORAEMON --> ZOO KIDS',
        'HH' => 'DORAEMON --> GREEN FOREST',
        'HI' => 'DORAEMON --> PRINCESS',
        'I1' => 'LAKE TOBA --> KITTY',
        'I2' => 'LAKE TOBA --> SNOW',
        'I3' => 'LAKE TOBA --> MOSQUE',
        'I4' => 'LAKE TOBA --> AVENGER',
        'I5' => 'LAKE TOBA --> BARBIE',
        'I6' => 'LAKE TOBA --> CARS',
        'I7' => 'LAKE TOBA --> PANORAMA',
        'I8' => 'LAKE TOBA --> DORAEMON',
        'I9' => 'LAKE TOBA --> MEKKAH',
        'IA' => 'LAKE TOBA --> BATIK FLOWER',
        'IB' => 'LAKE TOBA --> ANIMAL PRINT',
        'IC' => 'LAKE TOBA --> PEACOCK',
        'ID' => 'LAKE TOBA --> ITALY',
        'IE' => 'LAKE TOBA --> SANTORINI',
        'IF' => 'LAKE TOBA --> MIKI',
        'IG' => 'LAKE TOBA --> ZOO KIDS',
        'IH' => 'LAKE TOBA --> GREEN FOREST',
        'II' => 'LAKE TOBA --> PRINCESS',
        'J1' => 'MEKKAH --> KITTY',
        'J2' => 'MEKKAH --> SNOW',
        'J3' => 'MEKKAH --> MOSQUE',
        'J4' => 'MEKKAH --> AVENGER',
        'J5' => 'MEKKAH --> BARBIE',
        'J6' => 'MEKKAH --> CARS',
        'J7' => 'MEKKAH --> PANORAMA',
        'J8' => 'MEKKAH --> DORAEMON',
        'J9' => 'MEKKAH --> LAKE TOBA',
        'JA' => 'MEKKAH --> BATIK FLOWER',
        'JB' => 'MEKKAH --> ANIMAL PRINT',
        'JC' => 'MEKKAH --> PEACOCK',
        'JD' => 'MEKKAH --> ITALY',
        'JE' => 'MEKKAH --> SANTORINI',
        'JF' => 'MEKKAH --> MIKI',
        'JG' => 'MEKKAH --> ZOO KIDS',
        'JH' => 'MEKKAH --> GREEN FOREST',
        'JI' => 'MEKKAH --> PRINCESS',
        'K1' => 'BATIK FLOWER --> KITTY',
        'K2' => 'BATIK FLOWER --> SNOW',
        'K3' => 'BATIK FLOWER --> MOSQUE',
        'K4' => 'BATIK FLOWER --> AVENGER',
        'K5' => 'BATIK FLOWER --> BARBIE',
        'K6' => 'BATIK FLOWER --> CARS',
        'K7' => 'BATIK FLOWER --> PANORAMA',
        'K8' => 'BATIK FLOWER --> DORAEMON',
        'K9' => 'BATIK FLOWER --> LAKE TOBA',
        'KA' => 'BATIK FLOWER --> MEKKAH',
        'KB' => 'BATIK FLOWER --> ANIMAL PRINT',
        'KC' => 'BATIK FLOWER --> PEACOCK',
        'KD' => 'BATIK FLOWER --> ITALY',
        'KE' => 'BATIK FLOWER --> SANTORINI',
        'KF' => 'BATIK FLOWER --> MIKI',
        'KG' => 'BATIK FLOWER --> ZOO KIDS',
        'KH' => 'BATIK FLOWER --> GREEN FOREST',
        'KI' => 'BATIK FLOWER --> PRINCESS',
        'L1' => 'ANIMAL PRINT --> KITTY',
        'L2' => 'ANIMAL PRINT --> SNOW',
        'L3' => 'ANIMAL PRINT --> MOSQUE',
        'L4' => 'ANIMAL PRINT --> AVENGER',
        'L5' => 'ANIMAL PRINT --> BARBIE',
        'L6' => 'ANIMAL PRINT --> CARS',
        'L7' => 'ANIMAL PRINT --> PANORAMA',
        'L8' => 'ANIMAL PRINT --> DORAEMON',
        'L9' => 'ANIMAL PRINT --> LAKE TOBA',
        'LA' => 'ANIMAL PRINT --> BATIK FLOWER',
        'LB' => 'ANIMAL PRINT --> MEKKAH',
        'LC' => 'ANIMAL PRINT --> PEACOCK',
        'LD' => 'ANIMAL PRINT --> ITALY',
        'LE' => 'ANIMAL PRINT --> SANTORINI',
        'LF' => 'ANIMAL PRINT --> MIKI',
        'LG' => 'ANIMAL PRINT --> ZOO KIDS',
        'LH' => 'ANIMAL PRINT --> GREEN FOREST',
        'LI' => 'ANIMAL PRINT --> PRINCESS',
        'LJ' => 'ANIMAL PRINT --> BALLERINA',
        'LK' => 'ANIMAL PRINT --> CATTY',
        'LL' => 'ANIMAL PRINT --> AVNGR',
        'LM' => 'ANIMAL PRINT --> EMON & FRIENDS',
        'LN' => 'ANIMAL PRINT --> RATTAN',
        'LO' => 'ANIMAL PRINT --> DINOSAUR',
        'LP' => 'ANIMAL PRINT --> ONYX',
        'LQ' => 'ANIMAL PRINT --> HIGHLAND',
        'LR' => 'ANIMAL PRINT --> SOCCER',
        'LS' => 'ANIMAL PRINT --> LAKE TOBA',
        'LT' => 'ANIMAL PRINT --> SUNGKAI',
        'M1' => 'PEACOCK --> KITTY',
        'M2' => 'PEACOCK --> SNOW',
        'M3' => 'PEACOCK --> MOSQUE',
        'M4' => 'PEACOCK --> AVENGER',
        'M5' => 'PEACOCK --> BARBIE',
        'M6' => 'PEACOCK --> CARS',
        'M7' => 'PEACOCK --> PANORAMA',
        'M8' => 'PEACOCK --> DORAEMON',
        'M9' => 'PEACOCK --> LAKE TOBA',
        'MA' => 'PEACOCK --> BATIK FLOWER',
        'MB' => 'PEACOCK --> ANIMAL PRINT',
        'MC' => 'PEACOCK --> MEKKAH',
        'MD' => 'PEACOCK --> ITALY',
        'ME' => 'PEACOCK --> SANTORINI',
        'MF' => 'PEACOCK --> MIKI',
        'MG' => 'PEACOCK --> ZOO KIDS',
        'MH' => 'PEACOCK --> GREEN FOREST',
        'MI' => 'PEACOCK --> PRINCESS',
        'N1' => 'ITALY --> KITTY',
        'N2' => 'ITALY --> SNOW',
        'N3' => 'ITALY --> MOSQUE',
        'N4' => 'ITALY --> AVENGER',
        'N5' => 'ITALY --> BARBIE',
        'N6' => 'ITALY --> CARS',
        'N7' => 'ITALY --> PANORAMA',
        'N8' => 'ITALY --> DORAEMON',
        'N9' => 'ITALY --> LAKE TOBA',
        'NA' => 'ITALY --> BATIK FLOWER',
        'NB' => 'ITALY --> ANIMAL PRINT',
        'NC' => 'ITALY --> MEKKAH',
        'ND' => 'ITALY --> PEACOCK',
        'NE' => 'ITALY --> SANTORINI',
        'NF' => 'ITALY --> MIKI',
        'NG' => 'ITALY --> ZOO KIDS',
        'NH' => 'ITALY --> GREEN FOREST',
        'NI' => 'ITALY --> PRINCESS',
        'O1' => 'MIKI --> KITTY',
        'O2' => 'MIKI --> SNOW',
        'O3' => 'MIKI --> MOSQUE',
        'O4' => 'MIKI --> AVENGER',
        'O5' => 'MIKI --> BARBIE',
        'O6' => 'MIKI --> CARS',
        'O7' => 'MIKI --> PANORAMA',
        'O8' => 'MIKI --> DORAEMON',
        'O9' => 'MIKI --> LAKE TOBA',
        'OA' => 'MIKI --> BATIK FLOWER',
        'OB' => 'MIKI --> ANIMAL PRINT',
        'OC' => 'MIKI --> MEKKAH',
        'OD' => 'MIKI --> PEACOCK',
        'OE' => 'MIKI --> SANTORINI',
        'OF' => 'MIKI --> ITALY',
        'OG' => 'MIKI --> ZOO KIDS',
        'OH' => 'MIKI --> GREEN FOREST',
        'OI' => 'MIKI --> PRINCESS',
        'P1' => 'SANTORINI --> KITTY',
        'P2' => 'SANTORINI --> SNOW',
        'P3' => 'SANTORINI --> MOSQUE',
        'P4' => 'SANTORINI --> AVENGER',
        'P5' => 'SANTORINI --> BARBIE',
        'P6' => 'SANTORINI --> CARS',
        'P7' => 'SANTORINI --> PANORAMA',
        'P8' => 'SANTORINI --> DORAEMON',
        'P9' => 'SANTORINI --> LAKE TOBA',
        'PA' => 'SANTORINI --> BATIK FLOWER',
        'PB' => 'SANTORINI --> ANIMAL PRINT',
        'PC' => 'SANTORINI --> MEKKAH',
        'PD' => 'SANTORINI --> PEACOCK',
        'PE' => 'SANTORINI --> ITALY',
        'PF' => 'SANTORINI --> MIKI',
        'PG' => 'SANTORINI --> ZOO KIDS',
        'PH' => 'SANTORINI --> GREEN FOREST',
        'PI' => 'SANTORINI --> PRINCESS',
        'Q1' => 'ZOO KIDS --> KITTY',
        'Q2' => 'ZOO KIDS --> SNOW',
        'Q3' => 'ZOO KIDS --> MOSQUE',
        'Q4' => 'ZOO KIDS --> AVENGER',
        'Q5' => 'ZOO KIDS --> BARBIE',
        'Q6' => 'ZOO KIDS --> CARS',
        'Q7' => 'ZOO KIDS --> PANORAMA',
        'Q8' => 'ZOO KIDS --> DORAEMON',
        'Q9' => 'ZOO KIDS --> LAKE TOBA',
        'QA' => 'ZOO KIDS --> BATIK FLOWER',
        'QB' => 'ZOO KIDS --> ANIMAL PRINT',
        'QC' => 'ZOO KIDS --> MEKKAH',
        'QD' => 'ZOO KIDS --> PEACOCK',
        'QE' => 'ZOO KIDS --> ITALY',
        'QF' => 'ZOO KIDS --> MIKI',
        'QG' => 'ZOO KIDS --> SANTORINI',
        'QH' => 'ZOO KIDS --> GREEN FOREST',
        'QI' => 'ZOO KIDS --> PRINCESS',
        'R1' => 'GREEN FOREST --> KITTY',
        'R2' => 'GREEN FOREST --> SNOW',
        'R3' => 'GREEN FOREST --> MOSQUE',
        'R4' => 'GREEN FOREST --> AVENGER',
        'R5' => 'GREEN FOREST --> BARBIE',
        'R6' => 'GREEN FOREST --> CARS',
        'R7' => 'GREEN FOREST --> PANORAMA',
        'R8' => 'GREEN FOREST --> DORAEMON',
        'R9' => 'GREEN FOREST --> LAKE TOBA',
        'RA' => 'GREEN FOREST --> BATIK FLOWER',
        'RB' => 'GREEN FOREST --> ANIMAL PRINT',
        'RC' => 'GREEN FOREST --> MEKKAH',
        'RD' => 'GREEN FOREST --> PEACOCK',
        'RE' => 'GREEN FOREST --> ITALY',
        'RF' => 'GREEN FOREST --> MIKI',
        'RG' => 'GREEN FOREST --> ZOO KIDS',
        'RH' => 'GREEN FOREST --> SANTORINI',
        'RI' => 'GREEN FOREST --> PRINCESS',
        'S1' => 'PRINCESS --> KITTY',
        'S2' => 'PRINCESS --> SNOW',
        'S3' => 'PRINCESS --> MOSQUE',
        'S4' => 'PRINCESS --> AVENGER',
        'S5' => 'PRINCESS --> BARBIE',
        'S6' => 'PRINCESS --> CARS',
        'S7' => 'PRINCESS --> PANORAMA',
        'S8' => 'PRINCESS --> DORAEMON',
        'S9' => 'PRINCESS --> LAKE TOBA',
        'SA' => 'PRINCESS --> BATIK FLOWER',
        'SB' => 'PRINCESS --> ANIMAL PRINT',
        'SC' => 'PRINCESS --> MEKKAH',
        'SD' => 'PRINCESS --> PEACOCK',
        'SE' => 'PRINCESS --> ITALY',
        'SF' => 'PRINCESS --> MIKI',
        'SG' => 'PRINCESS --> ZOO KIDS',
        'SH' => 'PRINCESS --> SANTORINI',
        'SI' => 'PRINCESS --> GREEN FOREST',
        'T1' => 'BALLERINA --> KITTY',
        'T2' => 'BALLERINA --> SNOW',
        'T3' => 'BALLERINA --> MOSQUE',
        'T4' => 'BALLERINA --> AVENGER',
        'T5' => 'BALLERINA --> BARBIE',
        'T6' => 'BALLERINA --> CARS',
        'T7' => 'BALLERINA --> PANORAMA',
        'T8' => 'BALLERINA --> DORAEMON',
        'T9' => 'BALLERINA --> LAKE TOBA',
        'TA' => 'BALLERINA --> BATIK FLOWER',
        'TB' => 'BALLERINA --> ANIMAL PRINT',
        'TC' => 'BALLERINA --> MEKKAH',
        'TD' => 'BALLERINA --> PEACOCK',
        'TE' => 'BALLERINA --> ITALY',
        'TF' => 'BALLERINA --> MIKI',
        'TG' => 'BALLERINA --> ZOO KIDS',
        'TH' => 'BALLERINA --> SANTORINI',
        'TI' => 'BALLERINA --> GREEN FOREST',
        'UA' => 'MARBLE GREY --> BIRDS',
        'UB' => 'MARBLE GREY --> PANORAMA',
        'UC' => 'MARBLE GREY --> ZOO KIDS',
        'UD' => 'MARBLE GREY --> CATTY',
        'UE' => 'MARBLE GREY --> MOSQUE',
        'UF' => 'MARBLE GREY --> BARBIE',
        'UG' => 'MARBLE GREY --> WHALE BOY',
        'UH' => 'MARBLE GREY --> EMON & FRIENDS',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        $period = self::resolvePeriod($filters)
            ?? self::resolvePeriodFromRows($allRows);

        if ($period !== null) {
            $p = $period;
            $allRows = array_values(array_filter($allRows, static function (array $row) use ($p): bool {
                $date = $row['adjustment_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        $allRows = $this->applyRecordSelection($allRows);

        $allRows = $this->computeFormulas($allRows);
        $allRows = $this->sortRows($allRows);

        $groups = $this->buildGroups($allRows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'groups' => $groups,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'cb') {
                continue;
            }

            $recordXml = $reader->readOuterXML();

            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($node === false) {
                continue;
            }

            $adjustmentDate = self::parseDate((string) ($node->Adjustment_x0020_Date ?? ''));

            if ($adjustmentDate === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rows[] = [
                'adjustment_date' => $adjustmentDate,
                'adjustment_date_sort' => $adjustmentDate->format('Y-m-d'),
                'adjustment_date_display' => self::formatDate($adjustmentDate),
                'adjustment_type' => trim((string) ($node->Adjustment_x0020_Type ?? '')),
                'memo_number' => trim((string) ($node->Memo_x0020_Number ?? '')),
                'memo_remarks' => trim((string) ($node->Memo_x0020_Remarks ?? '')),
                'item_code' => trim((string) ($node->Item_x0020_Code ?? '')),
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'item_full' => trim((string) ($node->Item ?? '')),
                'item_remarks' => trim((string) ($node->Item_x0020_Remarks ?? '')),
                'quantity' => (float) ($node->Quantity ?? 0),
                'uom' => trim((string) ($node->UOM ?? '')),
                'adjusted_value' => (float) ($node->Adjusted_x0020_Value ?? 0),
            ];
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('Data XML tidak ditemukan.');
        }

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function applyRecordSelection(array $rows): array
    {
        $filtered = [];

        foreach ($rows as $row) {
            $typeLemari = self::computeTypeLemari($row);
            if ($typeLemari !== 'TAMPIL') {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private static function computeTypeLemari(array $row): string
    {
        $name = $row['item_name'] ?? '';

        if (str_contains($name, 'PLASTIK KABINET PK')) {
            return 'TAMPIL';
        }

        return 'TIDAK';
    }

    private function computeFormulas(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $row['pintu'] = self::computePintu($row);
            $row['temp_alm'] = self::computeTempAlm($row);
            $row['name_group'] = self::computeNameGroup($row);
            $row['jelas'] = self::computeJelas($row);
            $row['split_toleng'] = self::computeSplitToleng($row);
            $row['name_pk'] = self::computeNamePk($row);
            $result[] = $row;
        }

        return $result;
    }

    private static function computePintu(array $row): string
    {
        $name = $row['item_name'] ?? '';

        if (str_contains($name, '3Tx6P')) {
            return '3TX6P';
        }

        if (str_contains($name, '4Tx8P')) {
            return '4TX8P';
        }

        if (str_contains($name, '4Tx4P')) {
            return '4TX4P';
        }

        return 'NULL';
    }

    private static function computeTempAlm(array $row): string
    {
        $remarks = $row['memo_remarks'] ?? '';

        foreach (self::TEMP_ALM_MAP as $pattern => $code) {
            if (str_contains($remarks, $pattern)) {
                return $code;
            }
        }

        return 'Tidak Tergroup';
    }

    private static function computeNameGroup(array $row): string
    {
        $code = $row['temp_alm'] ?? '';

        if (isset(self::NAME_GROUP_MAP[$code])) {
            return self::NAME_GROUP_MAP[$code];
        }

        if ($code === 'Tidak Tergroup') {
            return 'Tidak Tergroup (Cek Tulisan Remarks)';
        }

        return 'Tidak Tergroup (Cek Tulisan Remarks)';
    }

    private static function computeJelas(array $row): string
    {
        $ng = $row['name_group'] ?? '';

        if (str_contains($ng, 'Tidak Terg')) {
            return 'AA';
        }

        return '00';
    }

    private static function computeSplitToleng(array $row): int
    {
        $name = $row['item_name'] ?? '';
        $pos = strpos($name, 'LP');

        if ($pos === false) {
            $nameFull = $row['item_full'] ?? '';
            $pos = strpos($nameFull, 'LP');
        }

        if ($pos === false) {
            return -1;
        }

        return $pos;
    }

    private static function computeNamePk(array $row): string
    {
        $name = $row['item_name'] ?? '';
        $splitToleng = $row['split_toleng'] ?? -1;

        if ($splitToleng <= 10) {
            return $name;
        }

        $length = $splitToleng - 10;
        $result = substr($name, 9, $length);

        return $result !== false ? trim($result) : $name;
    }

    private function sortRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $cmp = $a['pintu'] <=> $b['pintu'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = ($a['name_group'] ?? '') <=> ($b['name_group'] ?? '');
            if ($cmp !== 0) {
                return $cmp;
            }

            return $a['adjustment_date_sort'] <=> $b['adjustment_date_sort'];
        });

        return $rows;
    }

    private function buildGroups(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $pintu = $row['pintu'];
            $nameGroup = $row['name_group'];
            $grouped[$pintu][$nameGroup][] = $row;
        }

        $pintuGroups = [];

        foreach ($grouped as $pintu => $nameGroups) {
            $ngList = [];
            $totalSelisihPintu = 0;

            ksort($nameGroups);

            foreach ($nameGroups as $nameGroup => $records) {
                $pairs = [];
                $pairTotal = 0;
                $recordCount = count($records);

                for ($i = 0; $i < $recordCount; $i += 2) {
                    $masukRecord = $records[$i];
                    $keluarRecord = $records[$i + 1] ?? null;

                    $masukValue = (float) ($masukRecord['adjusted_value'] ?? 0);
                    $keluarAbs = $keluarRecord !== null
                        ? abs((float) ($keluarRecord['adjusted_value'] ?? 0))
                        : 0.0;

                    $selisih = $masukValue - $keluarAbs;

                    $pairs[] = [
                        'nama_barang' => $masukRecord['name_pk'] ?? $masukRecord['item_name'] ?? '',
                        'unit' => $masukRecord['uom'] ?? '',
                        'masuk' => $masukValue,
                        'keluar' => $keluarRecord !== null
                            ? (float) ($keluarRecord['adjusted_value'] ?? 0)
                            : 0.0,
                        'selisih' => $selisih,
                    ];

                    $pairTotal += $selisih;
                }

                $ngList[] = [
                    'name_group' => $nameGroup,
                    'jelas' => $records[0]['jelas'] ?? '00',
                    'pairs' => $pairs,
                    'subtotal_selisih' => $pairTotal,
                ];

                $totalSelisihPintu += $pairTotal;
            }

            $pintuGroups[] = [
                'pintu' => $pintu,
                'name_groups' => $ngList,
                'total_selisih' => $totalSelisihPintu,
            ];
        }

        $grandTotal = 0;
        foreach ($pintuGroups as $pg) {
            $grandTotal += $pg['total_selisih'];
        }

        return [
            'pintu_groups' => $pintuGroups,
            'grand_total' => $grandTotal,
        ];
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->translatedFormat('d-M-y');
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private static function resolvePeriod(array $filters): ?array
    {
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['AdjustmentDate.StartDate'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['AdjustmentDate.EndDate'] ?? ''));

        if ($start === null && $end === null) {
            return null;
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();

        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    private static function resolvePeriodFromRows(array $rows): ?array
    {
        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?Carbon => $row['adjustment_date'] ?? null,
            $rows,
        )));

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    private static function resolvePrintedBy(\SimpleXMLElement $node): string
    {
        $candidateKeys = [
            'Nama_x0020_User',
            'User_x0020_Name',
            'Printed_x0020_By',
            'Created_x0020_By',
        ];

        foreach ($candidateKeys as $key) {
            $value = trim((string) ($node->$key ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
