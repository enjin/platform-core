<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Facades\Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchTransferMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
use Enjin\Platform\Models\Substrate\SimpleTransferParams;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Override;

class BatchTransferTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'BatchTransfer';
    protected Codec $codec;
    protected Wallet $wallet;
    protected Collection $collection;
    protected CollectionAccount $collectionAccount;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected TokenAccount $tokenAccount;
    protected Wallet $recipient;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->wallet = Account::daemon();
        $this->recipient = Wallet::factory()->create();
        $this->collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet->id]);
        $this->token = Token::factory(['collection_id' => $this->collection->id])->create();
        $this->tokenAccount = TokenAccount::factory([
            'wallet_id' => $this->wallet,
            'token_id' => $this->token,
            'collection_id' => $this->collection,
        ])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $signingWallet = Wallet::factory([
            'managed' => false,
        ])->create();

        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $signingWallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $signingWallet,
        ])->create();


        $recipient = [
            'accountId' => Account::daemonPublicKey(),
            'params' => new SimpleTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance)
            ),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
        $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                ],
            ],
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
            'skipValidation' => true,
            'simulate' => null,
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->public_key,
                ],
            ],
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_simple_single_transfer_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => new SimpleTransferParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance)
                    ),
                ],
            ]
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $amount,
                    ],
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);
    }

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => new SimpleTransferParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance)
                    ),
                ],
            ]
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $amount,
                    ],
                ],
            ],
            'simulate' => true,
        ]);

        $this->assertArrayContainsArray([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'deposit' => null,
            'wallet' => null,
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_simple_single_transfer(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => new SimpleTransferParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance)
                    ),
                ],
            ]
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $amount,
                    ],
                ],
            ],
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'signingPayload' => Substrate::getSigningPayload($encodedData, [
                'nonce' => $nonce,
                'tip' => '0',
            ]),
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_bypass_ownership(): void
    {
        $token = Token::factory([
            'collection_id' => $collection = Collection::factory()->create(['owner_wallet_id' => Wallet::factory()->create()]),
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
                        'amount' => fake()->numberBetween(1, 10),
                    ],
                ],
            ],
            'nonce' => fake()->numberBetween(),
        ], true);
        $this->assertEquals(
            ['recipients.0.simpleParams.amount' => ['The recipients.0.simpleParams.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );

        IsCollectionOwner::bypass();
        $response = $this->graphql($this->method, $params, true);
        $this->assertEquals(
            ['recipients.0.simpleParams.amount' => ['The recipients.0.simpleParams.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );
        IsCollectionOwner::unBypass();
    }

    public function test_it_can_batch_simple_single_transfer_with_public_key_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $signingWallet,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $signingWallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $signingWallet,
        ])->create();

        $recipient = [
            'accountId' => Account::daemonPublicKey(),
            'params' => new SimpleTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance)
            ),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
        $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                ],
            ],
            'signingAccount' => $signingAccount,
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingAccount,
                ],
            ],
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_simple_single_transfer_with_keep_alive(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => new SimpleTransferParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
                    ),
                ],
            ]
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $amount,
                    ],
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_simple_single_transfer_with_null_keep_alive(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => new SimpleTransferParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
                    ),
                ],
            ]
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $amount,
                        'keepAlive' => null,
                    ],
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_operator_single_transfer(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => new OperatorTransferParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        source: $source = $this->wallet->public_key,
                        amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance)
                    ),
                ],
            ]
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($source),
                        'amount' => $amount,
                    ],
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_operator_single_transfer_with_keep_alive(): void
    {
        $encodedData = $this->codec->encoder()->getEncoded(
            $this->method,
            BatchTransferMutation::getEncodableParams(
                collectionId: $collectionId = $this->collection->collection_chain_id,
                recipients: [
                    [
                        'accountId' => $recipient = $this->recipient->public_key,
                        'params' => new OperatorTransferParams(
                            tokenId: $this->tokenIdEncoder->encode(),
                            source: $source = $this->wallet->public_key,
                            amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
                        ),
                    ],
                ]
            )
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($source),
                        'amount' => $amount,
                    ],
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_operator_single_transfer_with_null_keep_alive(): void
    {
        $encodedData = $this->codec->encoder()->getEncoded(
            $this->method,
            BatchTransferMutation::getEncodableParams(
                collectionId: $collectionId = $this->collection->collection_chain_id,
                recipients: [
                    [
                        'accountId' => $recipient = $this->recipient->public_key,
                        'params' => new OperatorTransferParams(
                            tokenId: $this->tokenIdEncoder->encode(),
                            source: $source = $this->wallet->public_key,
                            amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
                        ),
                    ],
                ]
            )
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($source),
                        'amount' => $amount,
                        'keepAlive' => null,
                    ],
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_simple_multiple_transfers(): void
    {
        // TODO: We should validate if the sum of all amount doesn't exceed the total balance
        // Will do that later

        $recipients = collect(
            range(
                0,
                ($value = fake()->randomNumber(1, $this->tokenAccount->balance)) < 10 ? $value : 9
            )
        )->map(fn ($x) => [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => new SimpleTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance)
            ),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray()
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) {
                $simpleParams = $recipient['params']->toArray()['Simple'];
                $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable();

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                ];
            })->toArray(),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_simple_multiple_transfers_with_continue_on_failure(): void
    {
        // TODO: We should validate if the sum of all amount doesn't exceed the total balance
        // Will do that later

        $recipients = collect(
            range(
                0,
                ($value = fake()->randomNumber(1, $this->tokenAccount->balance)) < 10 ? $value : 9
            )
        )->map(fn ($x) => [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => new SimpleTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance)
            ),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray()
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) {
                $simpleParams = $recipient['params']->toArray()['Simple'];
                $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable();

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                ];
            })->toArray(),
            'continueOnFailure' => true,
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_operator_multiple_transfers(): void
    {
        $recipients = collect(
            range(
                0,
                ($value = fake()->randomNumber(1, $this->tokenAccount->balance)) < 10 ? $value : 9
            )
        )->map(fn ($x) => [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance)
            ),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray()
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) {
                $operatorParams = $recipient['params']->toArray()['Operator'];
                $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable();

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'operatorParams' => $operatorParams,
                ];
            })->toArray(),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_mixed_multiple_transfers(): void
    {
        $recipients = collect(
            range(
                0,
                ($value = fake()->randomNumber(1, $this->tokenAccount->balance)) < 10 ? $value : 9
            )
        )->map(fn ($x) => [
            'accountId' => fake()->randomElement([
                Wallet::factory()->create()->public_key, app(Generator::class)->public_key(),
            ]),
            'params' => fake()->randomElement([
                new SimpleTransferParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    amount: fake()->numberBetween(1, $this->tokenAccount->balance)
                ),
                new OperatorTransferParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    source: $this->wallet->public_key,
                    amount: fake()->numberBetween(1, $this->tokenAccount->balance)
                ),
            ]),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray()
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) {
                $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
                if (isset($simpleParams)) {
                    $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable();
                }
                $operatorParams = Arr::get($recipient['params']->toArray(), 'Operator');
                if (isset($operatorParams)) {
                    $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable();
                }

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                    'operatorParams' => $operatorParams,
                ];
            })->toArray(),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_transfer_with_signing_wallet_simple_transfer(): void
    {
        $signingWallet = Wallet::factory([
            'managed' => true,
        ])->create();

        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $signingWallet,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $signingWallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $signingWallet,
        ])->create();

        $recipient = [
            'accountId' => Account::daemonPublicKey(),
            'params' => new SimpleTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance)
            ),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
        $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                ],
            ],
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->public_key,
                ],
            ],
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_transfer_with_signing_wallet_operator_transfer(): void
    {
        $signingWallet = Wallet::factory([
            'managed' => true,
        ])->create();

        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $signingWallet,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();

        $recipient = [
            'accountId' => $this->recipient->public_key,
            'params' => new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(1, $tokenAccount->balance)
            ),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $operatorParams = Arr::get($recipient['params']->toArray(), 'Operator');
        $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'operatorParams' => $operatorParams,
                ],
            ],
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->public_key,
                ],
            ],
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_transfer_with_null_signing_wallet(): void
    {
        $recipient = [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => fake()->randomElement([
                new SimpleTransferParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    amount: fake()->numberBetween(1, $this->tokenAccount->balance)
                ),
                new OperatorTransferParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    source: $this->wallet->public_key,
                    amount: fake()->numberBetween(1, $this->tokenAccount->balance)
                ),
            ]),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
        if (isset($simpleParams)) {
            $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable();
        }
        $operatorParams = Arr::get($recipient['params']->toArray(), 'Operator');
        if (isset($operatorParams)) {
            $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable();
        }

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                    'operatorParams' => $operatorParams,
                ],
            ],
            'signingAccount' => null,
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_transfer_with_big_int_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $this->wallet,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();

        $recipient = [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => fake()->randomElement([
                new SimpleTransferParams(
                    tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                    amount: fake()->numberBetween(1, $tokenAccount->balance)
                ),
                new OperatorTransferParams(
                    tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                    source: $this->wallet->public_key,
                    amount: fake()->numberBetween(1, $tokenAccount->balance)
                ),
            ]),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
        if (isset($simpleParams)) {
            $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);
        }
        $operatorParams = Arr::get($recipient['params']->toArray(), 'Operator');
        if (isset($operatorParams)) {
            $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);
        }

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                    'operatorParams' => $operatorParams,
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_transfer_with_big_int_token_id(): void
    {
        $collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet]);
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();

        Token::where('token_chain_id', Hex::MAX_UINT128)->update(['token_chain_id' => random_int(1, 1000)]);
        $token = Token::factory([
            'collection_id' => $collection,
            'token_chain_id' => Hex::MAX_UINT128,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();

        $recipient = [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => fake()->randomElement([
                new SimpleTransferParams(
                    tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                    amount: fake()->numberBetween(1, $tokenAccount->balance)
                ),
                new OperatorTransferParams(
                    tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                    source: $this->wallet->public_key,
                    amount: fake()->numberBetween(1, $tokenAccount->balance)
                ),
            ]),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple');
        if (isset($simpleParams)) {
            $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);
        }
        $operatorParams = Arr::get($recipient['params']->toArray(), 'Operator');
        if (isset($operatorParams)) {
            $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);
        }

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                    'operatorParams' => $operatorParams,
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_batch_transfer_with_recipient_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $address = app(Generator::class)->public_key())?->delete();

        $recipient = [
            'accountId' => $address,
            'params' => fake()->randomElement([
                new SimpleTransferParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    amount: fake()->numberBetween(1, $this->tokenAccount->balance)
                ),
                new OperatorTransferParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    source: $this->wallet->public_key,
                    amount: fake()->numberBetween(1, $this->tokenAccount->balance)
                ),
            ]),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchTransferMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $simpleParams = Arr::get($recipient['params']->toArray(), 'Simple') ?? null;
        if (!empty($simpleParams)) {
            $simpleParams['tokenId'] = $this->tokenIdEncoder->toEncodable();
        }
        $operatorParams = Arr::get($recipient['params']->toArray(), 'Operator') ?? null;
        if (!empty($operatorParams)) {
            $operatorParams['tokenId'] = $this->tokenIdEncoder->toEncodable();
        }

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'simpleParams' => $simpleParams,
                    'operatorParams' => $operatorParams,
                ],
            ],
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path

    public function test_it_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_collection_id_non_existent(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" of required type "[TransferRecipient!]!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_empty_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients' => ['The recipients field must have at least 1 items.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" of non-null type "[TransferRecipient!]!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid"; Expected type "TransferRecipient" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_missing_address_in_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "account" of required type "String!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_address_in_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => null,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'value null at "recipients[0].account"; Expected non-nullable type "String!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_address_in_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => 'invalid',
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.account' => ['The recipients.0.account is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_missing_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'You need to set either simple params or operator params for every recipient.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'simpleParams' => null,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'You need to set either simple params or operator params for every recipient.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_empty_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'simpleParams' => [],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => 'invalid',
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid" at "recipients[0].simpleParams"; Expected type "SimpleTransferParams" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_missing_token_id_in_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_token_id_in_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => null,
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].simpleParams.tokenId"; Expected non-nullable type "EncodableTokenIdInput!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_token_id_in_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => ['integer' => 'invalid'],
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].simpleParams.tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_token_id_non_existent_in_recipient(): void
    {
        $tokenIdEncoder = new Integer(fake()->numberBetween());

        Token::where('token_chain_id', '=', $tokenIdEncoder->encode())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'simpleParams' => [
                        'tokenId' => $tokenIdEncoder->toEncodable(),
                        'amount' => $this->tokenAccount->balance,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.simpleParams.tokenId' => ['The recipients.0.simpleParams.tokenId does not exist in the specified collection.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "amount" of required type "BigInt!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => null,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].simpleParams.amount"; Expected non-nullable type "BigInt!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_zero_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.simpleParams.amount' => ['The recipients.0.simpleParams.amount is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_negative_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => -1,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value -1 at "recipients[0].simpleParams.amount"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid" at "recipients[0].simpleParams.amount"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_amount_greater_than_balance(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween($this->tokenAccount->balance + 1),
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.simpleParams.amount' => ['The recipients.0.simpleParams.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_keep_alive(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                        'keepAlive' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].simpleParams.keepAlive"; Boolean cannot represent a non boolean value',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_both_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot set simple params and operator params for the same recipient.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_over_two_hundred_fifty_items(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => collect(range(0, 251))->map(fn () => [
                'account' => SS58Address::encode(app(Generator::class)->public_key()),
                ...fake()->randomElement([
                    [
                        'simpleParams' => [
                            'tokenId' => $this->tokenIdEncoder->toEncodable(),
                            'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                        ],
                    ],
                    [
                        'operatorParams' => [
                            'tokenId' => $this->tokenIdEncoder->toEncodable(),
                            'source' => SS58Address::encode($this->wallet->public_key),
                            'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                        ],
                    ],
                ]),
            ], )->toArray(),
        ], true);

        $this->assertArrayContainsArray(
            ['recipients' => ['The recipients field must not have more than 250 items.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => null,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'You need to set either simple params or operator params for every recipient.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_empty_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => [],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => 'invalid',
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid" at "recipients[0].operatorParams"; Expected type "OperatorTransferParams" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_missing_token_id_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => [
                        'source' => $this->wallet->public_key,
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_token_id_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => [
                        'tokenId' => null,
                        'source' => $this->wallet->public_key,
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].operatorParams.tokenId"; Expected non-nullable type "EncodableTokenIdInput!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_token_id_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => [
                        'tokenId' => 'invalid',
                        'source' => $this->wallet->public_key,
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].operatorParams.tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_token_id_in_operator_non_existent(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.operatorParams.tokenId' => ['The recipients.0.operatorParams.tokenId does not exist in the specified collection.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_missing_source_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => [
                        'tokenId' => (new Integer($this->token->token_chain_id))->toEncodable(),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "source" of required type "String!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_source_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => null,
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].operatorParams.source"; Expected non-nullable type "String!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_source_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => 'invalid',
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.operatorParams.source' => ['The recipients.0.operatorParams.source is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_source_doesnt_exists_in_operator(): void
    {
        Wallet::where('public_key', '=', $source = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($source),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.operatorParams.amount' => ['The recipients.0.operatorParams.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_missing_amount_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "amount" of required type "BigInt!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_zero_amount_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.operatorParams.amount' => ['The recipients.0.operatorParams.amount is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_negative_amount_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => -1,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value -1 at "recipients[0].operatorParams.amount"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_amount_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].operatorParams.amount',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_amount_greater_than_token_balance_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => fake()->numberBetween($this->tokenAccount->balance + 1),
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipients.0.operatorParams.amount' => ['The recipients.0.operatorParams.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_keep_alive_in_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->recipient->public_key),
                    'operatorParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'source' => SS58Address::encode($this->wallet->public_key),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                        'keepAlive' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid" at "recipients[0].operatorParams.keepAlive"; Boolean cannot represent a non boolean value',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_signing_wallet(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => SS58Address::encode($this->wallet->public_key),
                    'simpleParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                    ],
                ],
            ],
            'signingAccount' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['signingAccount' => ['The signing account is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
