<?php

namespace Enjin\Platform\Services\Token\Encoders;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Support\Blake2;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class Hash extends BaseEncoder
{
    /**
     * Create instance.
     */
    public function __construct(protected mixed $data = null)
    {
        parent::__construct($data);

        $config = config('enjin-platform.token_id_encoders.' . self::getName());
        $algo = $config['algo'] ?? null;

        if (empty($algo)) {
            throw new InvalidArgumentException(__('enjin-platform::error.token_id_encoder.hash.algo_not_defined_in_config'));
        }

        // Only support this for now
        if ('blake2' != $algo) {
            throw new InvalidArgumentException(__('enjin-platform::error.token_id_encoder.hash.algo_not_supported', ['algo' => $algo]));
        }
    }

    /**
     * Get the type of the encoder.
     */
    public static function getType(): Type
    {
        return GraphQL::type('Object');
    }

    /**
     * Get the description of the encoder.
     */
    public static function getDescription(): string
    {
        return __('enjin-platform::input_type.token_id_encoder.hash.description');
    }

    /**
     * Encode an arbitrary object of data into a tokenId.
     * The entire object will be serialized to JSON and then hashed.
     */
    public function encode(mixed $data = null): string
    {
        return HexConverter::hexToUInt(Blake2::hash(HexConverter::stringToHex(json_encode($data ?? $this->data)), 128));
    }
}
