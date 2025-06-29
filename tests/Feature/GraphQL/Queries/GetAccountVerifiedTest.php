<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Models\Verification;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;

class GetAccountVerifiedTest extends TestCaseGraphQL
{
    protected string $method = 'GetAccountVerified';

    protected Model $verification;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->verification = Verification::factory([
            'public_key' => app(Generator::class)->public_key(),
        ])->create();
    }

    public function test_it_can_get_a_verified_address_by_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => $verificationId = $this->verification->verification_id,
        ]);

        $this->assertArrayContainsArray([
            'verified' => true,
            'account' => [
                'publicKey' => $publicKey = $this->verification->public_key,
            ],
        ], $response);

        $this->assertDatabaseHas('verifications', [
            'verification_id' => $verificationId,
            'public_key' => $publicKey,
            'code' => $this->verification->code,
        ]);
    }

    public function test_it_can_get_by_verification_id_a_not_verified(): void
    {
        $verification = Verification::factory()->create();

        $response = $this->graphql($this->method, [
            'verificationId' => $verificationId = $verification->verification_id,
        ]);

        $this->assertArrayContainsArray([
            'verified' => false,
            'account' => [
                'publicKey' => null,
            ],
        ], $response);

        $this->assertDatabaseHas('verifications', [
            'verification_id' => $verificationId,
            'public_key' => null,
            'code' => $verification->code,
        ]);
    }

    public function test_it_returns_false_to_a_not_verified_address(): void
    {
        Verification::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'account' => SS58Address::encode($publicKey),
        ]);

        $this->assertArrayContainsArray([
            'verified' => false,
            'account' => [
                'publicKey' => null,
            ],
        ], $response);
    }

    public function test_it_returns_false_to_a_verification_id_that_could_be_valid_but_doesnt_exists(): void
    {
        $verification = Verification::factory()->create();
        $verificationId = $verification->verification_id;
        $verification->delete();

        $response = $this->graphql($this->method, [
            'verificationId' => $verificationId,
        ]);

        $this->assertArrayContainsArray([
            'verified' => false,
            'account' => [
                'publicKey' => null,
            ],
        ], $response);
    }

    public function test_it_can_get_a_verification_by_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => SS58Address::encode($publicKey = $this->verification->public_key),
        ]);

        $this->assertArrayContainsArray([
            'verified' => true,
            'account' => [
                'publicKey' => $publicKey,
            ],
        ], $response);

        $this->assertDatabaseHas('verifications', [
            'verification_id' => $this->verification->verification_id,
            'public_key' => $publicKey,
            'code' => $this->verification->code,
        ]);
    }

    public function test_it_can_verify_a_second_request_with_same_address(): void
    {
        $verification = Verification::factory([
            'public_key' => $publicKey = $this->verification->public_key,
        ])->create();

        $response = $this->graphql($this->method, [
            'account' => $publicKey,
        ]);

        $this->assertArrayContainsArray([
            'verified' => true,
            'account' => [
                'publicKey' => $publicKey,
            ],
        ], $response);

        $this->assertDatabaseHas('verifications', [
            'verification_id' => $verification->verification_id,
            'public_key' => $publicKey,
            'code' => $verification->code,
        ]);
    }

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertArrayContainsArray(
            [
                'verificationId' => ['The verification id field is required when account is not present.'],
                'account' => ['The account field is required when verification id is not present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_null_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => null,
        ], true);

        $this->assertArrayContainsArray(
            [
                'verificationId' => ['The verification id field is required when account is not present.'],
                'account' => ['The account field is required when verification id is not present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_null_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => null,
        ], true);

        $this->assertArrayContainsArray(
            [
                'verificationId' => ['The verification id field is required when account is not present.'],
                'account' => ['The account field is required when verification id is not present.'],
            ],
            $response['error']
        );
    }

    // Exception Path

    public function test_it_will_fail_with_empty_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => '',
        ], true);

        $this->assertArrayContainsArray(
            [
                'verificationId' => ['The verification id field is required when account is not present.'],
                'account' => ['The account field is required when verification id is not present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_empty_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => '',
        ], true);

        $this->assertArrayContainsArray(
            [
                'verificationId' => ['The verification id field is required when account is not present.'],
                'account' => ['The account field is required when verification id is not present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['account' => ['The account is not a valid substrate account.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['verificationId' => ['The verification ID is not valid.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_with_both_args(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => $this->verification->verification_id,
            'account' => $this->verification->public_key,
        ], true);

        $this->assertArrayContainsArray(
            [
                'verificationId' => ['The verification id field prohibits account from being present.'],
                'account' => ['The account field prohibits verification id from being present.'],
            ],
            $response['error']
        );
    }
}
