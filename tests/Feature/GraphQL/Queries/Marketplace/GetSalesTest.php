<?php

namespace Enjin\Platform\Marketplace\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Marketplace\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Providers\Faker\SubstrateProvider;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetSalesTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'GetSales';

    public function test_it_can_get_sales(): void
    {
        $listings = $this->createListing(fake()->numberBetween(1, 100));
        $response = $this->graphql(
            $this->method,
            ['accounts' => [$this->wallet->address]]
        );
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql(
            $this->method,
            ['listingIds' => $listings->pluck('listing_chain_id')->toArray()]
        );
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql(
            $this->method,
            ['ids' => $listings->pluck('id')->toArray()]
        );
        $this->assertNotEmpty($response['totalCount']);
    }

    public function test_it_will_fail_with_invalid_parameter_accounts(): void
    {
        $response = $this->graphql(
            $this->method,
            ['accounts' => [Str::random(300)]],
            true
        );
        $this->assertArrayContainsArray(
            ['accounts.0' => ['The accounts.0 field must not be greater than 255 characters.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['accounts' => [Str::random(255)]],
            true
        );
        $this->assertArrayContainsArray(
            ['accounts.0' => ['The accounts.0 is not a valid substrate address.']],
            $response['error']
        );

        $provider = resolve(SubstrateProvider::class);
        $response = $this->graphql(
            $this->method,
            ['accounts' => Collection::range(1, 1001)->map(fn ($val) => $provider->public_key())->toArray()],
            true
        );
        $this->assertArrayContainsArray(
            ['accounts' => ['The accounts field must not have more than 1000 items.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_listing_id(): void
    {
        $response = $this->graphql(
            $this->method,
            ['listingIds' => [Str::random(300)]],
            true
        );
        $this->assertArrayContainsArray(
            ['listingIds.0' => ['The listingIds.0 field must not be greater than 255 characters.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['listingIds' => Collection::range(1, 1001)->map(fn ($val) => (string) $val)->toArray()],
            true
        );
        $this->assertArrayContainsArray(
            ['listingIds' => ['The listing ids field must not have more than 1000 items.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_ids(): void
    {
        $response = $this->graphql(
            $this->method,
            ['ids' => [Hex::MAX_UINT256 + 1]],
            true
        );
        $this->assertEquals(
            'Variable "$ids" got invalid value 1.1579208923732E+77 at "ids[0]"; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['ids' => Collection::range(1, 1001)->toArray()],
            true
        );
        $this->assertArrayContainsArray(
            ['ids' => ['The ids field must not have more than 1000 items.']],
            $response['error']
        );
    }
}
