<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\CreateCollectionMutation;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Services\Serialization\Implementations\Substrate;
use Enjin\Platform\Tests\TestCase;

class SerializationTest extends TestCase
{
    public function test_it_can_encode_decode()
    {
        $substrate = new Substrate();
        $encoded = $substrate->encode(
            'CreateCollection',
            CreateCollectionMutation::getEncodableParams(
                mintPolicy: new MintPolicyParams(forceSingleMint: true)
            )
        );
        $this->assertNotEmpty($encoded);

        $decoded = $substrate->decode('createCollection', $encoded);
        $this->assertNotEmpty($decoded);
        $this->assertEquals([
            'mintPolicy' => [
                'forceSingleMint' => true,
                'maxTokenCount' => null,
                'maxTokenSupply' => '0',
            ],
            'marketPolicy' => null,
        ], $decoded);
    }
}
