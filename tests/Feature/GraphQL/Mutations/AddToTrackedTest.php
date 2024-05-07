<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Jobs\HotSync;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;

class AddToTrackedTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'AddToTracked';
    protected bool $fakeEvents = false;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Data Providers

    public static function getInputData(...$inputData): array
    {
        $outputData = [
            'type' => $inputData['type'] ?? ModelType::COLLECTION->name,
            'chainIds' => $inputData['chainIds'] ?? [(string) fake()->unique()->numberBetween(2000)],
        ];

        if (isset($inputData['hotSync'])) {
            $outputData['hotSync'] = $inputData['hotSync'];
        }

        return $outputData;
    }

    public static function validDataProvider(): array
    {
        return [
            'track single collection' => [
                self::getInputData(),
                fn () => Queue::assertPushed(HotSync::class),
            ],
            'track multiple collections' => [
                self::getInputData(chainIds: [
                    (string) fake()->unique()->numberBetween(2000),
                    (string) fake()->unique()->numberBetween(2000),
                ]),
                fn () => Queue::assertPushed(HotSync::class),
            ],
            'track multiple collections without hot sync' => [
                self::getInputData(
                    chainIds: [
                        (string) fake()->unique()->numberBetween(2000),
                        (string) fake()->unique()->numberBetween(2000),
                    ],
                    hotSync: false
                ),
                fn () => Queue::assertNotPushed(HotSync::class),
            ],
        ];
    }

    public static function invalidDataProvider(): array
    {
        return [
            'no type supplied' => [
                ['chainIds' => [(string) fake()->unique()->numberBetween(2000)]],
                'type',
                'Variable "$type" of required type "ModelType!" was not provided.',
            ],
            'invalid type' => [
                self::getInputData(type: 'invalid-type'),
                'type',
                'Variable "$type" got invalid value "invalid-type"; Value "invalid-type" does not exist in "ModelType" enum.',
            ],
            'no chain ids supplied' => [
                ['type' => ModelType::COLLECTION->name],
                'chainsIds',
                'Variable "$chainIds" of required type "[String!]!" was not provided.',
            ],
            'too many chain ids supplied with hot sync' => [
                [
                    'type' => ModelType::COLLECTION->name,
                    'chainIds' => array_map(fn () => (string) fake()->unique()->numberBetween(2000), array_fill(0, 11, null)),
                ],
                'chainIds',
                'The chain ids field must not have more than 10 items.',
            ],
            'too many chain ids supplied without hot sync' => [
                [
                    'type' => ModelType::COLLECTION->name,
                    'chainIds' => array_map(fn () => (string) fake()->unique()->numberBetween(2000), array_fill(0, 1001, null)),
                    'hotSync' => false,
                ],
                'chainIds',
                'The chain ids field must not have more than 1000 items.',
            ],
            'chain ids too low' => [
                self::getInputData(chainIds: ['100']),
                'chainIds.0',
                'The chainIds.0 is too small, the minimum value it can be is 2000.',
            ],
            'chain ids too large' => [
                self::getInputData(chainIds: [Hex::MAX_UINT256]),
                'chainIds.0',
                'The chainIds.0 is too large, the maximum value it can be is 340282366920938463463374607431768211455.',
            ],
            'invalid hot sync value supplied' => [
                self::getInputData(hotSync: 'invalid-hotsync'),
                'hotSync',
                'Variable "$hotSync" got invalid value "invalid-hotsync"; Boolean cannot represent a non boolean value: "invalid-hotsync"',
            ],
        ];
    }

    // Happy Path

    /**
     * @dataProvider validDataProvider
     */
    public function test_it_can_add_to_tracked_data($data, $queueAssertion): void
    {
        Queue::fake();
        $response = $this->graphql($this->method, $data);

        $this->assertTrue($response);
        $queueAssertion();
    }

    // Exception Path

    /**
     * @dataProvider invalidDataProvider
     */
    public function test_it_fails_to_add_to_tracked_data($data, $errorKey, $errorValue): void
    {
        Queue::fake();

        $response = $this->graphql($this->method, $data, true);

        if (is_array($response['error'])) {
            $this->assertArraySubset(
                [$errorKey => Arr::wrap($errorValue)],
                $response['error'],
            );
        } else {
            $this->assertStringContainsString(
                $errorValue,
                $response['error']
            );
        }

        Queue::assertNotPushed(HotSync::class);
    }
}
