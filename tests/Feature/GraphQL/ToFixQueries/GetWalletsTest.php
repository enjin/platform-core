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
use Enjin\Platform\Tests\Feature\GraphQL\Queries\Codec;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksSocketClient;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class GetWalletsTest extends TestCaseGraphQL
{
    use MocksSocketClient;

    protected string $method = 'GetWallets';

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

    public function test_it_can_get_wallets(): void
    {
        $this->mockNonceAndBalancesFor($this->wallet->public_key);

        $response = $this->graphql($this->method, ['ids' => [$this->wallet->id]]);
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql($this->method, ['externalIds' => [$this->wallet->external_id]]);
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql($this->method, ['verificationIds' => [$this->verification->verification_id]]);
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql($this->method, ['accounts' => [$this->wallet->public_key]]);
        $this->assertNotEmpty($response['totalCount']);
    }

    public function test_it_will_fail_with_invalid_id(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [$this->wallet->id],
            'externalIds' => [$this->wallet->external_id],
            'verificationIds' => [$this->verification->verification_id],
            'accounts' => [$this->wallet->public_key],
        ], true);
        $this->assertArrayContainsArray([
            'ids' => ['The ids field is prohibited.'],
            'externalIds' => ['The external ids field is prohibited.'],
            'verificationIds' => ['The verification ids field is prohibited.'],
            'accounts' => ['The accounts field is prohibited.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'ids' => SupportCollection::range(1, 200)->toArray(),
        ], true);
        $this->assertArrayContainsArray([
            'ids' => ['The ids field must not have more than 100 items.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'ids' => [Hex::MAX_UINT256 + 1],
        ], true);
        $this->assertEquals(
            'Variable "$ids" got invalid value 1.1579208923732E+77 at "ids[0]"; Int cannot represent non 32-bit signed integer value: 1.1579208923732E+77',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_external_id(): void
    {
        $response = $this->graphql($this->method, [
            'externalIds' => SupportCollection::range(1, 200)->map(fn ($val) => (string) $val)->toArray(),
        ], true);
        $this->assertArrayContainsArray([
            'externalIds' => ['The external ids field must not have more than 100 items.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'externalIds' => [Str::random(2000)],
        ], true);
        $this->assertArrayContainsArray([
            'externalIds.0' => ['The externalIds.0 field must not be greater than 1000 characters.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'externalIds' => [''],
        ], true);
        $this->assertArrayContainsArray([
            'externalIds.0' => ['The externalIds.0 field must have a value.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationIds' => SupportCollection::range(1, 200)->map(fn ($val) => (string) $val)->toArray(),
        ], true);

        $this->assertEquals(
            ['The verification ids field must not have more than 100 items.',
            ],
            $response['error']['verificationIds']
        );

        $response = $this->graphql($this->method, [
            'verificationIds' => [Str::random(2000)],
        ], true);
        $this->assertEquals([
            'verificationIds.0' => ['The verificationIds.0 field must not be greater than 1000 characters.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'verificationIds' => [''],
        ], true);
        $this->assertEquals([
            'verificationIds.0' => ['The verificationIds.0 field must have a value.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_accounts(): void
    {
        $response = $this->graphql($this->method, [
            'accounts' => SupportCollection::range(1, 200)->map(fn ($val) => (string) $val)->toArray(),
        ], true);
        $this->assertEquals(['The accounts field must not have more than 100 items.'], $response['error']['accounts']);

        $response = $this->graphql($this->method, [
            'accounts' => [Str::random(2000)],
        ], true);
        $this->assertArrayContainsArray([
            'accounts.0' => ['The accounts.0 field must not be greater than 255 characters.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'accounts' => [''],
        ], true);
        $this->assertArrayContainsArray([
            'accounts.0' => ['The accounts.0 field must have a value.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'accounts' => [Str::random(255)],
        ], true);
        $this->assertArrayContainsArray([
            'accounts.0' => ['The accounts.0 is not a valid substrate account.'],
        ], $response['error']);
    }

    protected function mockNonceAndBalancesFor(string $address): void
    {
        $this->mockWebsocketClient(
            'state_getStorage',
            [
                $this->codec->encoder()->systemAccountStorageKey($address),
            ],
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
