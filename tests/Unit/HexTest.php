<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\TestCase;

class HexTest extends TestCase
{
    public function test_it_can_detect_hex()
    {
        $this->assertTrue(Hex::isHexEncoded('1234567890abcdef'));
        $this->assertTrue(Hex::isHexEncoded('0x1234567890abcdef'));
        $this->assertFalse(Hex::isHexEncoded('test string'));
    }
}
