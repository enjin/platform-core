<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Enums\Substrate\DispatchCall;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidMutation implements DataAwareRule, ValidationRule
{
    /**
     * The data being validated.
     */
    protected array $data;

    /**
     * Set the data being validated.
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        match (Arr::get($this->data, 'dispatch.call')) {
            DispatchCall::FUEL_TANKS->name => $this->validateQuery('fuel-tanks', $value, $fail),
            DispatchCall::MARKETPLACE->name => $this->validateQuery('marketplace', $value, $fail),
            DispatchCall::MULTI_TOKENS->name => $this->validateQuery('primary', $value, $fail),
        };
    }

    /**
     * Validate the query.
     */
    protected function validateQuery(string $schema, string $query, Closure $fail): void
    {
        if ($node = Parser::parse($query)) {
            $names = collect(config("graphql.schemas.{$schema}.mutation"))
                ->map(fn ($class) => resolve($class)->name)
                ->toArray();
            if (!in_array(
                $node->definitions->offsetGet(0)?->selectionSet?->selections?->offsetGet(0)?->name?->value,
                $names
            )) {
                $fail('enjin-platform::validation.valid_mutation')->translate();

                return;
            }
        }

        $positon = strpos($query, 'encodedData');
        if ($positon === false || $query[$positon - 1] === '$') {
            $fail('enjin-platform::validation.valid_mutation.encodedData')->translate();

            return;
        }

        $positon = strpos($query, 'id');
        if ($positon === false || $query[$positon - 1] === '$') {
            $fail('enjin-platform::validation.valid_mutation.encodedData')->translate();
        }
    }
}
