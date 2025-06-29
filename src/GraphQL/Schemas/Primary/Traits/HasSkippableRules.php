<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Enjin\Platform\Exceptions\PlatformException;
use Rebing\GraphQL\Support\Facades\GraphQL;

trait HasSkippableRules
{
    /**
     * Get the skip validation field.
     */
    protected function getSkipValidationField(): array
    {
        return [
            'skipValidation' => [
                'name' => 'skipValidation',
                'description' => __('enjin-platform::mutation.args.skipValidation'),
                'type' => GraphQL::type('Boolean'),
                'defaultValue' => false,
            ],
        ];
    }

    /**
     * Get the validation rules.
     * @throws PlatformException
     */
    protected function rules(array $args = []): array
    {
        return $this->getAllRules($args);
    }

    /**
     * Get all validation rules.
     * @throws PlatformException
     */
    protected function getAllRules(array $args): array
    {
        if (!isset($args['skipValidation'])) {
            throw new PlatformException(__('enjin-platform::error.missing_skip_validation'));
        }

        return $args['skipValidation']
            ? [...$this->rulesCommon($args), ...$this->rulesWithoutValidation($args)]
            : [...$this->rulesCommon($args), ...$this->rulesWithValidation($args)];
    }

    /**
     * Get the common validation rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [];
    }

    /**
     * Get the rules with validation.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [];
    }

    /**
     * Get the rules without validation.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [];
    }
}
