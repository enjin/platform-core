<?php

namespace Enjin\Platform\Tests\Unit\Encoder;

use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\CreateTokenMutation;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Tests\TestCase;

class MintTest extends TestCase
{
    protected Codec $codec;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
    }

    public function test_it_can_encode_create_token_with_id_and_supply()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_with_supply_cap()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            cap: TokenMintCapType::SUPPLY,
            capSupply: '57005',
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000100b67a030000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_with_freeze_state()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            freezeState: FreezeStateType::PERMANENT
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a030000000000010000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_with_collapsing_supply()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            cap: TokenMintCapType::COLLAPSING_SUPPLY,
            capSupply: '57005',
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000101b67a030000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_listing_forbidden_false()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            listingForbidden: false,
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000000000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_listing_forbidden_true()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            listingForbidden: true,
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a0300000000010000000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_behavior_has_royalty()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            behavior: new TokenMarketBehaviorParams(
                hasRoyalty: new RoyaltyPolicyParams(
                    beneficiary: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
                    percentage: 20,
                ),
            ),
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a03000000010052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0208af2f000000000000000000",
            $data
        );
    }

    public function test_it_can_encode_create_token_with_attributes()
    {
        $params = new CreateTokenParams(
            tokenId: '1010',
            initialSupply: '1',
            attributes: [
                [
                    'key' => 'name',
                    'value' => 'Demo Token',
                ],
            ]
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '2000',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e411f00c90f04000000000004106e616d652844656d6f20546f6b656e000000000000",
            $data
        );
    }

    public function test_it_can_encode_mint_with_behavior_is_currency()
    {
        $params = new CreateTokenParams(
            tokenId: '255',
            initialSupply: '57005',
            behavior: new TokenMarketBehaviorParams(
                isCurrency: true,
            ),
        );

        $data = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e',
            collectionId: '1',
            createTokenParams: $params
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('MultiTokens.mint', true);
        $this->assertEquals(
            "0x{$callIndex}0052e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e0400fd03b67a030000000101000000000000000000",
            $data
        );
    }
}
