<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionUpdated;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class UpdateTransactionTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use HasHttp;

    protected Model $transaction;
    protected string $method = 'UpdateTransaction';

    protected function setUp(): void
    {
        parent::setUp();

        $this->transaction = Transaction::factory([
            'transaction_chain_id' => null,
            'transaction_chain_hash' => null,
            'signed_at_block' => null,
        ])->create();
    }

    // Happy Path
    public function test_it_can_update_only_transaction_state_without_auth(): void
    {
        $response = $this->httpGraphql(
            $this->method,
            [
                'variables' => [
                    'id' => $this->transaction->id,
                    'state' => $state = fake()->randomElement(array_diff(
                        TransactionState::caseNamesAsArray(),
                        [TransactionState::PENDING->name],
                    )),
                ],
            ]
        );

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $this->transaction->transaction_chain_id,
            'transaction_chain_hash' => $this->transaction->transaction_chain_hash,
            'state' => $state,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $this->transaction->signed_at_block,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_only_transaction_state(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'state' => $state = fake()->randomElement(array_diff(
                TransactionState::caseNamesAsArray(),
                [TransactionState::PENDING->name],
            )),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $this->transaction->transaction_chain_id,
            'transaction_chain_hash' => $this->transaction->transaction_chain_hash,
            'state' => $state,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $this->transaction->signed_at_block,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_transaction_id(): void
    {
        Transaction::where('transaction_chain_id', '=', $transactionId = fake()->numerify('######-#'))?->delete();

        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionId' => $transactionId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $transactionId,
            'transaction_chain_hash' => $this->transaction->transaction_chain_hash,
            'state' => $this->transaction->state,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $this->transaction->signed_at_block,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_with_ss58_account(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'signingAccount' => $account = app(Generator::class)->chain_address(),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $this->transaction->transaction_chain_id,
            'transaction_chain_hash' => $this->transaction->transaction_chain_hash,
            'state' => $this->transaction->state,
            'wallet_public_key' => SS58Address::getPublicKey($account),
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $this->transaction->signed_at_block,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_with_public_key_account(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'signingAccount' => $account = app(Generator::class)->public_key(),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $this->transaction->transaction_chain_id,
            'transaction_chain_hash' => $this->transaction->transaction_chain_hash,
            'state' => $this->transaction->state,
            'wallet_public_key' => $account,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $this->transaction->signed_at_block,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionHash' => $transactionHash = HexConverter::prefix(fake()->sha256()),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $this->transaction->transaction_chain_id,
            'transaction_chain_hash' => $transactionHash,
            'state' => $this->transaction->state,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $this->transaction->signed_at_block,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_signed_at_block(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'signedAtBlock' => $signedAtBlock = fake()->numberBetween(),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $this->transaction->transaction_chain_id,
            'transaction_chain_hash' => $this->transaction->transaction_chain_hash,
            'state' => $this->transaction->state,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $signedAtBlock,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    public function test_it_can_update_all_four(): void
    {
        Transaction::where('transaction_chain_id', '=', $transactionId = fake()->numerify('######-#'))?->delete();

        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'state' => $state = fake()->randomElement(TransactionState::caseNamesAsArray()),
            'transactionId' => $transactionId,
            'transactionHash' => $transactionHash = HexConverter::prefix(fake()->sha256()),
            'signedAtBlock' => $signedAtBlock = fake()->numberBetween(),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'transaction_chain_id' => $transactionId,
            'transaction_chain_hash' => $transactionHash,
            'state' => $state,
            'encoded_data' => $this->transaction->encoded_data,
            'signed_at_block' => $signedAtBlock,
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    // Exception Path

    public function test_it_will_fail_with_id_doesnt_exists(): void
    {
        Transaction::where('id', '=', $id = fake()->randomDigit())?->delete();

        $response = $this->graphql($this->method, [
            'id' => $id,
            'state' => fake()->randomElement(TransactionState::caseNamesAsArray()),
            'transactionId' => fake()->numerify('######-#'),
            'transactionHash' => HexConverter::prefix(fake()->sha256()),
            'signedAtBlock' => fake()->numberBetween(),
        ], true);

        $this->assertStringContainsString(
            'Transaction not found.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_invalid_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => 'not_valid',
            'state' => fake()->randomElement(TransactionState::caseNamesAsArray()),
            'transactionId' => fake()->numerify('######-#'),
            'transactionHash' => HexConverter::prefix(fake()->sha256()),
            'signedAtBlock' => fake()->numberBetween(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$id" got invalid value "not_valid"; Int cannot represent non-integer value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_invalid_account(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'signingAccount' => 'not_valid',
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_invalid_state(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'state' => 'not_valid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$state" got invalid value "not_valid"; Value "not_valid" does not exist in "TransactionState" enum',
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_invalid_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionId' => 'not_valid',
        ], true);

        $this->assertArraySubset(
            ['transactionId' => ['The transaction id has a not valid substrate transaction ID.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_invalid_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionHash' => 'not_valid',
        ], true);

        $this->assertArraySubset(
            ['transactionHash' => ['The transaction hash has an invalid hex string.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_no_prefix_on_hash(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionHash' => fake()->sha256(),
        ], true);

        $this->assertArraySubset(
            ['transactionHash' => ['The transaction hash has an invalid hex string.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_invalid_signed_at_block(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'signedAtBlock' => 'not_valid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$signedAtBlock" got invalid value "not_valid"; Int cannot represent non-integer value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_with_no_fields(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
        ], true);

        $this->assertArraySubset([
            'state' => ['The state field is required when none of transaction id / transaction hash / signed at block / signing account are present.'],
            'transactionId' => [ 'The transaction id field is required when none of state / transaction hash / signed at block / signing account are present.'],
            'transactionHash' => ['The transaction hash field is required when none of state / transaction id / signed at block / signing account are present.'],
            'signingAccount' => ['The signing account field is required when none of state / transaction id / transaction hash / signed at block are present.'],
            'signedAtBlock' => ['The signed at block field is required when none of state / transaction id / transaction hash / signing account are present.'],
        ], $response['error']);

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_trying_to_set_a_transaction_id_that_was_already_set(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->graphql($this->method, [
            'id' => $transaction->id,
            'transactionId' => $transaction->transaction_chain_id,
        ], true);

        $this->assertArraySubset([
            'transactionId' => ['The transaction id and hash are immutable once set.'],
        ], $response['error']);

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_will_fail_trying_to_set_a_transaction_hash_that_was_already_set(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->graphql($this->method, [
            'id' => $transaction->id,
            'transactionHash' => $transaction->transaction_chain_hash,
        ], true);

        $this->assertArraySubset([
            'transactionHash' => ['The transaction id and hash are immutable once set.'],
        ], $response['error']);


        Event::assertNotDispatched(TransactionUpdated::class);
    }
}
