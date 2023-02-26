<?php

namespace App\Helpers;

abstract class DayFractions
{
    const SCALE = 10;
    const HOUR = [
        0.0000000000,
        0.1250000000,
        0.2500000000,
        0.3750000000,
        0.5000000000,
        0.6250000000,
        0.7500000000,
        0.8750000000,
        1.0000000000,
    ];

    const MIN = [
        0.0000000000,
        0.0020833333,
        0.0041666667,
        0.0062500000,
        0.0083333333,
        0.0104166667,
        0.0125000000,
        0.0145833333,
        0.0166666667,
        0.0187500000,
        0.0208333333,
        0.0229166667,
        0.0250000000,
        0.0270833333,
        0.0291666667,
        0.0312500000,
        0.0333333333,
        0.0354166667,
        0.0375000000,
        0.0395833333,
        0.0416666667,
        0.0437500000,
        0.0458333333,
        0.0479166667,
        0.0500000000,
        0.0520833333,
        0.0541666667,
        0.0562500000,
        0.0583333333,
        0.0604166667,
        0.0625000000,
        0.0645833333,
        0.0666666667,
        0.0687500000,
        0.0708333333,
        0.0729166667,
        0.0750000000,
        0.0770833333,
        0.0791666667,
        0.0812500000,
        0.0833333333,
        0.0854166667,
        0.0875000000,
        0.0895833333,
        0.0916666667,
        0.0937500000,
        0.0958333333,
        0.0979166667,
        0.1000000000,
        0.1020833333,
        0.1041666667,
        0.1062500000,
        0.1083333333,
        0.1104166667,
        0.1125000000,
        0.1145833333,
        0.1166666667,
        0.1187500000,
        0.1208333333,
        0.1229166667,
        0.1250000000,
    ];
}