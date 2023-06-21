<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Tests\TestCase;

final class DecodingTest extends TestCase
{
    protected Codec $codec;

    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
    }

    public function test_it_can_decode_system_account()
    {
        $data = $this->codec->decode()->systemAccount('0x1f00000000000000010000000000000000424ed9cbe55f0b91010000000000000000e65e4b9feedf56000000000000000000000000000000000000000000000000000000000000000000000000000000');

        $this->assertEquals(
            [
                'nonce' => 31,
                'consumers' => 0,
                'providers' => 1,
                'sufficients' => 0,
                'balances' => [
                    'free' => '7397963999878421824000',
                    'reserved' => '1602556000000000000000',
                    'miscFrozen' => '0',
                    'feeFrozen' => '0',
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_create_collection()
    {
        $data = $this->codec->decode()->createCollection('0x280001ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f');

        $this->assertEquals(
            [
                'mintPolicy' => [
                    'maxTokenCount' => '255',
                    'maxTokenSupply' => '57005',
                    'forceSingleMint' => true,
                ],
                'marketPolicy' => [
                    'beneficiary' => '0x301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a',
                    'percentage' => 20.0,
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_create_collection_other()
    {
        $data = $this->codec->decode()->createCollection('0x280001ff0000000000000001adde00000000000000000000000000000100');

        $this->assertEquals(
            [
                'mintPolicy' => [
                    'maxTokenCount' => '255',
                    'maxTokenSupply' => '57005',
                    'forceSingleMint' => true,
                ],
                'marketPolicy' => null,
            ],
            $data
        );

        $data = $this->codec->decode()->createCollection('0x280001ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f');

        $this->assertEquals(
            [
                'mintPolicy' => [
                    'maxTokenCount' => '255',
                    'maxTokenSupply' => '57005',
                    'forceSingleMint' => true,
                ],
                'marketPolicy' => [
                    'beneficiary' => '0x301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a',
                    'percentage' => 20.0,
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_destroy_collection()
    {
        $data = $this->codec->decode()->destroyCollection('0x2801b67a0300');

        $this->assertEquals(
            [
                'collectionId' => '57005',
            ],
            $data
        );
    }

    public function test_it_can_decode_create_token()
    {
        $data = $this->codec->decode()->mint('0x280300d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400fd03b67a03000f0000c16ff286230101b67a030000');

        $this->assertEquals(
            [
                'recipientId' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                'collectionId' => '1',
                'params' => [
                    'CreateToken' => [
                        'tokenId' => '255',
                        'initialSupply' => '57005',
                        'unitPrice' => '10000000000000000',
                        'cap' => [
                            'type' => 'SUPPLY',
                            'amount' => '57005',
                        ],
                        'behavior' => null,
                        'listingForbidden' => false,
                        'attributes' => [],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_create_token_with_attributes()
    {
        $data = $this->codec->decode()->mint('0x2804000824170e49c79846e7c0931e64df98605d93fa5f2cd42fec85ce045321071614411f00c90f0413000064a7b3b6e00d0001008eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48025a62020104106e616d652844656d6f20546f6b656e');

        $this->assertEquals(
            [
                'recipientId' => '0x0824170e49c79846e7c0931e64df98605d93fa5f2cd42fec85ce045321071614',
                'collectionId' => '2000',
                'params' => [
                    'CreateToken' => [
                        'tokenId' => '1010',
                        'initialSupply' => '1',
                        'unitPrice' => '1000000000000000000',
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                            'amount' => null,
                        ],
                        'behavior' => [
                            'hasRoyalty' => [
                                'beneficiary' => '0x8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48',
                                'percentage' => 1.0,
                            ],
                        ],
                        'listingForbidden' => true,
                        'attributes' => [
                            [
                                'key' => 'name',
                                'value' => 'Demo Token',
                            ],
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_mint()
    {
        $data = $this->codec->decode()->mint('0x280300d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0401fd03b67a0300010000c16ff28623000000000000000000');

        $this->assertEquals(
            [
                'recipientId' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                'collectionId' => '1',
                'params' => [
                    'Mint' => [
                        'tokenId' => '255',
                        'amount' => '57005',
                        'unitPrice' => '10000000000000000',
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_mint_other()
    {
        $data = $this->codec->decode()->mint('0x280300d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0400fd03b67a03000f0000c16ff286230101b67a030000');

        $this->assertEquals(
            [
                'recipientId' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                'collectionId' => '1',
                'params' => [
                    'CreateToken' => [
                        'tokenId' => '255',
                        'initialSupply' => '57005',
                        'unitPrice' => '10000000000000000',
                        'cap' => [
                            'type' => 'SUPPLY',
                            'amount' => '57005',
                        ],
                        'behavior' => null,
                        'listingForbidden' => false,
                        'attributes' => [],
                    ],
                ],
            ],
            $data
        );

        $data = $this->codec->decode()->mint('0x280300d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d0401fd03b67a0300010000c16ff28623000000000000000000');

        $this->assertEquals(
            [
                'recipientId' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                'collectionId' => '1',
                'params' => [
                    'Mint' => [
                        'tokenId' => '255',
                        'amount' => '57005',
                        'unitPrice' => '10000000000000000',
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_burn()
    {
        $data = $this->codec->decode()->burn('0x2804b67a0300fd03040100');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'tokenId' => '255',
                'amount' => '1',
                'keepAlive' => true,
                'removeTokenStorage' => false,
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_collection()
    {
        $data = $this->codec->decode()->freeze('0x2806b67a030000');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Collection' => null,
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_token()
    {
        $data = $this->codec->decode()->freeze('0x2806b67a030001ff000000000000000000000000000000');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Token' => '255',
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_collection_account()
    {
        $data = $this->codec->decode()->freeze('0x2806b67a030002d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'CollectionAccount' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_token_account()
    {
        $data = $this->codec->decode()->freeze('0x2806b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'TokenAccount' => [
                        '255',
                        '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_thaw_collection()
    {
        $data = $this->codec->decode()->thaw('0x2807b67a030000');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Collection' => null,
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_thaw_token()
    {
        $data = $this->codec->decode()->thaw('0x2807b67a030001ff000000000000000000000000000000');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Token' => '255',
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_thaw_collection_account()
    {
        $data = $this->codec->decode()->thaw('0x2807b67a030002d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'CollectionAccount' => '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_thaw_token_account()
    {
        $data = $this->codec->decode()->thaw('0x2807b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'TokenAccount' => [
                        '255',
                        '0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d',
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_set_attribute_from_collection()
    {
        $data = $this->codec->decode()->setAttribute('0x2808b67a030000106e616d6540456e6a696e20436f6c6c656374696f6e');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'tokenId' => null,
                'key' => 'name',
                'value' => 'Enjin Collection',
            ],
            $data
        );
    }

    public function test_it_can_decode_set_attribute_from_token()
    {
        $data = $this->codec->decode()->setAttribute('0x2808b67a030001ff000000000000000000000000000000106e616d6530476f6c64656e2053776f7264');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'tokenId' => '255',
                'key' => 'name',
                'value' => 'Golden Sword',
            ],
            $data
        );
    }

    public function test_it_can_decode_remove_attribute_from_collection()
    {
        $data = $this->codec->decode()->removeAttribute('0x2809b67a030000106e616d65');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'tokenId' => null,
                'key' => 'name',
            ],
            $data
        );
    }

    public function test_it_can_decode_remove_attribute_from_token()
    {
        $data = $this->codec->decode()->removeAttribute('0x2809b67a030001ff000000000000000000000000000000106e616d65');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'tokenId' => '255',
                'key' => 'name',
            ],
            $data
        );
    }
}
