<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Token\TokenIdManager;
use Enjin\Platform\Support\Blake2;
use Enjin\Platform\Tests\TestCase;
use Faker\Generator;

final class TokenIdEncoderTest extends TestCase
{
    public function test_it_encodes_using_hash()
    {
        $dataToEncode = [
            'tokenId' => [
                'hash' => $this->toObject([
                    'test1' => fake()->sentence(1),
                    'test2' => [1],
                    'test3' => [
                        ['name' => fake()->name()],
                        ['name' => fake()->name()],
                    ],
                ]),
            ],
        ];

        $expectedResult = HexConverter::hexToUInt(Blake2::hash(HexConverter::stringToHex(json_encode($dataToEncode['tokenId']['hash'])), 128));

        $result = resolve(TokenIdManager::class)->encode($dataToEncode);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_it_encodes_using_string_id()
    {
        $dataToEncode = [
            'tokenId' => [
                'stringId' => fake()->asciify('********'),
            ],
        ];

        $expectedResult = HexConverter::hexToUInt(HexConverter::stringToHex($dataToEncode['tokenId']['stringId']));

        $result = resolve(TokenIdManager::class)->encode($dataToEncode);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_it_throws_error_using_string_id_with_array()
    {
        $dataToEncode = [
            'tokenId' => [
                'stringId' => [
                    'string' => ['test'],
                ],
            ],
        ];

        $this->expectExceptionMessage('The string id field must be a string.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_throws_error_using_string_id_with_object()
    {
        $dataToEncode = [
            'tokenId' => [
                'stringId' => $this->toObject([
                    'string' => ['key' => 'test'],
                ]),
            ],
        ];

        $this->expectExceptionMessage('The string id field must be a string.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_throws_error_using_string_id_with_array_of_object()
    {
        $dataToEncode = [
            'tokenId' => [
                'stringId' => [
                    'string' => [$this->toObject(['key' => 'test'])],
                ],
            ],
        ];

        $this->expectExceptionMessage('The string id field must be a string.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_throws_error_using_string_id_with_no_string()
    {
        $dataToEncode = [
            'tokenId' => [
                'stringId' => [
                    'name' => fake()->sentence(1),
                ],
            ],
        ];

        $this->expectExceptionMessage('The string id field must be a string.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_throws_error_using_string_id_with_out_of_bounds_conversion()
    {
        $dataToEncode = [
            'tokenId' => [
                'stringId' => [
                    'string' => 'thisMyTOKENID_123',
                ],
            ],
        ];

        $this->expectExceptionMessage('The string id field must be a string.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_encodes_using_erc1155()
    {
        $dataToEncode = [
            'tokenId' => [
                'erc1155' => [
                    'tokenId' => HexConverter::prefix(app(Generator::class)->erc1155_token_id()),
                    'index' => fake()->numberBetween(),
                ],
            ],
        ];

        $expectedResult = HexConverter::padRight(HexConverter::padRight($dataToEncode['tokenId']['erc1155']['tokenId'], 16) . HexConverter::padLeft(HexConverter::intToHex($dataToEncode['tokenId']['erc1155']['index']), 16), 32);

        $result = resolve(TokenIdManager::class)->encode($dataToEncode);

        $this->assertEquals(HexConverter::hexToUInt($expectedResult), $result);
    }

    public function test_it_encodes_using_erc1155_with_no_index()
    {
        $dataToEncode = [
            'tokenId' => [
                'erc1155' => [
                    'tokenId' => HexConverter::prefix(app(Generator::class)->erc1155_token_id()),
                ],
            ],
        ];

        $expectedResult = HexConverter::padRight(HexConverter::padRight($dataToEncode['tokenId']['erc1155']['tokenId'], 16), 32);

        $result = resolve(TokenIdManager::class)->encode($dataToEncode);

        $this->assertEquals(HexConverter::hexToUInt($expectedResult), $result);
    }

    public function test_it_fails_to_encode_using_invalid_erc1155()
    {
        $dataToEncode = [
            'tokenId' => [
                'erc1155' => [
                    'tokenId' => HexConverter::prefix('0x123456'),
                    'index' => fake()->numberBetween(),
                ],
            ],
        ];

        $this->expectExceptionMessage('The erc1155.token id field must be 18 characters.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_fails_to_encode_using_invalid_erc1155_hex()
    {
        $dataToEncode = [
            'tokenId' => [
                'erc1155' => [
                    'tokenId' => HexConverter::prefix('0x123456789abcdefg'),
                    'index' => fake()->numberBetween(),
                ],
            ],
        ];

        $this->expectExceptionMessage('The erc1155.token id has an invalid hex string.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }

    public function test_it_fails_to_encode_using_invalid_erc1155_index()
    {
        $dataToEncode = [
            'tokenId' => [
                'erc1155' => [
                    'tokenId' => HexConverter::prefix(app(Generator::class)->erc1155_token_id()),
                    'index' => 'abc',
                ],
            ],
        ];

        $this->expectExceptionMessage('The erc1155.index field must be an integer.');

        resolve(TokenIdManager::class)->encode($dataToEncode);
    }
}
