<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class RemoveFromTrackedTest extends TestCaseGraphQL
{
    protected string $method = 'RemoveFromTracked';
    protected bool $fakeEvents = false;

    protected static array $syncableIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Syncable::query()->insert([
            ['syncable_id' => static::$syncableIds[0], 'syncable_type' => ModelType::COLLECTION->value, 'deleted_at' => null],
            ['syncable_id' => static::$syncableIds[1], 'syncable_type' => ModelType::COLLECTION->value, 'deleted_at' => null],
            ['syncable_id' => static::$syncableIds[2], 'syncable_type' => ModelType::COLLECTION->value, 'deleted_at' => Carbon::now()],
        ]);
    }

    // Data Providers

    public static function getInputData(...$inputData): array
    {
        return [
            'type' => $inputData['type'] ?? ModelType::COLLECTION->name,
            'chainIds' => $inputData['chainIds'] ?? static::$syncableIds,
        ];
    }

    public static function validDataProvider(): array
    {
        static::$syncableIds = [
            fake()->unique()->numberBetween(2000),
            fake()->unique()->numberBetween(2000),
            fake()->unique()->numberBetween(2000),
        ];

        return [
            'remove single collection' => [
                static::getInputData(chainIds: [
                    static::$syncableIds[0],
                ]),
                collect([
                    ['assertSoftDeleted', ['syncables', ['syncable_id' => (string) static::$syncableIds[0]]]],
                    ['assertDatabaseHas', ['syncables', ['syncable_id' => (string) static::$syncableIds[1]]]],
                ]),
            ],
            'remove multiple collections' => [
                static::getInputData(),
                collect([
                    ['assertSoftDeleted', ['syncables', ['syncable_id' => (string) static::$syncableIds[0]]]],
                    ['assertSoftDeleted', ['syncables', ['syncable_id' => (string) static::$syncableIds[1]]]],
                ]),
            ],
        ];
    }

    public static function invalidDataProvider(): array
    {
        return [
            'no type supplied' => [
                ['chainIds' => [static::$syncableIds[0]]],
                'type',
                'Variable "$type" of required type "ModelType!" was not provided.',
            ],
            'invalid type' => [
                static::getInputData(type: 'invalid-type'),
                'type',
                'Variable "$type" got invalid value "invalid-type"; Value "invalid-type" does not exist in "ModelType" enum.',
            ],
            'no chain ids supplied' => [
                ['type' => ModelType::COLLECTION->name],
                'chainIds',
                'Variable "$chainIds" of required type "[BigInt!]!" was not provided.',
            ],
            'too many chain ids supplied' => [
                [
                    'type' => ModelType::COLLECTION->name,
                    'chainIds' => array_map(fn () => (string) fake()->unique()->numberBetween(2000), array_fill(0, 1001, null)),
                ],
                'chainIds',
                'The chain ids field must not have more than 1000 items.',
            ],
            'chain ids too low' => [
                static::getInputData(chainIds: [100]),
                'chainIds.0',
                'The chainIds.0 is too small, the minimum value it can be is 2000.',
            ],
            'chain ids too large' => [
                self::getInputData(chainIds: [Hex::MAX_UINT256]),
                'chainIds.0',
                'The chainIds.0 is too large, the maximum value it can be is 340282366920938463463374607431768211455.',
            ],
        ];
    }

    // Happy Path

    /**
     * @dataProvider validDataProvider
     */
    public function test_it_can_remove_tracked_data($data, $assertions): void
    {
        $response = $this->graphql($this->method, $data);

        $this->assertTrue($response);
        $assertions->each(fn ($assertion) => $this->{$assertion[0]}(...$assertion[1]));
    }

    // Exception Path

    /**
     * @dataProvider invalidDataProvider
     */
    public function test_it_fails_to_remove_tracked_data($data, $errorKey, $errorValue): void
    {
        $response = $this->graphql($this->method, $data, true);

        if (is_array($response['error'])) {
            $this->assertArrayContainsArray(
                [$errorKey => Arr::wrap($errorValue)],
                $response['error'],
            );
        } else {
            $this->assertStringContainsString(
                $errorValue,
                $response['error']
            );
        }
    }
}
