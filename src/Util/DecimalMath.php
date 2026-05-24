<?php

namespace App\Util;

final class DecimalMath
{
    public static function mul(string $left, string $right, int $scale = 2): string
    {
        if (\function_exists('bcmul')) {
            return \bcmul($left, $right, $scale);
        }

        return number_format((float) $left * (float) $right, $scale, '.', '');
    }

    public static function add(string $left, string $right, int $scale = 2): string
    {
        if (\function_exists('bcadd')) {
            return \bcadd($left, $right, $scale);
        }

        return number_format((float) $left + (float) $right, $scale, '.', '');
    }
}
