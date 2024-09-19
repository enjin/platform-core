<?php

namespace Enjin\Platform\Tests\Unit\Decoder;

use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Tests\TestCase;

class MintTest extends TestCase
{
    protected Codec $codec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
    }

    public function test_it_decodes_with_token_id_and_supply_other_defaults()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000000000000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_account_deposit_count()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012802093d000000000000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 1000000,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_cap_collapsing_supply()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f009101280001012800000000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => [
                        'type' => 'COLLAPSING_SUPPLY',
                        'amount' => '10',
                    ],
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_cap_supply()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000100821a060000000000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => [
                        'type' => 'SUPPLY',
                        'amount' => '100000',
                    ],
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_behavior_has_royalty()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000100bc068bc168aeb8e22637617e56f8c819ff2f16c49f00af5ee00a116a303979100208af2f000000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => [
                        'hasRoyalty' => [
                            'beneficiary' => '0xbc068bc168aeb8e22637617e56f8c819ff2f16c49f00af5ee00a116a30397910',
                            'percentage' => 20.0,
                        ],
                    ],
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_behavior_is_currency()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000101000000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => [
                        'isCurrency' => true,
                    ],
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_listing_forbidden()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000000010000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => true,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_freeze_state_permanent()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000000010000000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => 'PERMANENT',
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_freeze_state_temporary()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000000010100000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => 'TEMPORARY',
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_freeze_state_never()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000000010200000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => 'NEVER',
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_attribute()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000000000004106e616d651044656d6f000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [
                        [
                            'key' => 'name',
                            'value' => 'Demo',
                        ],
                    ],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_multiple_attributes()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000000000008106e616d651044656d6f2c6465736372697074696f6e4044656d6f206465736372697074696f6e000000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' =>  [
                        [
                            'key' => 'name',
                            'value' => 'Demo',
                        ],
                        [
                            'key' => 'description',
                            'value' => 'Demo description',
                        ],
                    ],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_infusion()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f009101280000000000000284d7170000000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '100000000',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_anyone_can_infuse()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000000000000000100000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => true,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_metadata_name()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000000000000001044656d6f000000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' =>  [
                        'name' => 'Demo',
                        'symbol' => null,
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_metadata_symbol()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f009101280000000000000000000c444d4f0000');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => 'DMO',
                        'decimalCount' => 0,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_metadata_decimal_count()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128000000000000000000000a00');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' => [
                        'name' => null,
                        'symbol' => null,
                        'decimalCount' => 10,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_all_metadata()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f0091012800000000000000001044656d6f0c444d4f0a00');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => null,
                    'behavior' => null,
                    'listingForbidden' => false,
                    'freezeState' => null,
                    'attributes' => [],
                    'accountDepositCount' => 0,
                    'infusion' => '0',
                    'anyoneCanInfuse' => false,
                    'metadata' =>  [
                        'name' => 'Demo',
                        'symbol' => 'DMO',
                        'decimalCount' => 10,
                    ],
                ],
            ],
        ], $data);
    }

    public function test_it_decodes_with_all_fields()
    {
        $data = $this->codec->decoder()->mint('0x280400b63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12411f00910128280100821a06000100bc068bc168aeb8e22637617e56f8c819ff2f16c49f00af5ee00a116a303979100208af2f01010108106e616d651044656d6f2c6465736372697074696f6e4044656d6f204465736372697074696f6e821a0600011044656d6f0c444d4f0a00');

        $this->assertEquals([
            'recipientId' => '0xb63ff0add4c15b95c6feedc900305b1f125c907c89149b5fd92f5eb4e5ea7c12',
            'collectionId' => '2000',
            'params' =>  [
                'CreateToken' =>  [
                    'tokenId' => '100',
                    'initialSupply' => '10',
                    'cap' => [
                        'type' => 'SUPPLY',
                        'amount' => '100000',
                    ],
                    'behavior' => [
                        'hasRoyalty' => [
                            'beneficiary' => '0xbc068bc168aeb8e22637617e56f8c819ff2f16c49f00af5ee00a116a30397910',
                            'percentage' => 20.0,
                        ],
                    ],
                    'listingForbidden' => true,
                    'freezeState' => 'TEMPORARY',
                    'attributes' =>  [
                        [
                            'key' => 'name',
                            'value' => 'Demo',
                        ],
                        [
                            'key' => 'description',
                            'value' => 'Demo Description',
                        ],
                    ],
                    'accountDepositCount' => 10,
                    'infusion' => '100000',
                    'anyoneCanInfuse' => true,
                    'metadata' =>  [
                        'name' => 'Demo',
                        'symbol' => 'DMO',
                        'decimalCount' => 10,
                    ],
                ],
            ],
        ], $data);
    }
}
