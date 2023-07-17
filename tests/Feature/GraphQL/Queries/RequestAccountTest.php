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
        $response = $this->graphql($this->method, [
            'callback' => $this->callback,
        ]);

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
        $response = $this->graphql($this->method, [
            'callback' => $this->callback,
        ]);

        $this->assertNotEmpty($qrCode = $response['qrCode']);
        $this->assertTrue(
            base64_decode(explode(':', $qrCode)[4]) === $this->callback
        );
    }

    public function test_is_valid_wallet_deep_link(): void
    {
        $response = $this->graphql($this->method, [
            'callback' => $this->callback,
        ]);

        $this->assertNotEmpty($qrCode = $response['qrCode']);
        $this->assertStringContainsString(
            config('enjin-platform.deep_links.proof'),
            $qrCode
        );
    }

    public function test_it_will_fail_with_no_callback(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$callback" of required type "String!" was not provided.',
            $response['error'],
        );
    }

    // Exception Path

    public function test_it_will_fail_with_null_callback(): void
    {
        $response = $this->graphql($this->method, [
            'callback' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$callback" of non-null type "String!" must not be null.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_callback(): void
    {
        $response = $this->graphql($this->method, [
            'callback' => '',
        ], true);

        $this->assertArraySubset(
            ['callback' => ['The callback field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_callback(): void
    {
        $response = $this->graphql($this->method, [
            'callback' => 'not_a_valid_url',
        ], true);

        $this->assertArraySubset(
            ['callback' => ['The callback field must be a valid URL.']],
            $response['error'],
        );
    }
}
