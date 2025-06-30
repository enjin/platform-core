<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\ToFixQueries;

use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\Queries\Codec;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Enjin\Platform\Tests\Support\MocksSocketClient;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class GetWalletTest extends TestCaseGraphQL
{
    use MocksHttpClient;
    use MocksSocketClient;

    protected string $method = 'GetWallet';

    protected Codec $codec;

    protected Model $verification;
    protected Wallet $wallet;
    protected Transaction $transaction;

    protected Wallet $anotherWallet;
    protected Wallet $approvedWallet;

    protected Collection $collection;
    protected CollectionAccount $collectionAccount;
    protected Model $collectionAccountApproval;
    protected Attribute $collectionAttribute;

    protected Token $token;
    protected TokenAccount $tokenAccount;
    protected Model $tokenAccountApproval;
    protected Model $tokenAccountNamedReserve;
    protected Attribute $tokenAttribute;

    protected Collection $anotherCollection;
    protected CollectionAccount $anotherCollectionAccount;
    protected Model $anotherCollectionAccountApprovedToWallet;

    protected Token $anotherToken;
    protected TokenAccount $anotherTokenAccount;
    protected Model $anotherTokenAccountApprovedToWallet;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->verification = Verification::factory([
            'public_key' => $address = app(Generator::class)->public_key(),
        ])->create();
        $this->wallet = Wallet::factory([
            'public_key' => $address,
            'verification_id' => $this->verification->verification_id,
        ])->create();
        $this->transaction = Transaction::factory([
            'wallet_public_key' => $this->wallet->public_key,
        ])->create();

        $this->anotherWallet = Wallet::factory()->create();
        $this->approvedWallet = Wallet::factory()->create();

        $this->collection = Collection::factory([
            'owner_wallet_id' => $this->wallet,
            'token_count' => 1,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
            'attribute_count' => 1,
        ])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->collectionAccountApproval = CollectionAccountApproval::factory([
            'collection_account_id' => $this->collectionAccount,
            'wallet_id' => $this->approvedWallet,
        ])->create();
        $this->collectionAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => null,
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'wallet_id' => $this->wallet,
        ])->create();
        $this->tokenAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ])->create();
        $this->tokenAccountApproval = TokenAccountApproval::factory([
            'token_account_id' => $this->tokenAccount,
            'wallet_id' => $this->approvedWallet,
        ])->create();
        $this->tokenAccountNamedReserve = TokenAccountNamedReserve::factory([
            'token_account_id' => $this->tokenAccount,
        ])->create();

        $this->anotherCollection = Collection::factory([
            'owner_wallet_id' => $this->anotherWallet,
            'token_count' => 1,
        ])->create();
        $this->anotherToken = Token::factory([
            'collection_id' => $this->anotherCollection,
            'attribute_count' => 1,
        ])->create();
        $this->anotherCollectionAccount = CollectionAccount::factory([
            'collection_id' => $this->anotherCollection,
            'wallet_id' => $this->anotherWallet,
            'account_count' => 1,
        ])->create();
        $this->anotherCollectionAccountApprovedToWallet = CollectionAccountApproval::factory([
            'collection_account_id' => $this->anotherCollectionAccount,
            'wallet_id' => $this->wallet,
        ])->create();
        $this->anotherTokenAccount = TokenAccount::factory([
            'collection_id' => $this->anotherCollection,
            'token_id' => $this->anotherToken,
            'wallet_id' => $this->anotherWallet,
        ])->create();
        $this->anotherTokenAccountApprovedToWallet = TokenAccountApproval::factory([
            'token_account_id' => $this->anotherTokenAccount,
            'wallet_id' => $this->wallet,
        ])->create();
    }

    public function test_it_can_get_wallet_with_all_data_by_id(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
        ]);

        $this->assertArrayContainsArray([
            'id' => $this->wallet->id,
            'account' => [
                'publicKey' => $this->wallet->public_key,
            ],
            'externalId' => $this->wallet->external_id,
            'managed' => $this->wallet->managed,
            'network' => $this->wallet->network,
            'nonce' => $this->mockedData()['nonce'],
            'balances' => [
                'free' => $this->mockedData()['balances']['free'],
                'reserved' => $this->mockedData()['balances']['reserved'],
                'miscFrozen' => $this->mockedData()['balances']['miscFrozen'],
                'feeFrozen' => $this->mockedData()['balances']['feeFrozen'],
            ],
            'collectionAccounts' => [
                'edges' => [
                    [
                        'node' => [
                            'accountCount' => $this->collectionAccount->account_count,
                            'isFrozen' => $this->collectionAccount->is_frozen,
                            'collection' => [
                                'collectionId' => $this->collection->collection_chain_id,
                            ],
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                            'approvals' => [
                                [
                                    'expiration' => $this->collectionAccountApproval->expiration,
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->approvedWallet->public_key,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'tokenAccounts' => [
                'edges' => [
                    [
                        'node' => [
                            'balance' => $this->tokenAccount->balance,
                            'reservedBalance' => $this->tokenAccount->reserved_balance,
                            'isFrozen' => $this->tokenAccount->is_frozen,
                            'collection' => [
                                'collectionId' => $this->collection->collection_chain_id,
                            ],
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                            'token' => [
                                'tokenId' => $this->token->token_chain_id,
                            ],
                            'approvals' => [
                                [
                                    'amount' => $this->tokenAccountApproval->amount,
                                    'expiration' => $this->tokenAccountApproval->expiration,
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->approvedWallet->public_key,
                                        ],
                                    ],
                                ],
                            ],
                            'namedReserves' => [
                                [
                                    'pallet' => $this->tokenAccountNamedReserve->pallet,
                                    'amount' => $this->tokenAccountNamedReserve->amount,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'collectionAccountApprovals' => [
                'edges' => [
                    [
                        'node' => [
                            'expiration' => $this->anotherCollectionAccountApprovedToWallet->expiration,
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'tokenAccountApprovals' => [
                'edges' => [
                    [
                        'node' => [
                            'amount' => $this->anotherTokenAccountApprovedToWallet->amount,
                            'expiration' => $this->anotherTokenAccountApprovedToWallet->expiration,
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'transactions' => [
                'edges' => [
                    [
                        'node' => [
                            'id' => $this->transaction->id,
                            'transactionId' => $this->transaction->transaction_chain_id,
                            'transactionHash' => $this->transaction->transaction_chain_hash,
                            'method' => $this->transaction->method,
                            'state' => $this->transaction->state,
                            'encodedData' => $this->transaction->encoded_data,
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'ownedCollections' => [
                'edges' => [
                    [
                        'node' => [
                            'collectionId' => $this->collection->collection_chain_id,
                            'maxTokenCount' => $this->collection->max_token_count,
                            'maxTokenSupply' => $this->collection->max_token_supply,
                            'forceCollapsingSupply' => $this->collection->force_collapsing_supply,
                            'network' => $this->collection->network,
                            'owner' => [
                                'account' => [
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                            'attributes' => [
                                [
                                    'key' => Hex::safeConvertToString($this->collectionAttribute->key),
                                    'value' => Hex::safeConvertToString($this->collectionAttribute->value),
                                ],
                            ],
                            'accounts' => [
                                'edges' => [
                                    [
                                        'node' => [
                                            'accountCount' => $this->collectionAccount->account_count,
                                            'isFrozen' => $this->collectionAccount->is_frozen,
                                            'collection' => [
                                                'collectionId' => $this->collection->collection_chain_id,
                                            ],
                                            'wallet' => [
                                                'account' => [
                                                    'publicKey' => $this->wallet->public_key,
                                                ],
                                            ],
                                            'approvals' => [
                                                [
                                                    'expiration' => $this->collectionAccountApproval->expiration,
                                                    'wallet' => [
                                                        'account' => [
                                                            'publicKey' => $this->approvedWallet->public_key,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'tokens' => [
                                'edges' => [
                                    [
                                        'node' => [
                                            'tokenId' => $this->token->token_chain_id,
                                            'supply' => $this->token->supply,
                                            'cap' => $this->token->cap,
                                            'capSupply' => $this->token->cap_supply,
                                            'isFrozen' => $this->token->is_frozen,
                                            'attributeCount' => $this->token->attribute_count,
                                            'collection' => [
                                                'collectionId' => $this->collection->collection_chain_id,
                                            ],
                                            'attributes' => [
                                                [
                                                    'key' => Hex::safeConvertToString($this->tokenAttribute->key),
                                                    'value' => Hex::safeConvertToString($this->tokenAttribute->value),
                                                ],
                                            ],
                                            'accounts' => [
                                                'edges' => [
                                                    [
                                                        'node' => [
                                                            'balance' => $this->tokenAccount->balance,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'metadata' => $this->token->metadata,
                                            'nonFungible' => $this->token->non_fungible,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_wallet_and_filter_collection_accounts(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'collectionAccountsCollectionIds' => [$this->collection->collection_chain_id],
        ]);

        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['collectionAccounts']['totalCount'] === 1);
        $this->assertArrayContainsArray([
            'accountCount' => $this->collectionAccount->account_count,
            'isFrozen' => $this->collectionAccount->is_frozen,
        ], $response['collectionAccounts']['edges'][0]['node']);
    }

    public function test_it_can_get_a_wallet_and_filter_collection_account_approvals(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'collectionApprovalAccounts' => [$this->wallet->public_key],
        ]);
        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['collectionAccounts']['totalCount'] === 1);
        $this->assertEquals(
            Arr::get($response, 'collectionAccounts.edges.0.node.wallet.account.publicKey'),
            $this->wallet->public_key
        );
    }

    public function test_it_can_get_a_wallet_and_filter_token_account_approvals(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'tokenApprovalAccounts' => [$this->wallet->public_key],
        ]);
        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['tokenAccountApprovals']['totalCount'] === 1);
        $this->assertEquals(
            Arr::get($response, 'tokenAccountApprovals.edges.0.node.wallet.account.publicKey'),
            $this->wallet->public_key
        );
    }

    public function test_it_can_get_a_wallet_and_filter_token_accounts(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'tokenAccountsCollectionIds' => [$this->collection->collection_chain_id],
            'tokenAccountsTokenIds' => [$this->token->token_chain_id],
        ]);

        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['tokenAccounts']['totalCount'] === 1);
        $this->assertArrayContainsArray([
            'balance' => $this->tokenAccount->balance,
            'reservedBalance' => $this->tokenAccount->reserved_balance,
            'isFrozen' => $this->tokenAccount->is_frozen,
        ], $response['tokenAccounts']['edges'][0]['node']);

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'bulkFilter' => [
                [
                    'collectionId' => $this->collection->collection_chain_id,
                    'tokenIds' => [$this->token->token_chain_id],
                ],
            ],
        ]);
        $this->assertNotEmpty(Arr::get($response, 'tokenAccounts.edges'));

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'bulkFilter' => [
                [
                    'collectionId' => $this->collection->collection_chain_id,
                    'tokenIds' => ['1..' . $this->token->token_chain_id + 1],
                ],
            ],
        ]);
        $this->assertNotEmpty(Arr::get($response, 'tokenAccounts.edges'));
    }

    public function test_it_can_get_a_wallet_and_filter_owned_collections(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'id' => $id = $this->wallet->id,
            'ownedCollectionsCollectionIds' => [$this->collection->collection_chain_id],
        ]);

        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['ownedCollections']['totalCount'] === 1);
        $this->assertArrayContainsArray([
            'collectionId' => $this->collection->collection_chain_id,
            'maxTokenCount' => $this->collection->max_token_count,
            'maxTokenSupply' => $this->collection->max_token_supply,
            'forceCollapsingSupply' => $this->collection->force_collapsing_supply,
            'network' => $this->collection->network,
        ], $response['ownedCollections']['edges'][0]['node']);
    }

    public function test_it_will_not_get_any_transactions_for_a_wallet_that_doesnt_have_an_address(): void
    {
        $wallet = Wallet::factory([
            'public_key' => null,
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $id = $wallet->id,
        ]);

        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['transactions']['totalCount'] === 0);
    }

    public function test_it_will_have_null_balance_and_nonce_for_a_wallet_that_doesnt_have_an_address(): void
    {
        $wallet = Wallet::factory([
            'public_key' => null,
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $id = $wallet->id,
        ]);

        $this->assertTrue($response['id'] == $id);
        $this->assertTrue($response['nonce'] === null);
        $this->assertTrue($response['balances'] === null);
    }

    public function test_it_can_get_wallet_by_external_id(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'externalId' => $externalId = $this->wallet->external_id,
        ]);

        $this->assertArrayContainsArray([
            'id' => $this->wallet->id,
            'externalId' => $externalId,
        ], $response);
    }

    public function test_it_can_get_wallet_by_address(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'account' => SS58Address::encode($this->wallet->public_key),
        ]);

        $this->assertArrayContainsArray([
            'id' => $this->wallet->id,
            'account' => [
                'publicKey' => $this->wallet->public_key,
            ],
        ], $response);
    }

    // Exception Path

    public function test_it_can_get_wallet_by_verification_id(): void
    {
        $this->mockNonceAndBalance();

        $response = $this->graphql($this->method, [
            'verificationId' => $this->wallet->verification_id,
        ]);

        $this->assertArrayContainsArray([
            'id' => $this->wallet->id,
            'account' => [
                'publicKey' => $this->wallet->public_key,
            ],
        ], $response);
    }

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

        $this->assertArrayContainsArray(
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
            'Variable "$id" got invalid value "invalid"; Int cannot represent non-integer value',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_external_id(): void
    {
        $response = $this->graphql($this->method, [
            'externalId' => null,
        ], true);

        $this->assertArrayContainsArray(
            ['externalId' => ['The external id field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => null,
        ], true);

        $this->assertArrayContainsArray(
            ['verificationId' => ['The verification id field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => null,
        ], true);

        $this->assertArrayContainsArray(
            ['account' => ['The account field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['account' => ['The account is not a valid substrate account.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['verificationId' => ['The verification ID is not valid.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_using_id_and_external_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'externalId' => $this->wallet->external_id,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_id_and_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'verificationId' => $this->wallet->verification_id,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_id_and_address(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'account' => $this->wallet->public_key,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_external_id_and_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'externalId' => $this->wallet->external_id,
            'verificationId' => $this->wallet->verification_id,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_external_id_and_address(): void
    {
        $response = $this->graphql($this->method, [
            'externalId' => $this->wallet->external_id,
            'account' => $this->wallet->public_key,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_using_verification_id_and_address(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => $this->wallet->verification_id,
            'account' => $this->wallet->public_key,
        ], true);

        $this->assertStringContainsString(
            'Please supply just one field.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => '',
        ], true);

        $this->assertStringContainsString(
            'Variable "$id" got invalid value (empty string)',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_external_id(): void
    {
        $response = $this->graphql($this->method, [
            'externalId' => '',
        ], true);

        $this->assertArrayContainsArray(
            ['externalId' => ['The external id field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationId' => '',
        ], true);

        $this->assertArrayContainsArray(
            ['verificationId' => ['The verification id field must have a value.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => '',
        ], true);

        $this->assertArrayContainsArray(
            ['account' => ['The account field must have a value.']],
            $response['error'],
        );
    }

    protected function mockNonceAndBalance(): void
    {
        $this->mockHttpClient(
            json_encode(
                [
                    'jsonrpc' => '2.0',
                    'result' => '0x1d000000000000000100000000000000331f60a549ec45201cd30000000000000080ebc061752bf3a5000000000000000000000000000000000000000000000000000000000000000000000000000000',
                    'id' => 1,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    protected function mockedData(): array
    {
        return [
            'nonce' => 29,
            'balances' => [
                'free' => '996938162244142665572147',
                'reserved' => '3061235000000000000000',
                'miscFrozen' => '0',
                'feeFrozen' => '0',
            ],
        ];
    }
}
