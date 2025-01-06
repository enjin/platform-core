<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class RetryTransactionsTest extends TestCaseGraphQL
{
    protected string $method = 'RetryTransactions';

    public function test_it_can_retry_transaction(): void
    {
        $transaction = Transaction::factory()->create();
        $response = $this->graphql($this->method, [
            'ids' => [$transaction->id],
        ]);
        $this->assertTrue($response);
        $transaction->refresh();
        $this->assertEquals($transaction->state, TransactionState::PENDING->name);
        $this->assertNull($transaction->transaction_chain_hash);


        $transaction = Transaction::factory()->create();
        $response = $this->graphql($this->method, [
            'idempotencyKeys' => [$transaction->idempotency_key],
        ]);
        $this->assertTrue($response);
        $transaction->refresh();
        $this->assertEquals($transaction->state, TransactionState::PENDING->name);
        $this->assertNull($transaction->transaction_chain_hash);
    }

    public function test_it_will_fail_with_finalized_state(): void
    {
        $transaction = Transaction::factory(['state' => TransactionState::FINALIZED->name])->create();
        $response = $this->graphql($this->method, [
            'ids' => [$transaction->id],
        ], true);
        $this->assertArrayContainsArray(
            ['ids' => ['The selected ids is invalid.']],
            $response['error']
        );

        $response = $this->graphql($this->method, [
            'idempotencyKeys' => [$transaction->idempotency_key],
        ], true);
        $this->assertArrayContainsArray(
            ['idempotencyKeys' => ['The selected idempotency keys is invalid.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_ids(): void
    {
        $response = $this->graphql($this->method, ['ids' => 'Invalid'], true);
        $this->assertStringContainsString(
            'Variable "$ids" got invalid value "Invalid"; Cannot represent following value as uint256',
            $response['error']
        );

        $response = $this->graphql($this->method, ['ids' => null], true);
        $this->assertArrayContainsArray(
            [
                'ids' => ['The ids field is required when idempotency keys is not present.'],
                'idempotencyKeys' => ['The idempotency keys field is required when ids is not present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, ['ids' => []], true);
        $this->assertArrayContainsArray(
            [
                'ids' => ['The ids field is required when idempotency keys is not present.'],
                'idempotencyKeys' => ['The idempotency keys field is required when ids is not present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, ['ids' => [12345678910]], true);
        $this->assertArrayContainsArray(
            ['ids' => ['The selected ids is invalid.']],
            $response['error']
        );

        $response = $this->graphql($this->method, ['ids' => [1], 'idempotencyKeys' => ['asd']], true);
        $this->assertArrayContainsArray(
            [
                'ids' => ['The ids field prohibits idempotency keys from being present.'],
                'idempotencyKeys' => ['The idempotency keys field prohibits ids from being present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, ['ids' => [Hex::MAX_UINT256 + 1]], true);
        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        $response = $this->graphql($this->method, ['ids' => [1, 1]], true);
        $this->assertArrayContainsArray(
            ['ids.0' => ['The ids.0 field has a duplicate value.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_idempotency_keys(): void
    {
        $response = $this->graphql($this->method, ['idempotencyKeys' => null], true);
        $this->assertArrayContainsArray(
            [
                'ids' => ['The ids field is required when idempotency keys is not present.'],
                'idempotencyKeys' => ['The idempotency keys field is required when ids is not present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, ['idempotencyKeys' => []], true);
        $this->assertArrayContainsArray(
            [
                'ids' => ['The ids field is required when idempotency keys is not present.'],
                'idempotencyKeys' => ['The idempotency keys field is required when ids is not present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, ['idempotencyKeys' => [fake()->uuid()]], true);
        $this->assertArrayContainsArray(
            ['idempotencyKeys' => ['The selected idempotency keys is invalid.']],
            $response['error']
        );

        $response = $this->graphql($this->method, ['idempotencyKeys' => ['a', 'a']], true);
        $this->assertArrayContainsArray(
            ['idempotencyKeys.0' => ['The idempotencyKeys.0 field has a duplicate value.']],
            $response['error']
        );
    }
}
