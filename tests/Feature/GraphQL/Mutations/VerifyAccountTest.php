<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;

class VerifyAccountTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use HasHttp;

    protected string $method = 'VerifyAccount';
    protected Model $verification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verification = Verification::factory()->create();
    }

    // Happy Path

    public function test_it_can_verify_without_auth(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);
        $response = $this->httpGraphql(
            $this->method,
            [
                'variables' => [
                    'verificationId' => $this->verification->verification_id,
                    'signature' => $data['signature'],
                    'account' => $data['address'],
                    'cryptoSignatureType' => 'SR25519',
                ],
            ]
        );

        $this->assertTrue($response);
        $this->assertDatabaseHas('verifications', [
            'verification_id' => $this->verification->verification_id,
            'code' => $this->verification->code,
            'public_key' => $data['publicKey'],
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $data['publicKey'],
            'verification_id' => $this->verification->verification_id,
        ]);
    }

    public function test_it_can_verify_labelled_mutation_without_auth(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);
        $response = $this->httpGraphql(
            $this->method . 'WithLabel',
            [
                'variables' => [
                    'verificationId' => $this->verification->verification_id,
                    'signature' => $data['signature'],
                    'account' => $data['address'],
                    'cryptoSignatureType' => 'SR25519',
                ],
            ]
        );

        $this->assertTrue($response);
        $this->assertDatabaseHas('verifications', [
            'verification_id' => $this->verification->verification_id,
            'code' => $this->verification->code,
            'public_key' => $data['publicKey'],
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $data['publicKey'],
            'verification_id' => $this->verification->verification_id,
        ]);
    }

    public function test_it_can_verify(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => $data['address'],
            'cryptoSignatureType' => 'SR25519',
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('verifications', [
            'verification_id' => $this->verification->verification_id,
            'code' => $this->verification->code,
            'public_key' => $data['publicKey'],
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $data['publicKey'],
            'verification_id' => $this->verification->verification_id,
        ]);
    }

    public function test_it_can_verify_with_sr25519(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => $data['address'],
            'cryptoSignatureType' => 'SR25519',
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('verifications', [
            'verification_id' => $this->verification->verification_id,
            'code' => $this->verification->code,
            'public_key' => $data['publicKey'],
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $data['publicKey'],
            'verification_id' => $this->verification->verification_id,
        ]);
    }

    public function test_it_can_verify_with_ed25519(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => $data['address'],
            'cryptoSignatureType' => 'ED25519',
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('verifications', [
            'verification_id' => $this->verification->verification_id,
            'code' => $this->verification->code,
            'public_key' => $data['publicKey'],
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $data['publicKey'],
            'verification_id' => $this->verification->verification_id,
        ]);
    }

    public function test_it_can_verify_flow(): void
    {
        $request = $this->requestAccount();
        $this->verifyAccount($request['verificationId'], $request['verificationCode']);
    }

    public function test_it_can_verify_flow_multiple_times(): void
    {
        $keypair = sodium_crypto_sign_keypair();

        $request = $this->requestAccount();
        $this->verifyAccount($request['verificationId'], $request['verificationCode'], keypair: $keypair);

        $request = $this->requestAccount();
        $this->verifyAccount($request['verificationId'], $request['verificationCode'], keypair: $keypair);
    }

    // Exception Path

    public function test_it_will_fail_with_verification_id_that_doesnt_exists(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => fake()->uuid(),
            'signature' => $data['signature'],
            'account' => $data['address'],
        ], true);

        $this->assertArraySubset(
            ['verificationId' => ['The selected verification id is invalid.']],
            $response['error']
        );
    }

    public function test_it_will_fail_using_wrong_address_for_sr25519(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'The signature provided is not valid.',
            $response['error']
        );
    }

    public function test_it_will_fail_using_wrong_address_for_ed25519(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => app(Generator::class)->public_key(),
            'cryptoSignatureType' => 'ED25519',
        ], true);

        $this->assertStringContainsString(
            'The signature provided is not valid.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_wrong_sr25519_signature(): void
    {
        $data = app(Generator::class)->sr25519_signature(fake()->word(), isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => $data['address'],
        ], true);

        $this->assertStringContainsString(
            'The signature provided is not valid.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_wrong_ed25519_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature(fake()->word(), isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => $data['address'],
            'cryptoSignatureType' => 'ED25519',
        ], true);

        $this->assertStringContainsString(
            'The signature provided is not valid.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_verification_id(): void
    {
        $data = app(Generator::class)->sr25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => '',
            'signature' => $data['signature'],
            'account' => $data['address'],
        ], true);

        $this->assertArraySubset(
            [
                'verificationId' => [
                    0 => 'The verification id field must have a value.',
                ],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_no_verification_id(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'signature' => $data['signature'],
            'account' => $data['address'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$verificationId" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_null_verification_id(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => null,
            'signature' => $data['signature'],
            'account' => $data['address'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$verificationId" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => '',
            'account' => $data['address'],
        ], true);

        $this->assertArraySubset(
            [
                'signature' => [
                    0 => 'The signature field must have a value.',
                ],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_no_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'account' => $data['address'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$signature" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_null_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => null,
            'public_key' => $data['address'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$signature" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => 'invalid',
            'account' => $data['address'],
        ], true);

        $this->assertArraySubset(
            [
                'signature' => [
                    0 => 'The signature has an invalid hex string.',
                ],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_no_address(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$account" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_address(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => '',
        ], true);

        $this->assertArraySubset(
            [
                'account' => [
                    0 => 'The account field must have a value.',
                ],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_null_address(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$account" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_invalid_address(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => $data['signature'],
            'account' => 'not_valid',
        ], true);

        $this->assertArraySubset(
            ['account' => ['The account is not a valid substrate account.']],
            $response['error']
        );
    }

    public function test_it_will_fail_without_prefix_signature(): void
    {
        $data = app(Generator::class)->ed25519_signature($this->verification->code, isCode: true);

        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'signature' => HexConverter::unPrefix($data['signature']),
            'account' => $data['address'],
        ], true);

        $this->assertArraySubset(
            ['signature' => ['The signature has an invalid hex string.']],
            $response['error']
        );
    }

    protected function requestAccount(): array
    {
        $response = $this->graphql('RequestAccount', [
            'callback' => fake()->url(),
        ]);

        $this->assertNotEmpty($verificationId = $response['verificationId']);
        $this->assertNotEmpty($verificationCode = explode(':', $response['qrCode'])[3]);

        return [
            'verificationId' => $verificationId,
            'verificationCode' => $verificationCode,
        ];
    }

    protected function verifyAccount(
        string $verificationId,
        string $verificationCode,
        ?string $externalId = null,
        ?string $keypair = null
    ) {
        $data = app(Generator::class)->ed25519_signature($verificationCode, isCode: true);

        if ($keypair) {
            $publicKey = sodium_crypto_sign_publickey($keypair);
            $signature = app(Generator::class)->signWithCode($verificationCode, $keypair);
            $address = SS58Address::encode(HexConverter::hexToBytes(bin2hex($publicKey)));

            $data = [
                'publicKey' => SS58Address::getPublicKey($address),
                'signature' => $signature,
            ];
        }

        $response = $this->graphql($this->method, [
            'verificationId' => $verificationId,
            'signature' => $data['signature'],
            'account' => $data['publicKey'],
            'cryptoSignatureType' => 'ED25519',
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('verifications', [
            'verification_id' => $verificationId,
            'code' => $verificationCode,
            'public_key' => $data['publicKey'],
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $data['publicKey'],
            'verification_id' => $verificationId,
            'external_id' => $externalId,
        ]);
    }
}
