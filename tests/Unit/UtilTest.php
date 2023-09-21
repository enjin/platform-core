<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Support\Util;
use Enjin\Platform\Tests\TestCase;

class UtilTest extends TestCase
{
    public function test_it_can_verify_base64_string()
    {
        $testString = base64_encode('test_data');

        $this->assertTrue(Util::isBase64String($testString));
    }

    public function test_it_can_base64_string_fails_verification()
    {
        $testString = 'test_data';

        $this->assertFalse(Util::isBase64String($testString));
    }
}
