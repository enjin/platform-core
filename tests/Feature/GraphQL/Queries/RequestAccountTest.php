<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class RequestAccountTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'RequestAccount';
    protected string $callback;

    protected function setUp(): void
    {
        parent::setUp();
        $this->callback = fake()->url();
    }

    public function test_can_request_an_address(): void
    {
        $response = $this->graphql($this->method);

        $this->assertNotEmpty($qrCode = $response['qrCode']);
        $this->assertNotEmpty($verificationId = $response['verificationId']);
        $this->assertDatabaseHas('verifications', [
            'code' => explode(':', base64_decode(explode(':', $qrCode)[3]))[1],
            'verification_id' => $verificationId,
            'public_key' => null,
        ]);
    }

    public function test_callback_is_embedded_in_qr_code(): void
    {
        $response = $this->graphql($this->method);

        $this->assertNotEmpty($qrCode = $response['qrCode']);
        $this->assertTrue(
            base64_decode(explode(':', $qrCode)[4]) === $this->callback
        );
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
