<?php

namespace Enjin\Platform\Marketplace\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Marketplace\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Facades\Schema;

class GetSaleTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'GetSale';

    public function test_it_can_get_sale(): void
    {
        $listing = $this->createListing();
        $response = $this->graphql(
            $this->method,
            ['id' => $listing->sales->first()->id]
        );

        $this->assertNotEmpty($response);
    }

    public function test_it_can_get_a_sale_with_no_listing(): void
    {
        Schema::disableForeignKeyConstraints();
        $listing = $this->createListing();
        $saleId = $listing->sales->first()->id;
        $listing->delete();
        Schema::enableForeignKeyConstraints();

        $response = $this->graphql(
            $this->method,
            ['id' => $saleId]
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

        $response = $this->graphql($this->method, ['id' => null], true);
        $this->assertEquals(
            'Variable "$id" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        $response = $this->graphql($this->method, ['id' => Hex::MAX_UINT256 + 1], true);
        $this->assertEquals(
            'Variable "$id" got invalid value 1.1579208923732E+77; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );
    }
}
