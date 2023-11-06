<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Substrate\SystemEventType;
use Enjin\Platform\Models\Event;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Collection;

class GetTransactionsTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'GetTransactions';
    protected string $defaultAccount;
    protected Collection $transactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultAccount = Account::daemonPublicKey();
        $this->transactions = $this->generateTransactions();
    }

    protected function tearDown(): void
    {
        Transaction::destroy($this->transactions);

        parent::tearDown();
    }

    public function test_it_can_get_a_single_transaction_using_ids_with_all_data(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [($transaction = fake()->randomElement($this->transactions))->id],
        ]);

        $this->assertArraySubset([
            'id' => $transaction->id,
            'transactionId' => $transaction->transaction_chain_id,
            'transactionHash' => $transaction->transaction_chain_hash,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'encodedData' => $transaction->encoded_data,
            'signedAtBlock' => $transaction->signed_at_block,
            'createdAt' => $transaction->created_at->toIso8601String(),
            'updatedAt' => $transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response['edges'][0]['node']);
    }

    public function test_it_can_get_a_single_transaction_using_any_filter_with_all_data(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [($transaction = fake()->randomElement($this->transactions))->transaction_chain_id],
        ]);

        $this->assertArraySubset([
            'id' => $transaction->id,
            'transactionId' => $transaction->transaction_chain_id,
            'transactionHash' => $transaction->transaction_chain_hash,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'encodedData' => $transaction->encoded_data,
            'signedAtBlock' => $transaction->signed_at_block,
            'createdAt' => $transaction->created_at->toIso8601String(),
            'updatedAt' => $transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response['edges'][0]['node']);
    }

    public function test_it_can_get_a_single_transaction_using_idempotency_key_with_all_data(): void
    {
        $response = $this->graphql($this->method, [
            'idempotencyKeys' => [($transaction = fake()->randomElement($this->transactions))->idempotency_key],
        ]);

        $this->assertArraySubset([
            'id' => $transaction->id,
            'idempotencyKey' => $transaction->idempotency_key,
            'transactionId' => $transaction->transaction_chain_id,
            'transactionHash' => $transaction->transaction_chain_hash,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'encodedData' => $transaction->encoded_data,
            'signedAtBlock' => $transaction->signed_at_block,
            'createdAt' => $transaction->created_at->toIso8601String(),
            'updatedAt' => $transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response['edges'][0]['node']);
    }

    public function test_it_can_get_a_single_transaction_with_events(): void
    {
        $transaction = fake()->randomElement($this->transactions);
        $event = Event::factory([
            'transaction_id' => $transaction->id,
        ])->create();

        $response = $this->graphql($this->method, [
            'transactionIds' => [$transaction->transaction_chain_id],
        ]);

        $this->assertArraySubset([
            'id' => $transaction->id,
            'transactionId' => $transaction->transaction_chain_id,
            'transactionHash' => $transaction->transaction_chain_hash,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'encodedData' => $transaction->encoded_data,
            'signedAtBlock' => $transaction->signed_at_block,
            'createdAt' => $transaction->created_at->toIso8601String(),
            'updatedAt' => $transaction->updated_at->toIso8601String(),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
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
        ], $response['edges'][0]['node']);
    }

    public function test_it_can_fetch_with_no_args(): void
    {
        $response = $this->graphql($this->method);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_args(): void
    {
        $response = $this->graphql($this->method, []);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_ids(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_idempotency_keys(): void
    {
        $response = $this->graphql($this->method, [
            'idempotencyKeys' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_transaction_ids(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_transaction_hashes(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHashes' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_methods(): void
    {
        $response = $this->graphql($this->method, [
            'methods' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_states(): void
    {
        $response = $this->graphql($this->method, [
            'states' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_results(): void
    {
        $response = $this->graphql($this->method, [
            'results' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_event_ids(): void
    {
        $response = $this->graphql($this->method, [
            'eventIds' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_ids(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_transaction_ids(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_transaction_hashes(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHashes' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_methods(): void
    {
        $response = $this->graphql($this->method, [
            'methods' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_states(): void
    {
        $response = $this->graphql($this->method, [
            'states' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_results(): void
    {
        $response = $this->graphql($this->method, [
            'results' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_event_ids(): void
    {
        $response = $this->graphql($this->method, [
            'eventIds' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_event_types(): void
    {
        $response = $this->graphql($this->method, [
            'eventTypes' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_get_filter_transactions_by_id(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [$transactionId = fake()->randomElement($this->transactions)->id],
        ]);

        $this->assertTrue(1 === $response['totalCount']);
        $this->assertEquals($transactionId, $response['edges'][0]['node']['id']);
    }

    public function test_it_can_get_filter_transactions_by_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [$transactionId = fake()->randomElement($this->transactions)->transaction_chain_id],
        ]);

        $this->assertTrue(1 === $response['totalCount']);
        $this->assertEquals($transactionId, $response['edges'][0]['node']['transactionId']);
    }

    public function test_it_can_get_filter_transactions_by_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = fake()->randomElement($this->transactions)->transaction_chain_hash],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
    }

    public function test_it_can_get_filter_transactions_by_methods(): void
    {
        $response = $this->graphql($this->method, [
            'methods' => [$method = fake()->randomElement($this->transactions)->method],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
    }

    public function test_it_can_get_filter_transactions_by_states(): void
    {
        $response = $this->graphql($this->method, [
            'states' => [$state = fake()->randomElement($this->transactions)->state],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
    }

    public function test_it_can_get_filter_transactions_by_results(): void
    {
        $response = $this->graphql($this->method, [
            'results' => [$result = fake()->randomElement($this->transactions)->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_can_filter_by_transaction_hash_and_methods(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = $transaction->transaction_chain_hash],
            'methods' => [$method = $transaction->method],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
    }

    public function test_it_can_filter_by_transaction_hash_and_states(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = $transaction->transaction_chain_hash],
            'states' => [$state = $transaction->state],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
    }

    public function test_it_can_filter_by_transaction_hash_and_results(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = $transaction->transaction_chain_hash],
            'results' => [$result = $transaction->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_can_filter_with_methods_and_states(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'methods' => [$method = $transaction->method],
            'states' => [$state = $transaction->state],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
    }

    public function test_it_can_filter_with_methods_and_results(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'methods' => [$method = $transaction->method],
            'results' => [$result = $transaction->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_can_filter_with_states_and_results(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'states' => [$state = $transaction->state],
            'results' => [$result = $transaction->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_can_filter_with_hashes_methods_states(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = $transaction->transaction_chain_hash],
            'methods' => [$method = $transaction->method],
            'states' => [$state = $transaction->state],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
    }

    public function test_it_can_filter_with_hashes_methods_results(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = $transaction->transaction_chain_hash],
            'methods' => [$method = $transaction->method],
            'results' => [$result = $transaction->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_can_filter_with_methods_states_results(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'methods' => [$method = $transaction->method],
            'states' => [$state = $transaction->state],
            'results' => [$result = $transaction->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_can_filter_with_hashes_methods_states_results(): void
    {
        $transaction = fake()->randomElement($this->transactions);

        $response = $this->graphql($this->method, [
            'transactionHashes' => [$transactionHash = $transaction->transaction_chain_hash],
            'methods' => [$method = $transaction->method],
            'states' => [$state = $transaction->state],
            'results' => [$result = $transaction->result],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'transactionHash', $transactionHash));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'method', $method));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'state', $state));
        $this->assertTrue($this->haveFieldEqualsTo($response['edges'], 'result', $result));
    }

    public function test_it_will_fail_with_invalid_id(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$ids" got invalid value "invalid" at "ids[0]"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_transaction_id(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => ['invalid'],
        ], true);

        $this->assertArraySubset(
            ['transactionIds' => ['The transaction ids has a not valid substrate transaction ID.']],
            $response['error'],
        );
    }

    // Exception Path

    public function test_it_will_fail_with_invalid_transaction_hash(): void
    {
        $response = $this->graphql($this->method, [
            'transactionHashes' => ['invalid'],
        ], true);

        $this->assertArraySubset(
            ['transactionHashes' => ['The transaction hashes has an invalid hex string.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_method(): void
    {
        $response = $this->graphql($this->method, [
            'methods' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$methods" got invalid value "invalid" at "methods[0]"; Value "invalid" does not exist in "TransactionMethod" enum',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_states(): void
    {
        $response = $this->graphql($this->method, [
            'states' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$states" got invalid value "invalid" at "states[0]"; Value "invalid" does not exist in "TransactionState" enum',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_results(): void
    {
        $response = $this->graphql($this->method, [
            'results' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$results" got invalid value "invalid" at "results[0]"; Value "invalid" does not exist in "TransactionResult" enum',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_ids_and_hashes(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [fake()->randomElement($this->transactions)->id],
            'transactionHashes' => [fake()->randomElement($this->transactions)->transaction_chain_hash],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_ids_and_transaction_ids(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [fake()->randomElement($this->transactions)->id],
            'transactionIds' => [fake()->randomElement($this->transactions)->transaction_chain_id],
        ], true);

        $this->assertStringContainsString(
            'Only one of these filter(s) can be used: ids, transactionIds, idempotencyKeys',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_transaction_ids_and_hashes(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [fake()->randomElement($this->transactions)->transaction_chain_id],
            'transactionHashes' => [fake()->randomElement($this->transactions)->transaction_chain_hash],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_ids_and_methods(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [fake()->randomElement($this->transactions)->id],
            'methods' => [fake()->randomElement($this->transactions)->method],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_transactions_ids_and_methods(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [fake()->randomElement($this->transactions)->transaction_chain_id],
            'methods' => [fake()->randomElement($this->transactions)->method],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_ids_and_states(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [fake()->randomElement($this->transactions)->id],
            'states' => [fake()->randomElement($this->transactions)->state],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_transactions_ids_and_states(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [fake()->randomElement($this->transactions)->transaction_chain_id],
            'states' => [fake()->randomElement($this->transactions)->state],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_withids_and_results(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [fake()->randomElement($this->transactions)->id],
            'results' => [fake()->randomElement($this->transactions)->result],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_transactions_ids_and_results(): void
    {
        $response = $this->graphql($this->method, [
            'transactionIds' => [fake()->randomElement($this->transactions)->transaction_chain_id],
            'results' => [fake()->randomElement($this->transactions)->result],
        ], true);

        $this->assertStringContainsString(
            'The filter(s) "ids, transactionIds, idempotencyKeys" can only be used alone. You cannot combine them with other filters',
            $response['error'],
        );
    }

    protected function haveFieldEqualsTo(array $transactions, string $field, mixed $value): bool
    {
        return empty(array_filter($transactions, fn ($tx) => $tx['node'][$field] !== $value));
    }

    protected function generateTransactions(?int $numberOfTransactions = 5): Collection
    {
        return collect(range(0, $numberOfTransactions - 1))
            ->map(fn () => Transaction::factory([
                'wallet_public_key' => $this->defaultAccount,
                'result' => fake()->randomElement([
                    SystemEventType::EXTRINSIC_SUCCESS->name,
                    SystemEventType::EXTRINSIC_FAILED->name,
                ]),
            ])->create());
    }
}
