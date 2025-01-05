<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Tests\TestCase;

final class DecodingTest extends TestCase
{
    protected Codec $codec;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
    }

    public function test_it_can_decode_system_account()
    {
        $data = $this->codec->decoder()->systemAccount('0x1f00000000000000010000000000000000424ed9cbe55f0b91010000000000000000e65e4b9feedf56000000000000000000000000000000000000000000000000000000000000000000000000000000');

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
        $data = $this->codec->decoder()->createCollection('0x280001ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f');

        $this->assertEquals(
            [
                'mintPolicy' => [
                    'maxTokenCount' => '255',
                    'maxTokenSupply' => '57005',
                    'forceCollapsingSupply' => true,
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
        $data = $this->codec->decoder()->createCollection('0x280001ff0000000000000001adde00000000000000000000000000000100');

        $this->assertEquals(
            [
                'mintPolicy' => [
                    'maxTokenCount' => '255',
                    'maxTokenSupply' => '57005',
                    'forceCollapsingSupply' => true,
                ],
                'marketPolicy' => null,
            ],
            $data
        );

        $data = $this->codec->decoder()->createCollection('0x280001ff0000000000000001adde00000000000000000000000000000101301cb3057d43941d5f631613aa1661be0354d39e34f23d4ef527396b10d2bb7a0208af2f');

        $this->assertEquals(
            [
                'mintPolicy' => [
                    'maxTokenCount' => '255',
                    'maxTokenSupply' => '57005',
                    'forceCollapsingSupply' => true,
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
        $data = $this->codec->decoder()->destroyCollection('0x2801b67a0300');

        $this->assertEquals(
            [
                'collectionId' => '57005',
            ],
            $data
        );
    }

    public function test_it_can_decode_burn()
    {
        $data = $this->codec->decoder()->burn('0x2805b67a0300fd030400');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'tokenId' => '255',
                'amount' => '1',
                'removeTokenStorage' => false,
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_collection()
    {
        $data = $this->codec->decoder()->freeze('0x2806b67a030000');

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
        $data = $this->codec->decoder()->freeze('0x2807b67a030001ff00000000000000000000000000000000');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Token' => [
                        'tokenId' => '255',
                        'freezeState' => null,
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_token_with_freeze_state()
    {
        $data = $this->codec->decoder()->freeze('0x2807b67a030001ff0000000000000000000000000000000101');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Token' => [
                        'tokenId' => '255',
                        'freezeState' => 'TEMPORARY',
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_freeze_collection_account()
    {
        $data = $this->codec->decoder()->freeze('0x2806b67a030002d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

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
        $data = $this->codec->decoder()->freeze('0x2806b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

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
        $data = $this->codec->decoder()->thaw('0x2807b67a030000');

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
        $data = $this->codec->decoder()->thaw('0x2808b67a030001ff00000000000000000000000000000000');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Token' => [
                        'tokenId' => '255',
                        'freezeState' => null,
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_thaw_token_with_freeze_state()
    {
        $data = $this->codec->decoder()->thaw('0x2808b67a030001ff0000000000000000000000000000000102');

        $this->assertEquals(
            [
                'collectionId' => '57005',
                'freezeType' => [
                    'Token' => [
                        'tokenId' => '255',
                        'freezeState' => 'NEVER',
                    ],
                ],
            ],
            $data
        );
    }

    public function test_it_can_decode_thaw_collection_account()
    {
        $data = $this->codec->decoder()->thaw('0x2807b67a030002d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

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
        $data = $this->codec->decoder()->thaw('0x2807b67a030003fd03d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

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
        $data = $this->codec->decoder()->setAttribute('0x2808b67a030000106e616d6540456e6a696e20436f6c6c656374696f6e');

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
        $data = $this->codec->decoder()->setAttribute('0x2808b67a030001ff000000000000000000000000000000106e616d6530476f6c64656e2053776f7264');

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
        $data = $this->codec->decoder()->removeAttribute('0x2809b67a030000106e616d65');

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
        $data = $this->codec->decoder()->removeAttribute('0x2809b67a030001ff000000000000000000000000000000106e616d65');

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
