<?php

namespace Enjin\Platform\Marketplace\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Marketplace\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Str;

class GetListingTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'GetListing';

    public function test_it_can_get_listing(): void
    {
        $listing = $this->createListing();
        $response = $this->graphql(
            $this->method,
            ['id' => $listing->id],
        );
        $this->assertNotEmpty($response);

        $response = $this->graphql(
            $this->method,
            ['listingId' => $listing->listing_chain_id],
        );
        $this->assertNotEmpty($response);
    }

    public function test_it_will_fail_with_invalid_parameter_id(): void
    {
        $response = $this->graphql($this->method, ['id' => 0], true);
        $this->assertArrayContainsArray(
            ['id' => ['The selected id is invalid.']],
            $response['error']
        );

        $response = $this->graphql($this->method, ['id' => null, 'listingId' => null], true);
        $this->assertArrayContainsArray(
            [
                'id' => ['The id field is required when listing id is not present.'],
                'listingId' => ['The listing id field is required when id is not present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, ['id' => Hex::MAX_UINT256 + 1], true);
        $this->assertEquals(
            'Variable "$id" got invalid value 1.1579208923732E+77; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_listing_id(): void
    {
        $response = $this->graphql($this->method, ['listingId' => fake()->text(255)], true);
        $this->assertArrayContainsArray(
            ['listingId' => ['The selected listing id is invalid.']],
            $response['error']
        );

        $response = $this->graphql($this->method, ['listingId' => Str::random(256)], true);
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing id field must not be greater than 255 characters.']],
            $response['error']
        );
    }
}
