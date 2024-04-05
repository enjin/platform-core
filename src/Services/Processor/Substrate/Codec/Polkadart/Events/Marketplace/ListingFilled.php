<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class ListingFilled extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly string $buyer;
    public readonly string $amountFilled;
    public readonly string $amountRemaining;
    public readonly string $protocolFee;
    public readonly string $royalty;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = is_string($value = $self->getValue($data, ['listing_id', '0'])) ? HexConverter::prefix($value) : HexConverter::bytesToHex($value);
        $self->buyer = Account::parseAccount($self->getValue($data, ['buyer', '1']));
        $self->amountFilled = $self->getValue($data, ['amount_filled', '2']);
        $self->amountRemaining = $self->getValue($data, ['amount_remaining', '3']);
        $self->protocolFee = $self->getValue($data, ['protocol_fee', '4']);
        $self->royalty = $self->getValue($data, ['royalty', '5']);

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'listing_id', 'value' => $this->listingId],
            ['type' => 'buyer', 'value' => $this->buyer],
            ['type' => 'amount_filled', 'value' => $this->amountFilled],
            ['type' => 'amount_remaining', 'value' => $this->amountRemaining],
            ['type' => 'protocol_fee', 'value' => $this->protocolFee],
            ['type' => 'royalty', 'value' => $this->royalty],
        ];
    }
}

/* Example 1
  {
        "phase": {
            "ApplyExtrinsic": 5
        },
        "event": {
            "Marketplace": {
                "ListingFilled": {
                    "listing_id": "9102fb9f5e5d05051caad813aa0e12e9a4317fa5da41391d2ef0987705e704ea",
                    "buyer": "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48",
                    "amount_filled": "1",
                    "amount_remaining": "0",
                    "protocol_fee": "25000000000000000",
                    "royalty": "0"
                }
            }
        },
        "topics": []
    },
*/
