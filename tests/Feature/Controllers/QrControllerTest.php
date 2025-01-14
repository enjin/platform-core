<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

class QrControllerTest extends TestCaseGraphQL
{
    #[RequiresOperatingSystem('Linux')]
    public function test_it_can_get_a_qr_image(): void
    {
        $response = $this->get('/qr?s=128&f=png&m=1&d=test_data');
        $content = mb_convert_encoding($response->content(), 'UTF-8');

        $this->assertTrue($response->isOk());
        $this->assertSame(
            'b1f778e2a1c367807931020fbf2085de51f300ec',
            sha1($content)
        );
    }

    public function test_it_fails_to_get_a_qr_with_no_data(): void
    {
        $response = $this->get('/qr?s=128&f=svg');

        $this->assertTrue($response->isServerError());
        $this->assertEquals(__('enjin-platform::error.qr.data_must_not_be_empty'), $response->exception->getMessage());
    }

    public function test_it_fails_to_get_a_qr_with_no_invalid_filetype(): void
    {
        $response = $this->get('/qr?s=128&f=jpg&d=test_data');

        $this->assertTrue($response->isServerError());
        $this->assertEquals(__('enjin-platform::error.qr.image_format_not_supported'), $response->exception->getMessage());
    }
}
