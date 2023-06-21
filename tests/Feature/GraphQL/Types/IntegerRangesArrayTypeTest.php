<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Types;

class IntegerRangesArrayTypeTest extends TestCase
{
    public function test_integer_is_expanded_to_array()
    {
        $this->resolveType('IntegerRangesArray', ['1'])
            ->assertScalarValueEquals([1]);
    }

    public function test_integer_range_is_expanded_to_array()
    {
        $this->resolveType('IntegerRangesArray', ['1..3', '5'])
            ->assertScalarValueEquals([1, 2, 3, 5]);
    }

    public function test_negative_integer_range_is_expanded_to_array()
    {
        $this->resolveType('IntegerRangesArray', ['-4', '-1..2', '5'])
            ->assertScalarValueEquals([-4, -1, 0, 1, 2, 5]);
    }

    public function test_integer_array_is_serialized_to_ranges()
    {
        $this->resolveType('IntegerRangesArray', [1, 2, 3, 5])
            ->assertSerializedScalarValueEquals(['1..3', '5']);
    }

    public function test_big_integer_range_is_expanded_to_array()
    {
        $this->resolveType('IntegerRangesArray', ['340282366920938463463374607431768211450', '340282366920938463463374607431768211453..340282366920938463463374607431768211455'])
            ->assertScalarValueEquals(['340282366920938463463374607431768211450', '340282366920938463463374607431768211453', '340282366920938463463374607431768211454', '340282366920938463463374607431768211455']);
    }

    public function test_big_integer_array_is_serialized_to_ranges()
    {
        $this->resolveType('IntegerRangesArray', ['340282366920938463463374607431768211450', '340282366920938463463374607431768211453', '340282366920938463463374607431768211454', '340282366920938463463374607431768211455'])
            ->assertSerializedScalarValueEquals(['340282366920938463463374607431768211450', '340282366920938463463374607431768211453..340282366920938463463374607431768211455']);
    }

    public function test_inverted_integer_range_array_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer range: ["3..1","5"]');

        $this->resolveType('IntegerRange', ['3..1', '5'])
            ->assertScalarValueEquals([1, 2, 3, 5]);
    }

    public function test_non_array_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer ranges array: "1..3"');

        $this->resolveType('IntegerRangesArray', '1..3')
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_float_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer ranges array: ["1.3","5"]');

        $this->resolveType('IntegerRangesArray', ['1.3', '5'])
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_integer_range_with_extra_dot_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer ranges array: ["1...3","5"]');

        $this->resolveType('IntegerRangesArray', ['1...3', '5'])
            ->assertScalarValueEquals([1, 2, 3]);
    }

    public function test_invalid_input_fails()
    {
        $this->expectExceptionMessage('Cannot represent following value as integer ranges array: ["a","16"]');

        $this->resolveType('IntegerRangesArray', ['a', '16'])
            ->assertScalarValueEquals([10]);
    }
}
