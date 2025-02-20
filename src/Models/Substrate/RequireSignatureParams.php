<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Support\SS58Address;

class RequireSignatureParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(
        public array|string $signature,
    ) {
        $this->signature = SS58Address::getPublicKey($this->signature);
    }

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            signature: $params['RequireSignature'],
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return ['RequireSignature' => SS58Address::getPublicKey($this->signature)];
    }

    public function toArray(): array
    {
        return ['RequireSignature' => HexConverter::prefix($this->signature)];
    }

    public function validate(string $signature): bool
    {
        return ctype_xdigit($signature) && strlen(HexConverter::unPrefix($signature)) === 16;
    }
}
