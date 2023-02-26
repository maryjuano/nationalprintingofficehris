<?php

namespace App\Helpers;

abstract class IsGovernmentService
{
    const N = 0;
    const Y = 1;
    const DISPLAY = [
        self::N => 'N',
        self::Y => 'Y',
    ];
}