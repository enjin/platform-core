<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Types;

class IntegerRangeTypeTest extends TestCase
{
    public function test_integer_is_expanded_to_array()
    {
        $this->resolveType('IntegerRange', '1')
            ->assertScalarValueEquals([1]);
    }

    public function test_negative_integer_is_expanded_to_array()
    {
        $this->resolveType('IntegerRange', '-1')
            ->assertScalarValueEquals([-1]);
    }

    public function test_integer_range_is_expanded_to_array()
    {
        $this->resolveType('IntegerRange', '1..3')
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_negative_integer_range_is_expanded_to_array()
    {
        $this->resolveType('IntegerRange', '-1..2')
            ->assertScalarValueEquals([-1, 0, 1, 2]);
    }

    public function test_big_integer_range_is_expanded_to_array()
    {
        $this->resolveType('IntegerRange', '340282366920938463463374607431768211453..340282366920938463463374607431768211455')
            ->assertScalarValueEquals(['340282366920938463463374607431768211453', '340282366920938463463374607431768211454', '340282366920938463463374607431768211455']);
    }

    public function test_integer_array_is_serialized_to_range()
    {
        $this->resolveType('IntegerRange', [1, 2, 3])
            ->assertSerializedScalarValueEquals('1..3');
    }

    public function test_big_integer_array_is_serialized_to_range()
    {
        $this->resolveType('IntegerRange', ['340282366920938463463374607431768211453', '340282366920938463463374607431768211454', '340282366920938463463374607431768211455'])
            ->assertSerializedScalarValueEquals('340282366920938463463374607431768211453..340282366920938463463374607431768211455');
    }

    public function test_inverted_integer_range_array_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer range: "3..1"');

        $this->resolveType('IntegerRange', '3..1')
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_integer_range_array_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer range: ["1..3","5"]');

        $this->resolveType('IntegerRange', ['1..3', '5'])
            ->assertScalarValueEquals([1, 2, 3, 5]);
    }

    public function test_float_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer range: "1.3"');

        $this->resolveType('IntegerRange', '1.3')
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_integer_range_with_extra_dot_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer range: "1...3"');

        $this->resolveType('IntegerRange', '1...3')
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_invalid_input_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer range: "a"');

        $this->resolveType('IntegerRange', 'a')
            ->assertScalarValueEquals([10]);
    }
}
