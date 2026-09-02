<?php

namespace Tests\Feature;

use App\Support\BcMathPolyfill;
use App\Support\DecimalDisplay;
use Tests\TestCase;

class DecimalMathTest extends TestCase
{
    public function test_quantity_display_normalizes_scale(): void
    {
        $this->assertSame('360', DecimalDisplay::quantity('360.000'));
        $this->assertSame('12.5', DecimalDisplay::quantity('12.500'));
        $this->assertSame('0', DecimalDisplay::quantity(null));
    }

    public function test_polyfill_matches_stock_scale_rules(): void
    {
        $this->assertSame('410.000', BcMathPolyfill::add('360', '50', 3));
        $this->assertSame('340.000', BcMathPolyfill::sub('360', '20', 3));
        $this->assertSame('1', (string) BcMathPolyfill::comp('410', '340', 3));
        $this->assertSame('5.000', BcMathPolyfill::div('10', '2', 3));
    }
}
