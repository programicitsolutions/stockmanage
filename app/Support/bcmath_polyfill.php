<?php

/**
 * Provide bcmath functions when the PHP extension is not installed.
 * Native ext-bcmath is used when present.
 */
if (! function_exists('bcadd')) {
    function bcadd(string $num1, string $num2, ?int $scale = null): string
    {
        return \App\Support\BcMathPolyfill::add($num1, $num2, $scale);
    }
}

if (! function_exists('bcsub')) {
    function bcsub(string $num1, string $num2, ?int $scale = null): string
    {
        return \App\Support\BcMathPolyfill::sub($num1, $num2, $scale);
    }
}

if (! function_exists('bcmul')) {
    function bcmul(string $num1, string $num2, ?int $scale = null): string
    {
        return \App\Support\BcMathPolyfill::mul($num1, $num2, $scale);
    }
}

if (! function_exists('bcdiv')) {
    function bcdiv(string $num1, string $num2, ?int $scale = null): string
    {
        return \App\Support\BcMathPolyfill::div($num1, $num2, $scale);
    }
}

if (! function_exists('bccomp')) {
    function bccomp(string $num1, string $num2, ?int $scale = null): int
    {
        return \App\Support\BcMathPolyfill::comp($num1, $num2, $scale);
    }
}
