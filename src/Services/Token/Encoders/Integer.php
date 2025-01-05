<?php

namespace Enjin\Platform\Services\Token\Encoders;

use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class Integer extends BaseEncoder
{
    /**
     * Get the type of the encoder.
     */
    public static function getType(): Type
    {
        return GraphQL::type('BigInt');
    }

    /**
     * Get the description of the encoder.
     */
    public static function getDescription(): string
    {
        return __('enjin-platform::input_type.token_id_encoder.integer.description');
    }

    /**
     * Get the rules of the encoder.
     */
    #[\Override]
    public static function getRules(): array
    {
        return [
            'integer' => [
                ...parent::getRules(),
                'numeric',
                new MinBigInt(0),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
        ];
    }

    /**
     * Pass in a native token id.
     */
    public function encode(mixed $data = null): string
    {
        return $data->scalar ?? $data ?? $this->data;
    }
}
