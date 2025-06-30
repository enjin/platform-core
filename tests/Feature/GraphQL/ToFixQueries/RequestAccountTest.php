<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\ToFixQueries;

use Enjin\Platform\Facades\Qr;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Override;

class RequestAccountTest extends TestCaseGraphQL
{
    protected string $method = 'RequestAccount';
    protected string $callback;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->callback = fake()->url();
    }

    public function test_can_request_an_address(): void
    {
        $response = $this->graphql($this->method);

        $link = Qr::url(config('enjin-platform.deep_links.proof'));
        $encodedString = base64_decode(str_replace($link, '', $qrCode = $response['qrCode']));
        $verificationCode = explode(':', $encodedString)[1];

        $this->assertNotEmpty($qrCode);
        $this->assertNotEmpty($verificationId = $response['verificationId']);
        $this->assertDatabaseHas('verifications', [
            'code' => $verificationCode,
            'verification_id' => $verificationId,
            'public_key' => null,
        ]);
    }

    public function test_is_valid_wallet_deep_link(): void
    {
        $response = $this->graphql($this->method);

        $this->assertNotEmpty($qrCode = $response['qrCode']);
        $this->assertStringContainsString(
            config('enjin-platform.deep_links.proof'),
            $qrCode
        );
    }
}
