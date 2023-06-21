<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Types;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Rebing\GraphQL\Support\Facades\GraphQL;

class TestCase extends BaseTestCase
{
    /**
     * Creates a new test type.
     */
    public function createTestType(string $name): TestType
    {
        return new TestType(GraphQL::type($name, true));
    }

    /**
     * Resolves a type.
     */
    public function resolveType(string $name, $subject): TestType
    {
        return $this->createTestType($name)->resolve($subject);
    }

    /**
     * Sets the default schema.
     */
    public function schema(string $name): self
    {
        Config::set('graphql.default_schema', $name);

        return $this;
    }
}
