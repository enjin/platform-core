<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Faker\Generator;

class VerifyMessageTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'VerifyMessage';
    protected string $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->message = HexConverter::stringToHexPrefixed(fake()->realText());
    }

    public function test_it_can_verify_a_message(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
        ]);

        $this->assertTrue($response);
    }

    public function test_it_can_verify_a_message_with_signature_type_sr25519(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
            'cryptoSignatureType' => 'SR25519',
        ]);

        $this->assertTrue($response);
    }

    public function test_it_can_verify_a_message_with_signature_type_ed25519(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
            'cryptoSignatureType' => 'ED25519',
        ]);

        $this->assertTrue($response);
    }

    public function test_it_will_return_false_with_invalid_sr25519_signature(): void
    {
        $otherMessage = HexConverter::stringToHexPrefixed(fake()->text());
        $data = app(Generator::class)->sr25519_signature($otherMessage);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
        ]);

        $this->assertFalse($response);
    }

    public function test_it_will_return_false_with_invalid_ed25519_signature(): void
    {
        $otherMessage = HexConverter::stringToHexPrefixed(fake()->text());
        $data = app(Generator::class)->ed25519_signature($otherMessage);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
            'cryptoSignatureType' => 'ED25519',
        ]);

        $this->assertFalse($response);
    }

    // Exception Path

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$message" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_no_message(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$message" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_no_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$signature" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_no_public_key(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$publicKey" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_message_null(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => null,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$message" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_signature_null(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => null,
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$signature" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_public_key_null(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$publicKey" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_message_not_hex(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => fake()->word(),
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertArraySubset(
            ['message' => ['The message has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_message_without_hex_prefix(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => HexConverter::unPrefix($this->message),
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertArraySubset(
            ['message' => ['The message has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_signature_not_hex(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => fake()->word(),
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertArraySubset(
            ['signature' => ['The signature has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_signature_without_hex_prefix(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => HexConverter::unPrefix($data['signature']),
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertArraySubset(
            ['signature' => ['The signature has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_public_key_for_sr25519(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => '0x01234',
        ], true);

        $this->assertArraySubset(
            ['publicKey' => ['The public key has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_public_key_for_ed25519(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => '0x01234',
        ], true);

        $this->assertArraySubset(
            ['publicKey' => ['The public key has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_public_key_without_hex_prefix(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => HexConverter::unPrefix($data['publicKey']),
        ], true);

        $this->assertArraySubset(
            ['publicKey' => ['The public key has an invalid hex string.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => '',
            'publicKey' => $data['publicKey'],
        ], true);

        $this->assertArraySubset(
            ['signature' => ['The signature field must have a value.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_public_key(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => '',
        ], true);

        $this->assertArraySubset(
            ['publicKey' => ['The public key field must have a value.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_crypto_signature_type(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
            'cryptoSignatureType' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$cryptoSignatureType" got invalid value "invalid"; Value "invalid" does not exist in "CryptoSignatureType" enum',
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_signature_type(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->message);

        $response = $this->graphql($this->method, [
            'message' => $this->message,
            'signature' => $data['signature'],
            'publicKey' => $data['publicKey'],
            'cryptoSignatureType' => '',
        ], true);

        $this->assertStringContainsString(
            'Variable "$cryptoSignatureType" got invalid value (empty string); Value "" does not exist in "CryptoSignatureType" enum',
            $response['error']
        );
    }
}
