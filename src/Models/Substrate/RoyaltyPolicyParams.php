<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class RoyaltyPolicyParams
{
    /**
     * Create new royalty policy parameter instance.
     */
    public function __construct(
        public string $beneficiary,
        public float $percentage,
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            beneficiary: HexConverter::prefix(Arr::get($params, 'beneficiary')),
            percentage: Arr::get($params, 'percentage') / 10 ** 7,
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            beneficiary: Arr::get($params, 'beneficiary'),
            percentage: Arr::get($params, 'percentage'),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        if (currentSpec() >= 1020) {
            // TODO: After v1020 we now have an array of beneficiaries for now we will just use the first one
            return [
                'beneficiaries' => [[
                    'beneficiary' => HexConverter::unPrefix(SS58Address::getPublicKey($this->beneficiary)),
                    'percentage' => (int) $this->percentage * 10 ** 7,
                ]],
            ];
        }

        return [
            'beneficiary' => HexConverter::unPrefix(SS58Address::getPublicKey($this->beneficiary)),
            'percentage' => (int) $this->percentage * 10 ** 7,
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'beneficiary' => $this->beneficiary,
            'percentage' => $this->percentage,
        ];
    }
}
