<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchTransferBalanceMutation;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Faker\Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Override;

class BatchTransferBalanceTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'BatchTransferBalance';

    protected Codec $codec;

    protected string $defaultAccount;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = Address::daemonPublicKey();
    }

    public static function getInputData(...$data): array
    {
        $signingAccount = '0x6802f945419791d3138b4086aa0b2700abb679f950e2721fd7d65b5d1fdf8f02';
        $recipientAccount = '0x52e3c0eb993523286d19954c7e3ada6f791fa3f32764e44b9c1df0c2723bc15e';

        return [
            'recipientInputData' => [
                [
                    'account' => $data['recipientAccount0'] ?? $recipientAccount,
                    'transferBalanceParams' => [
                        'keepAlive' => $data['keepAlive0'] ?? true,
                        'value' => $data['value0'] ?? 1,
                    ],
                ],
                [
                    'account' => $data['recipientAccount1'] ?? $recipientAccount,
                    'transferBalanceParams' => [
                        'keepAlive' => $data['keepAlive1'] ?? false,
                        'value' => $data['value1'] ?? 2,
                    ],
                ],
            ],
            'continueOnFailure' => $data['continueOnFailure'] ?? false,
            'signingAccount' => $data['signingAccount'] ?? $signingAccount,
        ];
    }

    public static function validRecipientDataProvider(): array
    {
        return [
            'valid recipient data' => [self::getInputData()],
            'continue on failure true' => [self::getInputData(continueOnFailure: true)],
            'null keep alive' => [self::getInputData(keepAlive0: null)],
            'can use SS58 signing account' => [self::getInputData(signingAccount: 'rf58UUJqHEP1ZpzkLvzAaHe6j69CJKUAgGD2gbnH7zvjv5ELp')],
            'can use null signing account' => [self::getInputData(signingAccount: null)],
            'can use big int value' => [self::getInputData(value0: Hex::MAX_UINT128)],
        ];
    }

    public static function invalidRecipientDataProvider(): array
    {
        return [
            'no transfer params' => [
                ['recipientInputData' => [['account' => 'rf58UUJqHEP1ZpzkLvzAaHe6j69CJKUAgGD2gbnH7zvjv5ELp', 'transferBalanceParams' => null]], 'continueOnFailure' => false, 'signingAccount' => null],
                'transferParams',
                'You need to set the transfer balance params for every recipient.',
            ],
            'invalid signing account' => [
                self::getInputData(signingAccount: 'not_valid'),
                'signingAccount',
                'The signing account is not a valid substrate account.',
            ],
            'invalid continue on failure' => [
                self::getInputData(continueOnFailure: 'not_valid'),
                'continueOnFailure',
                'Variable "$continueOnFailure" got invalid value "not_valid"; Boolean cannot represent a non boolean value: "not_valid"',
            ],
            'invalid keep alive' => [
                self::getInputData(keepAlive0: 'not_valid'),
                'recipients.0.transferBalanceParams.keepAlive',
                'Variable "$recipients" got invalid value "not_valid" at "recipients[0].transferBalanceParams.keepAlive"; Boolean cannot represent a non boolean value: "not_valid"',
            ],
            'invalid recipient account' => [
                self::getInputData(recipientAccount0: 'not_valid'),
                'recipients.0.account',
                'The recipients.0.account is not a valid substrate account.',
            ],
            'missing recipient account' => [
                ['recipientInputData' => [['account' => null, 'transferBalanceParams' => ['value' => 1]]], 'continueOnFailure' => false, 'signingAccount' => null],
                'recipients.0.account',
                'Variable "$recipients" got invalid value null at "recipients[0].account"; Expected non-nullable type "String!',
            ],
            'missing recipient value' => [
                ['recipientInputData' => [['account' => 'rf58UUJqHEP1ZpzkLvzAaHe6j69CJKUAgGD2gbnH7zvjv5ELp', 'transferBalanceParams' => ['value' => null]]], 'continueOnFailure' => false, 'signingAccount' => null],
                'recipients.0.value',
                'Variable "$recipients" got invalid value null at "recipients[0].transferBalanceParams.value"; Expected non-nullable type "BigInt!" not to be null.',
            ],
            'invalid value' => [
                self::getInputData(value0: 'not_valid'),
                'recipients.0.transferBalanceParams.value',
                'Variable "$recipients" got invalid value "not_valid" at "recipients[0].transferBalanceParams.value"; Cannot represent following value as uint256: "not_valid"',
            ],
            'negative value' => [
                self::getInputData(value0: -1),
                'recipients.0.transferBalanceParams.value',
                'Variable "$recipients" got invalid value -1 at "recipients[0].transferBalanceParams.value"; Cannot represent following value as uint256: -1',
            ],
            'value too small' => [
                self::getInputData(value0: 0),
                'recipients.0.transferBalanceParams.value',
                'The recipients.0.transfer balance params.value is too small, the minimum value it can be is 1.',
            ],
            'value too large' => [
                self::getInputData(value0: Hex::MAX_UINT256),
                'recipients.0.transferBalanceParams.value',
                'The recipients.0.transfer balance params.value is too large, the maximum value it can be is 340282366920938463463374607431768211455.',
            ],
        ];
    }

    // Happy Path

    /**
     * @dataProvider validRecipientDataProvider
     */
    public function test_it_passes($data): void
    {
        $encodedData = TransactionSerializer::encode('Batch', BatchTransferBalanceMutation::getEncodableParams(
            recipients: collect($data['recipientInputData'])->map(fn ($recipient) => [
                'accountId' => $recipient['account'],
                'keepAlive' => $recipient['transferBalanceParams']['keepAlive'],
                'value' => $recipient['transferBalanceParams']['value'],
            ])->toArray(),
            continueOnFailure: $data['continueOnFailure'],
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipients' => $data['recipientInputData'],
            'continueOnFailure' => $data['continueOnFailure'],
            'signingAccount' => SS58Address::encode($data['signingAccount']),
            'skipValidation' => true,
            'simulate' => true,
        ]);

        $this->assertArrayContainsArray([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'deposit' => null,
            'wallet' => [
                'account' => [
                    'publicKey' => SS58Address::getPublicKey($data['signingAccount']),
                ],
            ],
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    // Exception Path

    /**
     * @dataProvider invalidRecipientDataProvider
     */
    public function test_it_fails_with_error($data, $errorKey, $errorValue): void
    {
        $response = $this->graphql($this->method, [
            'recipients' => $data['recipientInputData'],
            'continueOnFailure' => $data['continueOnFailure'],
            'signingAccount' => $data['signingAccount'],
            'skipValidation' => false,
            'simulate' => true,
        ], true);

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

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
