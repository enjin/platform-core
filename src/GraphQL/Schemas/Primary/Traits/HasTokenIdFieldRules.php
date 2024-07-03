<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Enjin\Platform\Package;
use Enjin\Platform\Rules\TokenEncodeDoesNotExistInCollection;
use Enjin\Platform\Rules\TokenEncodeExistInCollection;
use Enjin\Platform\Services\Token\Encoder;

trait HasTokenIdFieldRules
{
    /**
     * Get optional token fields.
     */
    public function getOptionalTokenFieldRules(
        ?string $attribute = null,
        array $encodableTokenIdRules = []
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodeTokenIdRules($attribute, $encodableTokenIdRules, true),
        ];
    }

    /**
     * Get token fields rule.
     */
    public function getTokenFieldRules(
        ?string $attribute = null,
        array $encodableTokenIdRules = [],
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodeTokenIdRules($attribute, $encodableTokenIdRules),
        ];
    }

    /**
     * Get optional token fields with exist rule.
     */
    public function getOptionalTokenFieldRulesExist(
        ?string $attribute = null,
        array $encodableTokenIdRules = [],
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodeTokenIdRuleExist($attribute, $encodableTokenIdRules, true),
        ];
    }

    /**
     * Get token fields with exist rule.
     */
    public function getTokenFieldRulesExist(
        ?string $attribute = null,
        array $encodableTokenIdRules = [],
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodeTokenIdRuleExist($attribute, $encodableTokenIdRules),
        ];
    }

    /**
     * Get token fields with doesn't exist rule.
     */
    public function getTokenOptionalFieldRulesDoesntExist(
        ?string $attribute = null,
        array $encodableTokenIdRules = [],
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodeTokenIdRuleDoesntExist($attribute, $encodableTokenIdRules, true),
        ];
    }

    /**
     * Get token fields with doesn't exist rule.
     */
    public function getTokenFieldRulesDoesntExist(
        ?string $attribute = null,
        array $encodableTokenIdRules = [],
    ): array {
        $attribute = $attribute ? "{$attribute}." : $attribute;

        return [
            ...$this->getTokenEncoderRules("{$attribute}tokenId"),
            "{$attribute}tokenId" => $this->getEncodeTokenIdRuleDoesntExist($attribute, $encodableTokenIdRules),
        ];
    }

    /**
     * Get token ID rules.
     */
    public function getEncodeTokenIdRules(?string $attribute = null, array $extraRules = [], ?bool $isOptional = false): array
    {
        return [
            $isOptional ? 'filled' : 'required',
            ...$extraRules,
        ];
    }

    /**
     * Get token ID rules with exist.
     */
    public function getEncodeTokenIdRuleExist(?string $attribute = null, array $extraRules = [], ?bool $isOptional = false): array
    {
        return [
            'bail',
            $isOptional ? 'filled' : 'required',
            new TokenEncodeExistInCollection(),
            ...$extraRules,
        ];
    }

    /**
     * Get token ID rules with doesn't exist.
     */
    public function getEncodeTokenIdRuleDoesntExist(?string $attribute = null, array $extraRules = [], ?bool $isOptional = false): array
    {
        return [
            'bail',
            $isOptional ? 'filled' : 'required',
            new TokenEncodeDoesNotExistInCollection(),
            ...$extraRules,
        ];
    }

    /**
     * Get the encodable token id rules.
     */
    public function getTokenEncoderRules($attribute)
    {
        $encoders = Package::getClassesThatImplementInterface(Encoder::class);

        return $encoders->mapWithKeys(fn ($encoder) => collect($encoder::getRules())->mapWithKeys(fn ($value, $key) => ["{$attribute}.{$key}" => $value])->all());
    }
}
