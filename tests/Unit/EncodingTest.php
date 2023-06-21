<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Models\Substrate\BurnParams;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\SimpleTransferParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Tests\TestCase;

class EncodingTest extends TestCase
{
    protected Codec $codec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
    }

    public function test_it_can_encode_transfer_balance()
    {
        $data = $this->codec->encode()->transferBalance(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '3256489678678963378387378312'
        );

        $callIndex = $this->codec->encode()->callIndexes['Balances.transfer'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e238860bfb2b3660b3783b4850a",
            $data
        );
    }

    public function test_it_can_encode_transfer_balance_keep_alive()
    {
        $data = $this->codec->encode()->transferBalanceKeepAlive(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '3256489678678963378387378312'
        );

        $callIndex = $this->codec->encode()->callIndexes['Balances.transfer_keep_alive'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e238860bfb2b3660b3783b4850a",
            $data
        );
    }

    public function test_it_can_encode_transfer_all_balance_with_keep_alive()
    {
        $data = $this->codec->encode()->transferAllBalance(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            true
        );

        $callIndex = $this->codec->encode()->callIndexes['Balances.transfer_all'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e01",
            $data
        );
    }

    public function test_it_can_encode_transfer_all_balance_without_keep_alive()
    {
        $data = $this->codec->encode()->transferAllBalance(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        );

        $callIndex = $this->codec->encode()->callIndexes['Balances.transfer_all'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00",
            $data
        );
    }

    public function test_it_can_encode_approve_collection_with_expiration()
    {
        $data = $this->codec->encode()->approveCollection(
            '2000',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            535000
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.approve_collection'];
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e01d8290800",
            $data
        );
    }

    public function test_it_can_approve_collection_without_expiration()
    {
        $data = $this->codec->encode()->approveCollection(
            '2000',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.approve_collection'];
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00",
            $data
        );
    }

    public function test_it_can_encode_unapprove_collection()
    {
        $data = $this->codec->encode()->unapproveCollection(
            '2000',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.unapprove_collection'];
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e",
            $data
        );
    }

    public function test_it_can_encode_approve_token_with_expiration()
    {
        $data = $this->codec->encode()->approveToken(
            '2000',
            '5050',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '10',
            '0',
            535000
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.approve_token'];
        $this->assertEquals(
            "0x{$callIndex}411fe94e52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e2801d829080000",
            $data
        );
    }

    public function test_it_can_encode_approve_token_without_expiration()
    {
        $data = $this->codec->encode()->approveToken(
            '2000',
            '57005',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '500',
            '10',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.approve_token'];
        $this->assertEquals(
            "0x{$callIndex}411fb67a030052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15ed1070028",
            $data
        );
    }

    public function test_it_can_encode_unapprove_token()
    {
        $data = $this->codec->encode()->unapproveToken(
            '2000',
            '5050',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.unapprove_token'];
        $this->assertEquals(
            "0x{$callIndex}411fe94e52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e",
            $data
        );
    }

    public function test_it_can_encode_batch_transfer_simple_transfer()
    {
        $recipient = [
            'accountId' => '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            'params' => new SimpleTransferParams(
                tokenId: '255',
                amount: '1',
                keepAlive: false,
            ),
        ];

        $data = $this->codec->encode()->batchTransfer(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.batch_transfer'];
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00fd030400",
            $data
        );
    }

    public function test_it_can_encode_batch_transfer_operator_transfer()
    {
        $recipient = [
            'accountId' => '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            'params' => new OperatorTransferParams(
                tokenId: '1',
                source: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                amount: '1',
                keepAlive: false,
            ),
        ];

        $data = $this->codec->encode()->batchTransfer(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.batch_transfer'];
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0104d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400",
            $data
        );
    }

    public function test_it_can_encode_simple_transfer()
    {
        $data = $this->codec->encode()->transferToken(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '2000',
            new SimpleTransferParams(
                tokenId: '255',
                amount: '1',
                keepAlive: false,
            ),
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.transfer'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f00fd030400",
            $data
        );
    }

    public function test_it_can_encode_operator_transfer()
    {
        $data = $this->codec->encode()->transferToken(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '2000',
            new OperatorTransferParams(
                tokenId: '255',
                source: 'rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ',
                amount: '1',
                keepAlive: false,
            ),
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.transfer'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f01fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400",
            $data
        );
    }

    public function test_it_can_encode_create_collection_with_args()
    {
        $mintPolicy = new MintPolicyParams(
            forceSingleMint: true,
            maxTokenCount: '255',
            maxTokenSupply: '57005',
        );

        $marketPolicy = new RoyaltyPolicyParams(
            beneficiary: '0x301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a',
            percentage: 20,
        );

        $explicitRoyaltyCurrencies = [
            [
                'collectionId' => '0',
                'tokenId' => '0',
            ],
        ];

        $attributes = [
            [
                'key' => 'name',
                'value' => 'Demo Collection',
            ],
            [
                'key' => 'description',
                'value' => 'My demo collection',
            ],
        ];

        $data = $this->codec->encode()->createCollection($mintPolicy, $marketPolicy, $explicitRoyaltyCurrencies, $attributes);

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.create_collection'];
        $this->assertEquals(
            "0x{$callIndex}01ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f04000008106e616d653c44656d6f20436f6c6c656374696f6e2c6465736372697074696f6e484d792064656d6f20636f6c6c656374696f6e",
            $data
        );
    }

    public function test_it_can_encode_create_collection_with_only_required_args()
    {
        $data = $this->codec->encode()->createCollection(new MintPolicyParams(forceSingleMint: true));

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.create_collection'];
        $this->assertEquals(
            "0x{$callIndex}000001000000",
            $data
        );
    }

    public function test_it_can_encode_destroy_collection()
    {
        $data = $this->codec->encode()->destroyCollection(
            collectionId: '57005'
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.destroy_collection'];
        $this->assertEquals(
            "0x{$callIndex}b67a0300",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_owner()
    {
        $data = $this->codec->encode()->mutateCollection(
            collectionId: '2000',
            owner: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_collection'];
        $this->assertEquals(
            "0x{$callIndex}411f0152e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_royalty()
    {
        $data = $this->codec->encode()->mutateCollection(
            collectionId: '2000',
            royalty: new RoyaltyPolicyParams(
                beneficiary: '0x301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a',
                percentage: 20,
            )
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_collection'];
        $this->assertEquals(
            "0x{$callIndex}411f000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f00",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_empty_royalties()
    {
        $data = $this->codec->encode()->mutateCollection(
            collectionId: '2000',
            royalty: []
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_collection'];
        $this->assertEquals(
            "0x{$callIndex}411f000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_royalty_equals_null()
    {
        $data = $this->codec->encode()->mutateCollection(
            collectionId: '57005',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_collection'];
        $this->assertEquals(
            "0x{$callIndex}b67a030000010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token()
    {
        $data = $this->codec->encode()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_token'];
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd0301000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_empty_behavior()
    {
        $data = $this->codec->encode()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
            behavior: []
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_token'];
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd03000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_listing_true()
    {
        $data = $this->codec->encode()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
            listingForbidden: true
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_token'];
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd030100010100",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_is_currency_true()
    {
        $data = $this->codec->encode()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
            behavior: new TokenMarketBehaviorParams(isCurrency: true)
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mutate_token'];
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd030101010000",
            $data
        );
    }

    /**
     * @test
     * @define-env usesCanaryNetwork
     */
    public function test_it_can_encode_batch_create_token_on_canary()
    {
        $recipient = [
            'accountId' => '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            'params' => new CreateTokenParams(
                tokenId: '255',
                initialSupply: '57005',
                unitPrice: '10000000000000',
                cap: TokenMintCapType::INFINITE,
                behavior: null,
                listingForbidden: null,
            ),
        ];

        $data = $this->codec->encode()->batchMint(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.batch_mint'];
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00fd03b67a0300000100a0724e180900000000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_batch_create_token()
    {
        $recipient = [
            'accountId' => '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            'params' => new CreateTokenParams(
                tokenId: '255',
                initialSupply: '57005',
                unitPrice: '10000000000000',
                cap: TokenMintCapType::INFINITE,
                behavior: null,
                listingForbidden: null,
            ),
        ];

        $data = $this->codec->encode()->batchMint(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.batch_mint'];
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00fd03b67a0300000100a0724e180900000000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_batch_mint_token()
    {
        $recipient = [
            'accountId' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
            'params' => new MintParams(
                tokenId: '1',
                amount: '1',
                unitPrice: '100000000000000000'
            ),
        ];

        $data = $this->codec->encode()->batchMint(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.batch_mint'];
        $this->assertEquals(
            "0x{$callIndex}411f04d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0104040100008a5d784563010000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_no_cap()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::INFINITE,
            behavior: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e180900000000000000000000000000000000",
            $data
        );
    }

    /**
     * @test
     * @define-env usesCanaryNetwork
     */
    public function test_it_can_encode_create_token_on_canary()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '1',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::INFINITE,
            behavior: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '2000',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f00fd0304000100a0724e180900000000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_cap_supply()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SUPPLY,
            supply: '57005',
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e1809000000000000000000000101b67a03000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_other_args_as_null()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::INFINITE,
            behavior: null,
            listingForbidden: null
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e180900000000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_supply_and_behavior_null()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SUPPLY,
            supply: '57005',
            behavior: null,
            listingForbidden: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e1809000000000000000000000101b67a03000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_no_listing_forbidden()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SINGLE_MINT,
            behavior: null,
            listingForbidden: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000001000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_no_behavior()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SINGLE_MINT,
            behavior: null,
            listingForbidden: false,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000001000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_listing_forbidden()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SINGLE_MINT,
            behavior: null,
            listingForbidden: true,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000001000001000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_no_cap()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::INFINITE,
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: 20,
                ),
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000000010052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0208af2f00000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_supply()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SUPPLY,
            supply: '57005',
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: '20',
                ),
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e1809000000000000000000000101b67a0300010052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0208af2f00000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_with_attributes()
    {
        $params = new CreateTokenParams(
            tokenId: '1010',
            initialSupply: '1',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::INFINITE,
            listingForbidden: true,
            attributes: [
                [
                    'key' => 'name',
                    'value' => 'Demo Token',
                ],
            ]
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '2000',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f00c90f04000100a0724e1809000000000000000000000000010004106e616d652844656d6f20546f6b656e00",
            $data
        );
    }

    public function test_it_can_encode_mint_with_single_mint()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SINGLE_MINT,
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: '20',
                ),
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e1809000000000000000000000100010052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0208af2f00000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_behavior_is_currency()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SINGLE_MINT,
            behavior: new TokenMarketBehaviorParams(
                isCurrency: true,
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e1809000000000000000000000100010100000000",
            $data
        );
    }

    public function test_it_can_encode_mint_no_args()
    {
        $params = new MintParams(
            tokenId: '255',
            amount: '57005',
            unitPrice: '10000000000000'
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}00d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0401fd03b67a03000100a0724e180900000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_cap_single_mint()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            unitPrice: '10000000000000',
            cap: TokenMintCapType::SINGLE_MINT,
        );

        $data = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.mint'];
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000001000000000000",
            $data
        );
    }

    public function test_it_can_encode_burn()
    {
        $data = $this->codec->encode()->burn(
            collectionId: '2000',
            params: new BurnParams(
                tokenId: '57005',
                amount: 100,
                keepAlive: true,
                removeTokenStorage: true
            )
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.burn'];
        $this->assertEquals(
            "0x{$callIndex}411fb67a030091010101",
            $data
        );
    }

    public function test_it_can_encode_freeze_collection()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::COLLECTION,
            token: null,
            account: null
        );

        $data = $this->codec->encode()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.freeze'];
        $this->assertEquals(
            "0x{$callIndex}b67a030000",
            $data
        );
    }

    /**
     * @test
     * @define-env usesCanaryNetwork
     */
    public function test_it_can_encode_freeze_token_on_canary()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN,
            token: '255',
            account: null
        );

        $data = $this->codec->encode()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.freeze'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff0000000000000000000000000000000101",
            $data
        );
    }

    public function test_it_can_encode_freeze_token()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN,
            token: '255',
            account: null
        );

        $data = $this->codec->encode()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.freeze'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff0000000000000000000000000000000101",
            $data
        );
    }

    public function test_it_can_encode_freeze_collection_account()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::COLLECTION_ACCOUNT,
            token: null,
            account: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d'
        );

        $data = $this->codec->encode()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.freeze'];
        $this->assertEquals(
            "0x{$callIndex}b67a030002d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
            $data
        );
    }

    public function test_it_can_encode_freeze_token_account()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN_ACCOUNT,
            token: '255',
            account: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d'
        );

        $data = $this->codec->encode()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.freeze'];
        $this->assertEquals(
            "0x{$callIndex}b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
            $data
        );
    }

    public function test_it_can_encode_thaw_collection()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::COLLECTION,
            token: null,
            account: null
        );

        $data = $this->codec->encode()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.thaw'];
        $this->assertEquals(
            "0x{$callIndex}b67a030000",
            $data
        );
    }

    /**
     * @test
     * @define-env usesCanaryNetwork
     */
    public function test_it_can_encode_thaw_token_on_canary()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN,
            token: '255',
            account: null
        );

        $data = $this->codec->encode()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.thaw'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff0000000000000000000000000000000101",
            $data
        );
    }

    public function test_it_can_encode_thaw_token()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN,
            token: '255',
            account: null
        );

        $data = $this->codec->encode()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.thaw'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff0000000000000000000000000000000101",
            $data
        );
    }

    public function test_it_can_encode_thaw_collection_account()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::COLLECTION_ACCOUNT,
            token: null,
            account: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d'
        );

        $data = $this->codec->encode()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.thaw'];
        $this->assertEquals(
            "0x{$callIndex}b67a030002d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
            $data
        );
    }

    public function test_it_can_encode_thaw_token_account()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN_ACCOUNT,
            token: '255',
            account: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d'
        );

        $data = $this->codec->encode()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.thaw'];
        $this->assertEquals(
            "0x{$callIndex}b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
            $data
        );
    }

    public function test_it_can_encode_set_attribute_from_token()
    {
        $data = $this->codec->encode()->setAttribute(
            '57005',
            '255',
            'name',
            'Golden Sword'
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.set_attribute'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff000000000000000000000000000000106e616d6530476f6c64656e2053776f7264",
            $data
        );
    }

    public function test_it_can_encode_remove_attribute_from_collection()
    {
        $data = $this->codec->encode()->removeAttribute(
            collectionId: '57005',
            tokenId: null,
            key: 'name',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.remove_attribute'];
        $this->assertEquals(
            "0x{$callIndex}b67a030000106e616d65",
            $data
        );
    }

    public function test_it_can_encode_remove_attribute_from_token()
    {
        $data = $this->codec->encode()->removeAttribute(
            collectionId: '57005',
            tokenId: '255',
            key: 'name',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.remove_attribute'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff000000000000000000000000000000106e616d65",
            $data
        );
    }

    public function test_it_can_encode_remove_all_attributes()
    {
        $data = $this->codec->encode()->removeAllAttributes(
            collectionId: '57005',
            tokenId: '255',
            attributeCount: '1',
        );

        $callIndex = $this->codec->encode()->callIndexes['MultiTokens.remove_all_attributes'];
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff00000000000000000000000000000001000000",
            $data
        );
    }

    public function test_it_can_encode_efinity_utility_batch()
    {
        $call = $this->codec->encode()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: new CreateTokenParams(
                tokenId: '255',
                initialSupply: '57005',
                unitPrice: '10000000000000',
                cap: TokenMintCapType::INFINITE,
                behavior: null,
                listingForbidden: null
            )
        );

        $data = $this->codec->encode()->batch(
            calls: [$call, $call],
            continueOnFailure: false,
        );

        $callIndex = $this->codec->encode()->callIndexes['EfinityUtility.batch'];
        $this->assertEquals(
            "0x{$callIndex}0828040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000000000000000028040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000000000000000000",
            $data
        );
    }
}
