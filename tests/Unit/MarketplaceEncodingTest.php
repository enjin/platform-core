<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\Marketplace\Enums\ListingType;
use Enjin\Platform\Marketplace\GraphQL\Mutations\CancelListingMutation;
use Enjin\Platform\Marketplace\GraphQL\Mutations\CreateListingMutation;
use Enjin\Platform\Marketplace\GraphQL\Mutations\FillListingMutation;
use Enjin\Platform\Marketplace\GraphQL\Mutations\FinalizeAuctionMutation;
use Enjin\Platform\Marketplace\GraphQL\Mutations\PlaceBidMutation;
use Enjin\Platform\Marketplace\Models\Substrate\MultiTokensTokenAssetIdParams;
use Enjin\Platform\Marketplace\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Marketplace\Tests\TestCase;

class MarketplaceEncodingTest extends TestCase
{
    protected Codec $codec;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
    }

    public function test_it_can_encode_create_listing()
    {
        $asset = new MultiTokensTokenAssetIdParams(24016, 1);
        $data = TransactionSerializer::encode('CreateListing', CreateListingMutation::getEncodableParams(
            makeAssetId: $asset,
            takeAssetId: $asset,
            amount: 1,
            price: 1,
            salt: 'test',
            startBlock: 100,
            listingData: ['type' => ListingType::AUCTION->name, 'auctionParams' => [
                'startBlock' => 100,
                'endBlock' => 1000,
            ]],
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Marketplace.create_listing', true);
        $this->assertEquals(
            "0x{$callIndex}427701000442770100040404016400000010746573740001a10f00",
            $data
        );
    }

    public function test_it_can_encode_cancel_listing()
    {
        $data = TransactionSerializer::encode('CancelListing', CancelListingMutation::getEncodableParams(
            listingId: '0x002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Marketplace.cancel_listing', true);
        $this->assertEquals(
            "0x{$callIndex}002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7",
            $data
        );
    }

    public function test_it_can_encode_fill_listing()
    {
        $data = TransactionSerializer::encode('FillListing', FillListingMutation::getEncodableParams(
            listingId: '0x002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7',
            amount: 1000
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Marketplace.fill_listing', true);
        $this->assertEquals(
            "0x{$callIndex}002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7a10f00000000",
            $data
        );
    }

    public function test_it_can_encode_finalize_auction()
    {
        $data = TransactionSerializer::encode('FinalizeAuction', FinalizeAuctionMutation::getEncodableParams(
            listingId: '0x002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7'
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Marketplace.finalize_auction', true);
        $this->assertEquals(
            "0x{$callIndex}002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f700000000",
            $data
        );
    }

    public function test_it_can_encode_place_bid()
    {
        $data = TransactionSerializer::encode('PlaceBid', PlaceBidMutation::getEncodableParams(
            listingId: '0x002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7',
            price: 2000
        ));

        $callIndex = $this->codec->encoder()->getCallIndex('Marketplace.place_bid', true);
        $this->assertEquals(
            "0x{$callIndex}002ddf91ca0f13b03541dbddb3a008d8efc975b0044fde799ea7ffe33fdf57f7411f",
            $data
        );
    }
}
