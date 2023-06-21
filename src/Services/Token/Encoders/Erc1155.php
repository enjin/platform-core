<?php

namespace Enjin\Platform\Services\Token\Encoders;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Rules\ValidHex;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class Erc1155 extends BaseEncoder
{
    /**
     * Get the type of the encoder.
     */
    public static function getType(): Type
    {
        return GraphQL::type('Erc1155EncoderInput');
    }

    /**
     * Get the description of the encoder.
     */
    public static function getDescription(): string
    {
        return __('enjin-platform::input_type.token_id_encoder.erc1155.description');
    }

    /**
     * Get the rules of the encoder.
     */
    public static function getRules(): array
    {
        return [
            'erc1155.tokenId' => [
                ...parent::getRules(),
                'string',
                new ValidHex(),
                'size:18',
            ],
            'erc1155.index' => [
                ...parent::getRules(),
                'integer',
            ],
        ];
    }

    /**
     * Encode an ERC1155 style token ID into an int token ID.
     * Note that the max int value returned for Substrate is 128bits compared to Ethereum's 256bit ids.
     *
     * @param $data
     *
     * @throws ValidationException
     * @return string
     */
    public function encode(mixed $data = null): string
    {
        return HexConverter::hexToUInt($this->tokenIdAndIndexToHex($data ?? $this->data));
    }

    /**
     * Create an integer id from the supplied hex token id and index.
     * @throws ValidationException
     */
    protected function tokenIdAndIndexToHex(object $data): string
    {
        $idToEncode = HexConverter::unPrefix($data->tokenId);

        if (isset($data->index)) {
            return $idToEncode . HexConverter::padLeft(HexConverter::intToHex($data->index), 16);
        }

        return HexConverter::padRight($idToEncode, 32);
    }
}
