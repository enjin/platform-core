<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class GetWalletsTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'GetWallets';

    protected Codec $codec;

    protected Model $verification;
    protected Model $wallet;
    protected Model $transaction;

    protected Model $anotherWallet;
    protected Model $approvedWallet;

    protected Model $collection;
    protected Model $collectionAccount;
    protected Model $collectionAccountApproval;
    protected Model $collectionAttribute;

    protected Model $token;
    protected Model $tokenAccount;
    protected Model $tokenAccountApproval;
    protected Model $tokenAccountNamedReserve;
    protected Model $tokenAttribute;

    protected Model $anotherCollection;
    protected Model $anotherCollectionAccount;
    protected Model $anotherCollectionAccountApprovedToWallet;

    protected Model $anotherToken;
    protected Model $anotherTokenAccount;
    protected Model $anotherTokenAccountApprovedToWallet;

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
        $this->assertArraySubset([
            'ids' => ['The ids field is prohibited.'],
            'externalIds' => ['The external ids field is prohibited.'],
            'verificationIds' => ['The verification ids field is prohibited.'],
            'accounts' => ['The accounts field is prohibited.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'ids' => SupportCollection::range(1, 200)->toArray(),
        ], true);
        $this->assertArraySubset([
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
        $this->assertArraySubset([
            'externalIds' => ['The external ids field must not have more than 100 items.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'externalIds' => [Str::random(2000)],
        ], true);
        $this->assertArraySubset([
            'externalIds.0' => ['The externalIds.0 field must not be greater than 1000 characters.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'externalIds' => [''],
        ], true);
        $this->assertArraySubset([
            'externalIds.0' => ['The externalIds.0 field must have a value.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_verification_id(): void
    {
        $response = $this->graphql($this->method, [
            'verificationIds' => SupportCollection::range(1, 200)->map(fn ($val) => (string) $val)->toArray(),
        ], true);
        $this->assertArraySubset([
            'verificationIds' => ['The verification ids field must not have more than 100 items.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'verificationIds' => [Str::random(2000)],
        ], true);
        $this->assertArraySubset([
            'verificationIds.0' => ['The verificationIds.0 field must not be greater than 1000 characters.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'verificationIds' => [''],
        ], true);
        $this->assertArraySubset([
            'verificationIds.0' => ['The verificationIds.0 field must have a value.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_accounts(): void
    {
        $response = $this->graphql($this->method, [
            'accounts' => SupportCollection::range(1, 200)->map(fn ($val) => (string) $val)->toArray(),
        ], true);
        $this->assertArraySubset([
            'accounts' => ['The accounts field must not have more than 100 items.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'accounts' => [Str::random(2000)],
        ], true);
        $this->assertArraySubset([
            'accounts.0' => ['The accounts.0 field must not be greater than 255 characters.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'accounts' => [''],
        ], true);
        $this->assertArraySubset([
            'accounts.0' => ['The accounts.0 field must have a value.'],
        ], $response['error']);

        $response = $this->graphql($this->method, [
            'accounts' => [Str::random(255)],
        ], true);
        $this->assertArraySubset([
            'accounts.0' => ['The accounts.0 is not a valid substrate account.'],
        ], $response['error']);
    }
}
