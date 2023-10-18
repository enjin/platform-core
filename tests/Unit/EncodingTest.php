<?php

namespace Enjin\Platform\Tests\Unit;

use Codec\Base;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
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

    public function test_it_can_encode_sequence_length()
    {
        $data = $this->codec->encoder()->sequenceLength('0x110022');
        $this->assertEquals('0x0c', $data);

        $data = $this->codec->encoder()->sequenceLength($hexText = HexConverter::stringToHexPrefixed(fake()->text()));
        $length = (new ScaleInstance(Base::create()))->createTypeByTypeString('Compact<u32>')->encode(count(HexConverter::hexToBytes($hexText)));

        $this->assertEquals(HexConverter::prefix($length), HexConverter::prefix($data));
    }

    public function test_it_can_add_a_fake_signature_to_a_call()
    {
        $call = $this->codec->encoder()->transferBalance(
            '0x3a158a287b46acd830ee9a83d304a63569f8669968a20ea80720e338a565dd09',
            '1000000000000000000'
        );

        $data = $this->codec->encoder()->addFakeSignature($call);

        $extraByte = '84';
        $signed = '003a158a287b46acd830ee9a83d304a63569f8669968a20ea80720e338a565dd09';
        $signature = '01a2369c177101204aede6d1992240c436a05084f1b56321a0851ed346cb2783704fd10cbfd10a423f1f7ab321761de08c3927c082236ed433a979b19ee09ed789';
        $era = '00';
        $nonce = '00';
        $tip = '00';
        $length = $this->codec->encoder()->sequenceLength(($fakeInfo = '0x' . $extraByte . $signed . $signature . $era . $nonce . $tip) . HexConverter::unPrefix($call));

        $this->assertEquals($length, substr($data, 0, 6));
        $this->assertEquals(HexConverter::unPrefix($call), substr($data, strlen('0x' . HexConverter::unPrefix($length) . HexConverter::unPrefix($fakeInfo))));
    }

    public function test_it_can_encode_transfer_balance()
    {
        $data = $this->codec->encoder()->transferBalance(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '3256489678678963378387378312'
        );

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e238860bfb2b3660b3783b4850a",
            $data
        );
    }

    public function test_it_can_encode_transfer_balance_keep_alive()
    {
        $data = $this->codec->encoder()->transferBalanceKeepAlive(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '3256489678678963378387378312'
        );

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_keep_alive', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e238860bfb2b3660b3783b4850a",
            $data
        );
    }

    public function test_it_can_encode_transfer_all_balance_with_keep_alive()
    {
        $data = $this->codec->encoder()->transferAllBalance(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            true
        );

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_all', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e01",
            $data
        );
    }

    public function test_it_can_encode_transfer_all_balance_without_keep_alive()
    {
        $data = $this->codec->encoder()->transferAllBalance(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        );

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_all', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00",
            $data
        );
    }

    public function test_it_can_encode_approve_collection_with_expiration()
    {
        $data = $this->codec->encoder()->approveCollection(
            '2000',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            535000
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e01d8290800",
            $data
        );
    }

    public function test_it_can_approve_collection_without_expiration()
    {
        $data = $this->codec->encoder()->approveCollection(
            '2000',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00",
            $data
        );
    }

    public function test_it_can_encode_unapprove_collection()
    {
        $data = $this->codec->encoder()->unapproveCollection(
            '2000',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.unapprove_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e",
            $data
        );
    }

    public function test_it_can_encode_approve_token_with_expiration()
    {
        $data = $this->codec->encoder()->approveToken(
            '2000',
            '5050',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '10',
            '0',
            535000
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_token', true);
        $this->assertEquals(
            "0x{$callIndex}411fe94e52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e2801d829080000",
            $data
        );
    }

    public function test_it_can_encode_approve_token_without_expiration()
    {
        $data = $this->codec->encoder()->approveToken(
            '2000',
            '57005',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '500',
            '10',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_token', true);
        $this->assertEquals(
            "0x{$callIndex}411fb67a030052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15ed1070028",
            $data
        );
    }

    public function test_it_can_encode_unapprove_token()
    {
        $data = $this->codec->encoder()->unapproveToken(
            '2000',
            '5050',
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.unapprove_token', true);
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

        $data = $this->codec->encoder()->batchTransfer(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_transfer', true);
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

        $data = $this->codec->encoder()->batchTransfer(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_transfer', true);
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0104d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400",
            $data
        );
    }

    public function test_it_can_encode_simple_transfer()
    {
        $data = $this->codec->encoder()->transferToken(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '2000',
            new SimpleTransferParams(
                tokenId: '255',
                amount: '1',
                keepAlive: false,
            ),
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.transfer', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f00fd030400",
            $data
        );
    }

    public function test_it_can_encode_operator_transfer()
    {
        $data = $this->codec->encoder()->transferToken(
            '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            '2000',
            new OperatorTransferParams(
                tokenId: '255',
                source: 'rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ',
                amount: '1',
                keepAlive: false,
            ),
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.transfer', true, true);
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

        $data = $this->codec->encoder()->createCollection($mintPolicy, $marketPolicy, $explicitRoyaltyCurrencies, $attributes);

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.create_collection', true);
        $this->assertEquals(
            "0x{$callIndex}01ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f04000008106e616d653c44656d6f20436f6c6c656374696f6e2c6465736372697074696f6e484d792064656d6f20636f6c6c656374696f6e",
            $data
        );
    }

    public function test_it_can_encode_create_collection_with_only_required_args()
    {
        $data = $this->codec->encoder()->createCollection(new MintPolicyParams(forceSingleMint: true));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.create_collection', true);
        $this->assertEquals(
            "0x{$callIndex}000001000000",
            $data
        );
    }

    public function test_it_can_encode_destroy_collection()
    {
        $data = $this->codec->encoder()->destroyCollection(
            collectionId: '57005'
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.destroy_collection', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_owner()
    {
        $data = $this->codec->encoder()->mutateCollection(
            collectionId: '2000',
            owner: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f0152e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_royalty()
    {
        $data = $this->codec->encoder()->mutateCollection(
            collectionId: '2000',
            royalty: new RoyaltyPolicyParams(
                beneficiary: '0x301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a',
                percentage: 20,
            )
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f00",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_empty_royalties()
    {
        $data = $this->codec->encoder()->mutateCollection(
            collectionId: '2000',
            royalty: []
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_royalty_equals_null()
    {
        $data = $this->codec->encoder()->mutateCollection(
            collectionId: '57005',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030000010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token()
    {
        $data = $this->codec->encoder()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd0301000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_empty_behavior()
    {
        $data = $this->codec->encoder()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
            behavior: []
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd03000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_listing_true()
    {
        $data = $this->codec->encoder()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
            listingForbidden: true
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd030100010100",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_is_currency_true()
    {
        $data = $this->codec->encoder()->mutateToken(
            collectionId: '57005',
            tokenId: '255',
            behavior: new TokenMarketBehaviorParams(isCurrency: true)
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd030101010000",
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
                cap: TokenMintCapType::INFINITE,
                unitPrice: '10000000000000',
                behavior: null,
                listingForbidden: null,
            ),
        ];

        $data = $this->codec->encoder()->batchMint(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_mint', true);
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

        $data = $this->codec->encoder()->batchMint(
            '2000',
            [$recipient]
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_mint', true);
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
            cap: TokenMintCapType::INFINITE,
            unitPrice: '10000000000000',
            behavior: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e180900000000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_cap_supply()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            cap: TokenMintCapType::SUPPLY,
            unitPrice: '10000000000000',
            supply: '57005',
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e1809000000000000000000000101b67a03000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_no_unit_price()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            cap: TokenMintCapType::INFINITE,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a03000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_with_freeze_state()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            cap: TokenMintCapType::INFINITE,
            freezeState: FreezeStateType::PERMANENT
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000000000001000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_other_args_as_null()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            cap: TokenMintCapType::INFINITE,
            unitPrice: '10000000000000',
            behavior: null,
            listingForbidden: null
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SUPPLY,
            unitPrice: '10000000000000',
            supply: '57005',
            behavior: null,
            listingForbidden: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SINGLE_MINT,
            unitPrice: '10000000000000',
            behavior: null,
            listingForbidden: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SINGLE_MINT,
            unitPrice: '10000000000000',
            behavior: null,
            listingForbidden: false,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SINGLE_MINT,
            unitPrice: '10000000000000',
            behavior: null,
            listingForbidden: true,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::INFINITE,
            unitPrice: '10000000000000',
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: 20,
                ),
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SUPPLY,
            unitPrice: '10000000000000',
            supply: '57005',
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: '20',
                ),
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::INFINITE,
            unitPrice: '10000000000000',
            listingForbidden: true,
            attributes: [
                [
                    'key' => 'name',
                    'value' => 'Demo Token',
                ],
            ]
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '2000',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SINGLE_MINT,
            unitPrice: '10000000000000',
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: '20',
                ),
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SINGLE_MINT,
            unitPrice: '10000000000000',
            behavior: new TokenMarketBehaviorParams(
                isCurrency: true,
            ),
            listingForbidden: null,
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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

        $data = $this->codec->encoder()->mint(
            recipientId: '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
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
            cap: TokenMintCapType::SINGLE_MINT,
            unitPrice: '10000000000000',
        );

        $data = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000001000000000000",
            $data
        );
    }

    public function test_it_can_encode_burn()
    {
        $data = $this->codec->encoder()->burn(
            collectionId: '2000',
            params: new BurnParams(
                tokenId: '57005',
                amount: 100,
                keepAlive: true,
                removeTokenStorage: true
            )
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.burn', true);
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

        $data = $this->codec->encoder()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.freeze', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030000",
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

        $data = $this->codec->encoder()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.freeze', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff00000000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_freeze_token_with_freeze_state()
    {
        $params = new FreezeTypeParams(
            type: FreezeType::TOKEN,
            token: '255',
            account: null,
            freezeState: FreezeStateType::PERMANENT
        );

        $data = $this->codec->encoder()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.freeze', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff0000000000000000000000000000000100",
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

        $data = $this->codec->encoder()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.freeze', true);
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

        $data = $this->codec->encoder()->freeze(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.freeze', true);
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

        $data = $this->codec->encoder()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.thaw', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030000",
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

        $data = $this->codec->encoder()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.thaw', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff00000000000000000000000000000000",
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

        $data = $this->codec->encoder()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.thaw', true);
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

        $data = $this->codec->encoder()->thaw(
            collectionId: '57005',
            params: $params
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.thaw', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
            $data
        );
    }

    public function test_it_can_encode_set_attribute_from_token()
    {
        $data = $this->codec->encoder()->setAttribute(
            '57005',
            '255',
            'name',
            'Golden Sword'
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.set_attribute', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff000000000000000000000000000000106e616d6530476f6c64656e2053776f7264",
            $data
        );
    }

    public function test_it_can_encode_remove_attribute_from_collection()
    {
        $data = $this->codec->encoder()->removeAttribute(
            collectionId: '57005',
            tokenId: null,
            key: 'name',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.remove_attribute', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030000106e616d65",
            $data
        );
    }

    public function test_it_can_encode_remove_attribute_from_token()
    {
        $data = $this->codec->encoder()->removeAttribute(
            collectionId: '57005',
            tokenId: '255',
            key: 'name',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.remove_attribute', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff000000000000000000000000000000106e616d65",
            $data
        );
    }

    public function test_it_can_encode_remove_all_attributes()
    {
        $data = $this->codec->encoder()->removeAllAttributes(
            collectionId: '57005',
            tokenId: '255',
            attributeCount: '1',
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.remove_all_attributes', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff00000000000000000000000000000001000000",
            $data
        );
    }

    public function test_it_can_encode_efinity_utility_batch()
    {
        $call = $this->codec->encoder()->mint(
            recipientId: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            params: new CreateTokenParams(
                tokenId: '255',
                initialSupply: '57005',
                cap: TokenMintCapType::INFINITE,
                unitPrice: '10000000000000',
                behavior: null,
                listingForbidden: null
            )
        );

        $data = $this->codec->encoder()->batch(
            calls: [$call, $call],
            continueOnFailure: false,
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MatrixUtility.batch', true);
        $this->assertEquals(
            "0x{$callIndex}0828040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000000000000000028040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100a0724e18090000000000000000000000000000000000",
            $data
        );
    }
}
