<?php

namespace Enjin\Platform\Providers\Faker;

use Enjin\BlockchainTools\HexConverter;
use Faker\Provider\Base;

class Erc1155Provider extends Base
{
    /**
     * Get a random ERC-1155 token ID.
     */
    public function erc1155_token_id(?bool $fungible = null, bool $replicated = false): string
    {
        $fungible ??= (bool) mt_rand(0, 1);
        $typeBit = $fungible ? 0 : 8;

        if ($replicated) {
            $typeBit += 4;
        }

        return Base::regexify(sprintf(
            '[0-9]{2}%x[0-9a-f]{13}',
            $typeBit,
        ));
    }

    /**
     * Get a random ERC-1155 token index.
     */
    public function erc1155_token_index(): string
    {
        return Base::regexify('[0-9a-f]{16}');
    }

    /**
     * Get a random ERC-1155 token integer.
     */
    public function erc1155_token_int(): string
    {
        return HexConverter::hexToUInt(HexConverter::padRight($this->erc1155_token_id(), 48) . HexConverter::padLeft($this->erc1155_token_index(), 16));
    }
}
