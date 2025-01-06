<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Types;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Testing\Assert as PHPUnit;

class TestType
{
    private $subject;
    private $context;

    public function __construct(private readonly Type $type) {}

    public function resolve($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function actingAs(Authorizable $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function assertHasField(string $field): self
    {
        try {
            $hasField = (bool) $this->resolveField($field);
        } catch (InvariantViolation) {
            $hasField = false;
        }

        PHPUnit::assertTrue($hasField);

        return $this;
    }

    public function assertDoesntHaveField(string $field): self
    {
        try {
            $hasField = (bool) $this->resolveField($field);
        } catch (InvariantViolation) {
            $hasField = false;
        }

        PHPUnit::assertFalse($hasField);

        return $this;
    }

    public function assertFieldEquals(string $field, $expected): self
    {
        PHPUnit::assertEquals($expected, $this->resolveField($field));

        return $this;
    }

    public function assertFieldNull(string $field): self
    {
        PHPUnit::assertNull($this->resolveField($field));

        return $this;
    }

    public function assertScalarValueEquals($expected): self
    {
        PHPUnit::assertEquals($expected, $this->type->parseValue($this->subject));

        return $this;
    }

    public function assertSerializedScalarValueEquals($expected): self
    {
        PHPUnit::assertEquals($expected, $this->type->serialize($this->subject));

        return $this;
    }

    private function getFieldResolver(string $field): mixed
    {
        return $this->type->getField($field)->resolveFn;
    }

    private function resolveField(string $field, array $args = []): mixed
    {
        $resolver = $this->getFieldResolver($field);

        return $resolver($this->subject, $args, $this->context);
    }
}
