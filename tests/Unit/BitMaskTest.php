<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Support\BitMask;
use Enjin\Platform\Tests\TestCase;

class BitMaskTest extends TestCase
{
    public function test_get_bit_is_set()
    {
        $this->assertTrue(BitMask::getBit(10, 1024));
    }

    public function test_get_bit_is_not_set()
    {
        $this->assertFalse(BitMask::getBit(0, 1024));
    }

    public function test_get_bits()
    {
        $bits = [0, 1, 3, 5, 9];

        $this->assertEquals($bits, BitMask::getBits(555));
    }

    public function test_set_bit()
    {
        $this->assertEquals(1025, BitMask::setBit(10, 1));
    }

    public function test_unset_bit()
    {
        $this->assertEquals(1, BitMask::unsetBit(10, 1025));
    }

    public function test_set_bits()
    {
        $bits = [0, 1, 3, 5, 9];

        $this->assertEquals(555, BitMask::setBits($bits, 0));
    }

    public function test_unset_bits()
    {
        $bits = [3, 5, 9];

        $this->assertEquals(3, BitMask::unsetBits($bits, 555));
    }

    public function test_toggle_bits()
    {
        $bits = [0, 1, 3, 5, 9, 10];

        $this->assertEquals(1024, BitMask::toggleBits($bits, 555));
    }
}
