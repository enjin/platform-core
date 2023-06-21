<?php

namespace Enjin\Platform\Services\Token\Encoders;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class StringId extends BaseEncoder
{
    /**
     * Get the type of the encoder.
     */
    public static function getType(): Type
    {
        return GraphQL::type('String');
    }

    /**
     * Get the description of the encoder.
     */
    public static function getDescription(): string
    {
        return __('enjin-platform::input_type.token_id_encoder.string_id.description');
    }

    /**
     * Get the rules of the encoder.
     */
    public static function getRules(): array
    {
        return [
            'stringId' => [
                ...parent::getRules(),
                'string',
            ],
        ];
    }

    /**
     * Encode a string into a token ID via its hex representation.
     */
    public function encode(mixed $data = null): string
    {
        $data = $data->scalar ?? $data ?? $this->data;

        $tokenId = HexConverter::hexToUInt(HexConverter::stringToHex($data));

        if (bccomp($tokenId, Hex::MAX_UINT128) >= 0) {
            throw new PlatformException(__('enjin-platform::error.token_int_too_large'), 400);
        }

        return $tokenId;
    }
}
