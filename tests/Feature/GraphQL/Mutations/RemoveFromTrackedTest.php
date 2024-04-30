<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;

class RemoveFromTrackedTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'RemoveFromTracked';
    protected bool $fakeEvents = false;

    protected static array $syncableIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Syncable::query()->insert([
            ['syncable_id' => static::$syncableIds[0], 'syncable_type' => ModelType::COLLECTION->value],
            ['syncable_id' => static::$syncableIds[1], 'syncable_type' => ModelType::COLLECTION->value],
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
        ];

        return [
            'remove single collection' => [
                static::getInputData(chainIds: [
                    static::$syncableIds[0],
                ]),
                collect([
                    ['assertDatabaseMissing', ['syncables', ['syncable_id' => static::$syncableIds[0]]]],
                    ['assertDatabaseHas', ['syncables', ['syncable_id' => static::$syncableIds[1]]]],
                ]),
            ],
            'remove multiple collections' => [
                static::getInputData(),
                collect([
                    ['assertDatabaseMissing', ['syncables', ['syncable_id' => static::$syncableIds[0]]]],
                    ['assertDatabaseMissing', ['syncables', ['syncable_id' => static::$syncableIds[1]]]],
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
                'chainsIds',
                'Variable "$chainIds" of required type "[BigInt!]!" was not provided.',
            ],
            'chain ids too low' => [
                static::getInputData(chainIds: [100]),
                'chainIds.0',
                'The chainIds.0 is too small, the minimum value it can be is 2000.',
            ],
        ];
    }

    // Happy Path

    /**
     * @dataProvider validDataProvider
     */
    public function test_it_can_add_to_tracked_data($data, $assertions): void
    {
        $response = $this->graphql($this->method, $data);

        $this->assertTrue($response);
        $assertions->each(fn ($assertion) => $this->{$assertion[0]}(...$assertion[1]));
    }

    // Exception Path

    /**
     * @dataProvider invalidDataProvider
     */
    public function test_it_fails_to_add_to_remove_tracked_data($data, $errorKey, $errorValue): void
    {
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
    }
}
