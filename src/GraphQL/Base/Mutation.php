<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Base;

use Illuminate\Support\Str;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Support\Mutation as BaseMutation;

abstract class Mutation extends BaseMutation
{
    /**
     * Get the blockchain method name from the graphql mutation name.
     */
    public function getMethodName(): string
    {
        return Str::camel($this->attributes()['name']);
    }

    /**
     * Get the graphql mutation name.
     */
    public function getMutationName(): string
    {
        return $this->attributes()['name'];
    }

    /**
     * Validate arguments base from the rules.
     */
    protected function validateArguments(array $arguments, array $rules): void
    {
        $validator = $this->getValidator($arguments, $rules);

        if ($validator->stopOnFirstFailure(false)->fails()) {
            throw new ValidationError('validation', $validator);
        }
    }
}
