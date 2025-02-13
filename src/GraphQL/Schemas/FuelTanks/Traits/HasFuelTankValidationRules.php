<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries;

use Enjin\Platform\FuelTanks\Rules\TokenExistsInCollection;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

trait HasFuelTankValidationRules
{
    use HasTokenIdFieldRules;

    /**
     * Get the common validation rules.
     */
    protected function commonRulesExist(string $attribute, array $args = []): array
    {
        $isArray = str_contains($attribute, '.*');

        return match (true) {
            str_contains($attribute, 'FuelBudget') => [
                "{$attribute}.amount" => [
                    'bail',
                    new MinBigInt(),
                    new MaxBigInt(Hex::MAX_UINT128),
                ],
                "{$attribute}.resetPeriod" => [
                    'bail',
                    new MinBigInt(),
                    new MaxBigInt(Hex::MAX_UINT32),
                ],
            ],
            default => [
                "{$attribute}.requireSignature" => ['nullable', new ValidSubstrateAddress()],
                "{$attribute}.whitelistedCallers.*" => ['bail', 'distinct', 'max:255', 'filled', new ValidSubstrateAddress()],
                "{$attribute}.whitelistedCallers" => ['nullable', 'array', 'min:1'],
                "{$attribute}.requireToken.collectionId" => $isArray
                        ? Rule::forEach(fn ($value, $key) => [
                            'bail',
                            'required_with:' . str_replace('collectionId', 'tokenId', $key),
                            new MinBigInt(),
                            new MaxBigInt(Hex::MAX_UINT128),
                            Rule::exists('collections', 'collection_chain_id'),
                        ])
                        : [
                            "required_with:{$attribute}.requireToken.tokenId",
                            Rule::exists('collections', 'collection_chain_id'),
                        ],
                ...$this->getOptionalTokenFieldRules("{$attribute}.requireToken"),
                "{$attribute}.requireToken" => $isArray
                    ? Rule::forEach(fn ($value, $key) => new TokenExistsInCollection(Arr::get($args, "{$key}.collectionId")))
                    : new TokenExistsInCollection(Arr::get($args, "{$attribute}.requireToken.collectionId")),
            ]
        };
    }

    /**
     * Get the common validation rules.
     */
    protected function commonRules(string $attribute, array $args = []): array
    {
        $isArray = str_contains($attribute, '.*');

        return match (true) {
            str_contains($attribute, 'FuelBudget') => [
                "{$attribute}.amount" => [
                    'bail',
                    new MinBigInt(),
                    new MaxBigInt(Hex::MAX_UINT128),
                ],
                "{$attribute}.resetPeriod" => [
                    'bail',
                    new MinBigInt(),
                    new MaxBigInt(Hex::MAX_UINT32),
                ],
            ],
            default => [
                "{$attribute}.requireSignature" => ['nullable', new ValidSubstrateAddress()],
                "{$attribute}.whitelistedCallers.*" => ['bail', 'distinct', 'max:255', 'filled', new ValidSubstrateAddress()],
                "{$attribute}.whitelistedCallers" => ['nullable', 'array', 'min:1'],
                "{$attribute}.requireToken.collectionId" => $isArray
                    ? Rule::forEach(fn ($value, $key) => [
                        'bail',
                        'required_with:' . str_replace('collectionId', 'tokenId', $key),
                        new MinBigInt(),
                        new MaxBigInt(Hex::MAX_UINT128),
                    ])
                    : [
                        "required_with:{$attribute}.requireToken.tokenId",
                    ],
                ...$this->getOptionalTokenFieldRules("{$attribute}.requireToken"),
            ]
        };
    }

    /**
     * Get the mutation's request validation rules.
     */
    protected function validationRulesExist(array $args = [], array $except = [], string $attributePrefix = ''): array
    {
        $rules = [
            "{$attributePrefix}name" => [
                'bail',
                'filled',
                'max:32',
                Rule::unique('fuel_tanks', 'name'),
            ],
            ...$this->commonRulesExist("{$attributePrefix}accountRules", $args),
            ...$this->dispatchRulesExist($args, $attributePrefix),
        ];

        return Arr::except($rules, $except);
    }

    /**
     * Get the mutation's request validation rules.
     */
    protected function validationRules(array $args = [], array $except = [], string $attributePrefix = ''): array
    {
        $rules = [
            "{$attributePrefix}name" => [
                'bail',
                'filled',
                'max:32',
            ],
            ...$this->commonRules("{$attributePrefix}accountRules", $args),
            ...$this->dispatchRules($args, $attributePrefix),
        ];

        return Arr::except($rules, $except);
    }

    /**
     * Get the dispatch rules validation rules.
     */
    protected function dispatchRulesExist(array $args = [], string $attributePrefix = '', $isArray = true): array
    {
        $array = $isArray ? '.*' : '';

        return [
            ...$this->commonRulesExist("{$attributePrefix}dispatchRules{$array}", $args),
            "{$attributePrefix}dispatchRules{$array}.whitelistedCollections.*" => [
                'bail',
                'distinct',
                'max:255',
                Rule::exists('collections', 'collection_chain_id'),
            ],
            "{$attributePrefix}dispatchRules{$array}.whitelistedCollections" => [
                'nullable',
                'array',
                'min:1',
            ],
            "{$attributePrefix}dispatchRules{$array}.maxFuelBurnPerTransaction" => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
            ...$this->commonRulesExist("{$attributePrefix}dispatchRules{$array}.userFuelBudget"),
            ...$this->commonRulesExist("{$attributePrefix}dispatchRules{$array}.tankFuelBudget"),
            "{$attributePrefix}dispatchRules{$array}.whitelistedPallets.*" => ['bail', 'distinct', 'max:255', 'filled', new ValidHex()],
            "{$attributePrefix}dispatchRules{$array}.whitelistedPallets" => ['nullable', 'array', 'min:1'],
            "{$attributePrefix}dispatchRules{$array}.userFuelBudget" => ['prohibited_unless:requireAccount,true'],
        ];
    }

    /**
     * Get the dispatch rules validation rules.
     */
    protected function dispatchRules(array $args = [], string $attributePrefix = '', $isArray = true): array
    {
        $array = $isArray ? '.*' : '';

        return [
            ...$this->commonRules("{$attributePrefix}dispatchRules{$array}", $args),
            "{$attributePrefix}dispatchRules{$array}.whitelistedCollections.*" => [
                'bail',
                'distinct',
                'max:255',
            ],
            "{$attributePrefix}dispatchRules{$array}.whitelistedCollections" => [
                'nullable',
                'array',
                'min:1',
            ],
            "{$attributePrefix}dispatchRules{$array}.maxFuelBurnPerTransaction" => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
            ...$this->commonRules("{$attributePrefix}dispatchRules{$array}.userFuelBudget"),
            ...$this->commonRules("{$attributePrefix}dispatchRules{$array}.tankFuelBudget"),
            "{$attributePrefix}dispatchRules{$array}.whitelistedPallets.*" => ['bail', 'distinct', 'max:255', 'filled', new ValidHex()],
            "{$attributePrefix}dispatchRules{$array}.whitelistedPallets" => ['nullable', 'array', 'min:1'],
            "{$attributePrefix}dispatchRules{$array}.userFuelBudget" => ["prohibited_unless:{$attributePrefix}requireAccount,true"],
        ];
    }
}
