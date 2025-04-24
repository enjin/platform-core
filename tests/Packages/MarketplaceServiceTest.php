<?php

namespace Enjin\Platform\Tests\Packages;

use Enjin\Platform\Models\Laravel\MarketplaceListing;
use Enjin\Platform\Services\MarketplaceService;
use Enjin\Platform\Tests\Packages\Traits\CreateCollectionData;
use Enjin\Platform\Tests\TestCase;

class MarketplaceServiceTest extends TestCase
{
    use CreateCollectionData;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->createCollectionData();
    }

    public function test_it_can_create_records()
    {
        $service = resolve(MarketplaceService::class);
        $listing = MarketplaceListing::factory()->make(['seller_wallet_id' => $this->wallet->id]);
        $this->assertNotEmpty($model = $service->store($listing->toArray()));
        $this->assertNotEmpty($service->get($model->listing_chain_id));
        $this->assertTrue($service->insert(
            MarketplaceListing::factory()->make(['seller_wallet_id' => $this->wallet->id])->toArray()
        ));
    }
}
