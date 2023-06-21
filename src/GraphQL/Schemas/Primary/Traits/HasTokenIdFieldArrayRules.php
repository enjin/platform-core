<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Enjin\Platform\Package;
use Enjin\Platform\Rules\TokenEncodeDoesNotExistInCollection;
use Enjin\Platform\Rules\TokenEncodeExistInCollection;
use Enjin\Platform\Services\Token\Encoder;
use Illuminate\Support\Arr;
use Illuminate\Validation\NestedRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

trait HasTokenIdFieldArrayRules
{
    /**
     * Get token fields rules.
     */
    public function getTokenFieldRules(
        string $attribute,
        array $args = [],
        array $encodableTokenIdRules = []
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodableTokenIdRules($args, $encodableTokenIdRules),
        ];
    }

    /**
     * Get token fields rules with exist.
     */
    public function getTokenFieldRulesExist(
        string $attribute,
        array $args = [],
        array $encodableTokenIdRules = []
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodableTokenIdRulesExist($args, $encodableTokenIdRules),
        ];
    }

    /**
     * Get token fields rules with doesn't exist.
     */
    public function getTokenFieldRulesDoesntExist(
        string $attribute,
        array $args = [],
        array $encodeTokenIdRules = []
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodableTokenIdRulesDoesntExist($args, $encodeTokenIdRules),
        ];
    }

    /**
     * Get encode token ID rules.
     */
    public function getEncodableTokenIdRules(array $args = [], array $extraRules = []): NestedRules
    {
        return Rule::forEach(function ($value, $attribute) use ($args, $extraRules) {
            return [
                'bail',
                'filled',
                new RequiredIf(Arr::get($args, str_replace('.tokenId', '', $attribute))),
                ...$extraRules,
            ];
        });
    }

    /**
     * Get encode token ID rules with exist rule.
     */
    public function getEncodableTokenIdRulesExist(array $args = [], array $extraRules = []): NestedRules
    {
        return Rule::forEach(function ($value, $attribute) use ($args, $extraRules) {
            $rules = [
                'bail',
                'filled',
                new RequiredIf(Arr::get($args, str_replace('.tokenId', '', $attribute))),
                new TokenEncodeExistInCollection(),
                ...$extraRules,
            ];

            return $rules;
        });
    }

    /**
     * Get encodeable token rules with doesn't exist rule.
     */
    public function getEncodableTokenIdRulesDoesntExist(array $args = [], array $extraRules = []): NestedRules
    {
        return Rule::forEach(function ($value, $attribute) use ($args, $extraRules) {
            return [
                'bail',
                'filled',
                new RequiredIf(Arr::get($args, str_replace('.tokenId', '', $attribute))),
                new TokenEncodeDoesNotExistInCollection(),
                ...$extraRules,
            ];
        });
    }

    /**
     * Get token encoder validation rules.
     */
    public function getTokenEncoderRules(?string $attribute)
    {
        $attribute = $attribute ? "{$attribute}." : '';
        $encoders = Package::getClassesThatImplementInterface(Encoder::class);

        return $encoders->mapWithKeys(fn ($encoder) => collect($encoder::getRules())->mapWithKeys(fn ($value, $key) => [$attribute . $key => $value])->all());
    }
}
