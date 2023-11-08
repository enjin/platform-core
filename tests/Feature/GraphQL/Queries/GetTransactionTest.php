<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Substrate\SystemEventType;
use Enjin\Platform\Models\Event;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;

class GetTransactionTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'GetTransaction';
    protected string $defaultAccount;
    protected Model $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultAccount = Account::daemonPublicKey();
        if (Wallet::where('public_key', $this->defaultAccount)->doesntExist()) {
            Wallet::factory([
                'public_key' => $this->defaultAccount,
            ])->create();
        }

        $this->transaction = Transaction::factory([
            'wallet_public_key' => $this->defaultAccount,
        ])->create();
    }

    // Happy path
    public function test_it_can_get_a_transaction_with_all_data_by_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $transactionId = $this->transaction->id,
        ]);

        $this->assertArraySubset([
            'id' => $transactionId,
            'transactionId' => $this->transaction->transaction_chain_id,
            'transactionHash' => $this->transaction->transaction_chain_hash,
            'method' => $this->transaction->method,
            'state' => $this->transaction->state,
            'result' => $this->transaction->result,
            'encodedData' => $this->transaction->encoded_data,
            'signedAtBlock' => $this->transaction->signed_at_block,
            'createdAt' => $this->transaction->created_at->toIso8601String(),
            'updatedAt' => $this->transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_transaction_with_all_data_by_idempotency_key(): void
    {
        $response = $this->graphql($this->method, [
            'idempotencyKey' => $idempotencyKey = $this->transaction->idempotency_key,
        ]);

        $this->assertArraySubset([
            'id' => $this->transaction->id,
            'idempotencyKey' => $idempotencyKey,
            'transactionId' => $this->transaction->transaction_chain_id,
            'transactionHash' => $this->transaction->transaction_chain_hash,
            'method' => $this->transaction->method,
            'state' => $this->transaction->state,
            'result' => $this->transaction->result,
            'encodedData' => $this->transaction->encoded_data,
            'signedAtBlock' => $this->transaction->signed_at_block,
            'createdAt' => $this->transaction->created_at->toIso8601String(),
            'updatedAt' => $this->transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_transaction_with_all_data_by_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'transactionId' => $transactionId = $this->transaction->transaction_chain_id,
        ]);

        $this->assertArraySubset([
            'id' => $this->transaction->id,
            'transactionId' => $transactionId,
            'transactionHash' => $this->transaction->transaction_chain_hash,
            'method' => $this->transaction->method,
            'state' => $this->transaction->state,
            'result' => $this->transaction->result,
            'encodedData' => $this->transaction->encoded_data,
            'signedAtBlock' => $this->transaction->signed_at_block,
            'createdAt' => $this->transaction->created_at->toIso8601String(),
            'updatedAt' => $this->transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_transaction_with_all_data_by_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHash' => $transactionHash = $this->transaction->transaction_chain_hash,
        ]);

        $this->assertArraySubset([
            'id' => $this->transaction->id,
            'transactionId' => $this->transaction->transaction_chain_id,
            'transactionHash' => $transactionHash,
            'method' => $this->transaction->method,
            'state' => $this->transaction->state,
            'result' => $this->transaction->result,
            'encodedData' => $this->transaction->encoded_data,
            'signedAtBlock' => $this->transaction->signed_at_block,
            'createdAt' => $this->transaction->created_at->toIso8601String(),
            'updatedAt' => $this->transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_transaction_with_result(): void
    {
        $transaction = Transaction::factory([
            'wallet_public_key' => $this->defaultAccount,
            'result' => fake()->randomElement([
                SystemEventType::EXTRINSIC_SUCCESS->name,
                SystemEventType::EXTRINSIC_FAILED->name,
            ]),
        ])->create();

        $response = $this->graphql($this->method, [
            'transactionHash' => $transactionHash = $transaction->transaction_chain_hash,
        ]);

        $this->assertArraySubset([
            'id' => $transaction->id,
            'transactionId' => $transaction->transaction_chain_id,
            'transactionHash' => $transactionHash,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'result' => $transaction->result,
            'encodedData' => $transaction->encoded_data,
            'signedAtBlock' => $transaction->signed_at_block,
            'createdAt' => $transaction->created_at->toIso8601String(),
            'updatedAt' => $transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_transaction_with_events(): void
    {
        $event = Event::factory([
            'transaction_id' => $this->transaction->id,
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
        ]);

        $this->assertArraySubset([
            'id' => $this->transaction->id,
            'transactionId' => $this->transaction->transaction_chain_id,
            'transactionHash' => $this->transaction->transaction_chain_hash,
            'method' => $this->transaction->method,
            'state' => $this->transaction->state,
            'result' => $this->transaction->result,
            'events' => [
                'edges' => [
                    [
                        'node' => [
                            'phase' => $event->phase,
                            'lookUp' => $event->look_up,
                            'moduleId' => $event->module_id,
                            'eventId' => $event->event_id,
                            'params' => JSON::decode($event->params, true),
                        ],
                    ],
                ],
            ],
            'encodedData' => $this->transaction->encoded_data,
            'signedAtBlock' => $this->transaction->signed_at_block,
            'createdAt' => $this->transaction->created_at->toIso8601String(),
            'updatedAt' => $this->transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);
    }

    // Exception Path
    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => null,
        ], true);

        $this->assertArraySubset(
            ['id' => ['The id field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$id" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'transactionId' => null,
        ], true);

        $this->assertArraySubset(
            ['transactionId' => ['The transaction id field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'transactionId' => 'invalid',
        ], true);

        $this->assertArraySubset(
            ['transactionId' => ['The transaction id has a not valid substrate transaction ID.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHash' => null,
        ], true);

        $this->assertArraySubset(
            ['transactionHash' => ['The transaction hash field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHash' => 'invalid',
        ], true);

        $this->assertArraySubset(
            ['transactionHash' => ['The transaction hash has an invalid hex string.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_using_id_and_hash(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionHash' => $this->transaction->transaction_chain_hash,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_id_and_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->transaction->id,
            'transactionId' => $this->transaction->transaction_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_transaction_id_and_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'transactionId' => $this->transaction->transaction_chain_id,
            'transactionHash' => $this->transaction->transaction_chain_hash,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }
}
