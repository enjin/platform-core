<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Base;

use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Support\Query as BaseQuery;

abstract class Query extends BaseQuery
{
    /**
     * Validate arguments base from the rules.
     */
    #[\Override]
    protected function validateArguments(array $arguments, array $rules): void
    {
        $validator = $this->getValidator($arguments, $rules);

        if ($validator->stopOnFirstFailure(false)->fails()) {
            throw new ValidationError('validation', $validator);
        }
    }
}
