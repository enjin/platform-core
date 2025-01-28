<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

class QrControllerTest extends TestCaseGraphQL
{
    #[RequiresOperatingSystem('Linux')]
    public function test_it_can_get_a_qr_image(): void
    {
        $response = $this->get('/qr?s=32&f=svg&d=test_data');
        $content = sha1($response->content());

        $this->assertTrue($response->isOk());
        $this->assertSame(
            'cc1e0b93da576ebe68be132395f40847031e0f9e',
            $content
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
