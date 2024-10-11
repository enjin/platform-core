<?php

namespace Enjin\Platform\Tests\Unit;

use Codec\Base;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\ApproveCollectionMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\ApproveTokenMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchMintMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchTransferBalanceMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchTransferMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BurnMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\CreateCollectionMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\CreateTokenMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\DestroyCollectionMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\FreezeMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\MutateCollectionMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\MutateTokenMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\OperatorTransferTokenMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveAllAttributesMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveCollectionAttributeMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveTokenAttributeMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\SetTokenAttributeMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\SimpleTransferTokenMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\ThawMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\TransferAllBalanceMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\TransferBalanceMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\UnapproveCollectionMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\UnapproveTokenMutation;
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
        $call = TransactionSerializer::encode('TransferKeepAlive', TransferBalanceMutation::getEncodableParams(
            recipientAccount: '0x3a158a287b46acd830ee9a83d304a63569f8669968a20ea80720e338a565dd09',
            value: '1000000000000000000'
        ));

        $data = $this->codec->encoder()->addFakeSignature($call);

        $extraByte = '84';
        $signed = '003a158a287b46acd830ee9a83d304a63569f8669968a20ea80720e338a565dd09';
        $signature = '01a2369c177101204aede6d1992240c436a05084f1b56321a0851ed346cb2783704fd10cbfd10a423f1f7ab321761de08c3927c082236ed433a979b19ee09ed789';
        $era = '00';
        $nonce = '00';
        $tip = '00';
        $mode = '00';
        $length = $this->codec->encoder()->sequenceLength(($fakeInfo = '0x' . $extraByte . $signed . $signature . $era . $nonce . $tip . $mode) . HexConverter::unPrefix($call));

        $this->assertEquals($length, substr($data, 0, 6));
        $this->assertEquals(HexConverter::unPrefix($call), substr($data, strlen('0x' . HexConverter::unPrefix($length) . HexConverter::unPrefix($fakeInfo))));
    }

    public function test_it_can_encode_transfer_balance()
    {
        $data = TransactionSerializer::encode('TransferKeepAlive', TransferBalanceMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            value: '3256489678678963378387378312'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_keep_alive', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e238860bfb2b3660b3783b4850a",
            $data
        );
    }

    public function test_it_can_encode_transfer_balance_keep_alive()
    {
        $data = TransactionSerializer::encode('TransferKeepAlive', TransferBalanceMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            value: '3256489678678963378387378312'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_keep_alive', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e238860bfb2b3660b3783b4850a",
            $data
        );
    }

    public function test_it_can_encode_batch_transfer_balance()
    {
        $data = TransactionSerializer::encode('Batch', BatchTransferBalanceMutation::getEncodableParams(
            recipients: [
                [
                    'accountId' => '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    'keepAlive' => true,
                    'value' => 1,
                ],
                [
                    'accountId' => '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    'keepAlive' => false,
                    'value' => 2,
                ],
            ],
            continueOnFailure: true,
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MatrixUtility.batch', true);
        $this->assertEquals(
            "0x{$callIndex}080a030052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e040a000052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0801",
            $data
        );
    }

    public function test_it_can_encode_transfer_all_balance_with_keep_alive()
    {
        $data = TransactionSerializer::encode('TransferAllBalance', TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            keepAlive: true
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_all', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e01",
            $data
        );
    }

    public function test_it_can_encode_transfer_all_balance_without_keep_alive()
    {
        $data = TransactionSerializer::encode('TransferAllBalance', TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Balances.transfer_all', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00",
            $data
        );
    }

    public function test_it_can_encode_approve_collection_with_expiration()
    {
        $data = TransactionSerializer::encode('ApproveCollection', ApproveCollectionMutation::getEncodableParams(
            collectionId: '2000',
            operator: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            expiration: 535000
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e01d8290800",
            $data
        );
    }

    public function test_it_can_approve_collection_without_expiration()
    {
        $data = TransactionSerializer::encode('ApproveCollection', ApproveCollectionMutation::getEncodableParams(
            collectionId: '2000',
            operator: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00",
            $data
        );
    }

    public function test_it_can_encode_unapprove_collection()
    {
        $data = TransactionSerializer::encode('UnapproveCollection', UnapproveCollectionMutation::getEncodableParams(
            collectionId: '2000',
            operator: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.unapprove_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e",
            $data
        );
    }

    public function test_it_can_encode_approve_token_with_expiration()
    {
        $data = TransactionSerializer::encode('ApproveToken', ApproveTokenMutation::getEncodableParams(
            collectionId: '2000',
            tokenId: '5050',
            operator: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            amount: '10',
            currentAmount: '0',
            expiration: 535000
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_token', true);
        $this->assertEquals(
            "0x{$callIndex}411fe94e52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e2801d829080000",
            $data
        );
    }

    public function test_it_can_encode_approve_token_without_expiration()
    {
        $data = TransactionSerializer::encode('ApproveToken', ApproveTokenMutation::getEncodableParams(
            collectionId: '2000',
            tokenId: '57005',
            operator: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            amount: '500',
            currentAmount: '10'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.approve_token', true);
        $this->assertEquals(
            "0x{$callIndex}411fb67a030052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15ed1070028",
            $data
        );
    }

    public function test_it_can_encode_unapprove_token()
    {
        $data = TransactionSerializer::encode('UnapproveToken', UnapproveTokenMutation::getEncodableParams(
            collectionId: '2000',
            tokenId: '5050',
            operator: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        ));

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
            ),
        ];

        $data = TransactionSerializer::encode('BatchTransfer', BatchTransferMutation::getEncodableParams(
            collectionId: '2000',
            recipients: [$recipient]
        ));

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
            ),
        ];

        $data = TransactionSerializer::encode('BatchTransfer', BatchTransferMutation::getEncodableParams(
            collectionId: '2000',
            recipients: [$recipient]
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_transfer', true);
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0104d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400",
            $data
        );
    }

    public function test_it_can_encode_simple_transfer()
    {
        $data = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '2000',
            simpleTransferParams: new SimpleTransferParams(
                tokenId: '255',
                amount: '1',
            ),
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.transfer', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f00fd030400",
            $data
        );
    }

    public function test_it_can_encode_operator_transfer()
    {
        $data = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '2000',
            operatorTransferParams: new OperatorTransferParams(
                tokenId: '255',
                source: 'rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ',
                amount: '1',
            ),
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.transfer', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f01fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400",
            $data
        );
    }

    public function test_it_can_encode_create_collection_with_args()
    {
        $mintPolicy = new MintPolicyParams(
            forceCollapsingSupply: true,
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

        $data = TransactionSerializer::encode('CreateCollection', CreateCollectionMutation::getEncodableParams(
            mintPolicy: $mintPolicy,
            marketPolicy: $marketPolicy,
            explicitRoyaltyCurrencies: $explicitRoyaltyCurrencies,
            attributes: $attributes
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.create_collection', true);
        $this->assertEquals(
            "0x{$callIndex}01ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f0004000008106e616d653c44656d6f20436f6c6c656374696f6e2c6465736372697074696f6e484d792064656d6f20636f6c6c656374696f6e",
            $data
        );
    }

    public function test_it_can_encode_create_collection_with_only_required_args()
    {
        $data = TransactionSerializer::encode('CreateCollection', CreateCollectionMutation::getEncodableParams(
            mintPolicy: new MintPolicyParams(forceCollapsingSupply: true)
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.create_collection', true);
        $this->assertEquals(
            "0x{$callIndex}00000100000000",
            $data
        );
    }

    public function test_it_can_encode_destroy_collection()
    {
        $data = TransactionSerializer::encode('DestroyCollection', DestroyCollectionMutation::getEncodableParams(
            collectionId: '57005'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.destroy_collection', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_owner()
    {
        $data = TransactionSerializer::encode('MutateCollection', MutateCollectionMutation::getEncodableParams(
            collectionId: '2000',
            owner: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f0152e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_royalty()
    {
        $data = TransactionSerializer::encode('MutateCollection', MutateCollectionMutation::getEncodableParams(
            collectionId: '2000',
            royalty: new RoyaltyPolicyParams(
                beneficiary: '0x301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a',
                percentage: 20,
            )
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f00",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_empty_royalties()
    {
        $data = TransactionSerializer::encode('MutateCollection', MutateCollectionMutation::getEncodableParams(
            collectionId: '2000',
            royalty: []
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}411f000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_collection_with_royalty_equals_null()
    {
        $data = TransactionSerializer::encode('MutateCollection', MutateCollectionMutation::getEncodableParams(
            collectionId: '57005',
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_collection', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030000010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token()
    {
        $data = TransactionSerializer::encode('MutateToken', MutateTokenMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd030100000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_empty_behavior()
    {
        $data = TransactionSerializer::encode('MutateToken', MutateTokenMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
            behavior: []
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd0300000000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_listing_true()
    {
        $data = TransactionSerializer::encode('MutateToken', MutateTokenMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
            listingForbidden: true
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd03010001010000",
            $data
        );
    }

    public function test_it_can_encode_mutate_token_with_is_currency_true()
    {
        $data = TransactionSerializer::encode('MutateToken', MutateTokenMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
            behavior: new TokenMarketBehaviorParams(isCurrency: true)
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mutate_token', true);
        $this->assertEquals(
            "0x{$callIndex}b67a0300fd03010101000000",
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
            ),
        ];

        $data = TransactionSerializer::encode('BatchMint', BatchMintMutation::getEncodableParams(
            collectionId: '2000',
            recipients: [$recipient]
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_mint', true);
        $this->assertEquals(
            "0x{$callIndex}411f0452e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e00fd03b67a0300000000000000000000000000",
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
            ),
        ];

        $data = TransactionSerializer::encode('BatchMint', BatchMintMutation::getEncodableParams(
            collectionId: '2000',
            recipients: [$recipient]
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.batch_mint', true);
        $this->assertEquals(
            "0x{$callIndex}411f04d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d01040400",
            $data
        );
    }

    public function test_it_can_encode_burn()
    {
        $data = TransactionSerializer::encode('Burn', BurnMutation::getEncodableParams(
            collectionId: '2000',
            burnParams: new BurnParams(
                tokenId: '57005',
                amount: 100,
                removeTokenStorage: true
            )
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.burn', true);
        $this->assertEquals(
            "0x{$callIndex}411fb67a0300910101",
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

        $data = TransactionSerializer::encode('Freeze', FreezeMutation::getEncodableParams(
            collectionId: '57005',
            freezeParams: $params
        ));

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

        $data = TransactionSerializer::encode('Freeze', FreezeMutation::getEncodableParams(
            collectionId: '57005',
            freezeParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.freeze', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff0000000000000000000000000000000101",
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

        $data = TransactionSerializer::encode('Freeze', FreezeMutation::getEncodableParams(
            collectionId: '57005',
            freezeParams: $params
        ));

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

        $data = TransactionSerializer::encode('Freeze', FreezeMutation::getEncodableParams(
            collectionId: '57005',
            freezeParams: $params
        ));

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

        $data = TransactionSerializer::encode('Freeze', FreezeMutation::getEncodableParams(
            collectionId: '57005',
            freezeParams: $params
        ));

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

        $data = TransactionSerializer::encode('Thaw', ThawMutation::getEncodableParams(
            collectionId: '57005',
            thawParams: $params
        ));

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

        $data = TransactionSerializer::encode('Thaw', ThawMutation::getEncodableParams(
            collectionId: '57005',
            thawParams: $params
        ));

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

        $data = TransactionSerializer::encode('Thaw', ThawMutation::getEncodableParams(
            collectionId: '57005',
            thawParams: $params
        ));

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

        $data = TransactionSerializer::encode('Thaw', ThawMutation::getEncodableParams(
            collectionId: '57005',
            thawParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.thaw', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
            $data
        );
    }

    public function test_it_can_encode_set_attribute_from_token()
    {
        $data = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
            key: 'name',
            value: 'Golden Sword'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.set_attribute', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff000000000000000000000000000000106e616d6530476f6c64656e2053776f726400",
            $data
        );
    }

    public function test_it_can_encode_remove_attribute_from_collection()
    {
        $data = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: null,
            key: 'name',
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.remove_attribute', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030000106e616d65",
            $data
        );
    }

    public function test_it_can_encode_remove_attribute_from_token()
    {
        $data = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
            key: 'name',
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.remove_attribute', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff000000000000000000000000000000106e616d65",
            $data
        );
    }

    public function test_it_can_encode_remove_all_attributes()
    {
        $data = TransactionSerializer::encode('RemoveAllAttributes', RemoveAllAttributesMutation::getEncodableParams(
            collectionId: '57005',
            tokenId: '255',
            attributeCount: '1',
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.remove_all_attributes', true);
        $this->assertEquals(
            "0x{$callIndex}b67a030001ff00000000000000000000000000000001000000",
            $data
        );
    }

    public function test_it_can_encode_matrix_utility_batch()
    {
        $call = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: new CreateTokenParams(
                tokenId: '255',
                initialSupply: '57005',
                cap: null,
                behavior: null,
                listingForbidden: null
            )
        ));

        $data = $this->codec->encoder()->batch(
            calls: [$call, $call],
            continueOnFailure: false,
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MatrixUtility.batch', true);
        $this->assertEquals(
            "0x{$callIndex}0828040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a030000000000000000000000000028040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a030000000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_matrix_utility_batch_with_continue_on_failure()
    {
        $call = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: new CreateTokenParams(
                tokenId: '255',
                initialSupply: '57005',
                cap: null,
                behavior: null,
                listingForbidden: null
            )
        ));

        $data = $this->codec->encoder()->batch(
            calls: [$call, $call],
            continueOnFailure: true,
        );

        $callIndex = $this->codec->encoder()->getCallIndex('MatrixUtility.batch', true);
        $this->assertEquals(
            "0x{$callIndex}0828040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a030000000000000000000000000028040052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a030000000000000000000000000001",
            $data
        );
    }
}
