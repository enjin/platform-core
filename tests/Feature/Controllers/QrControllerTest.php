<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class QrControllerTest extends TestCaseGraphQL
{
    public function test_it_can_get_a_qr_image(): void
    {
        $response = $this->get('/qr?s=128&f=png&d=test_data');

        $this->assertTrue($response->isOk());
        $this->assertSame(
            'ccac6fd60b4f2e46cb00563d01b2e94ef8760e6a',
            sha1($response->content())
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
