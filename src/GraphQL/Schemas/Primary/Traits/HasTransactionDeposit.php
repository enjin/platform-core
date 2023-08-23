<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Codec\Utils;
use Enjin\Platform\Enums\Substrate\TransactionDeposit;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use GMP;
use Illuminate\Support\Arr;

trait HasTransactionDeposit
{
    use HasEncodableTokenId;

    protected function getDeposit($args): ?string
    {
        return match ((new \ReflectionClass($this))->getShortName()) {
            'CreateCollectionMutation' => $this->getCreateCollectionDeposit($args),
            'CreateTokenMutation' => $this->getCreateTokenDeposit($args),
            'MintTokenMutation' => $this->getMintTokenDeposit($args),
            'SetCollectionAttributeMutation', 'SetTokenAttributeMutation' => $this->getSetAttributeDeposit($args),
            'BatchSetAttributeMutation' => $this->getBatchSetAttributeDeposit($args),
            'BatchMintMutation' => $this->getBatchMintDeposit($args),
            default => null,
        };
    }

    protected function calculateDepositForAttributes(array $attributes): GMP
    {
        if (empty($attributes)) {
            return gmp_init(0);
        }

        $totalBytes = collect($attributes)->sum(
            fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
        );

        $depositPerByte = gmp_mul(TransactionDeposit::ATTRIBUTE_PER_BYTE->toGMP(), $totalBytes);

        return gmp_add(TransactionDeposit::ATTRIBUTE_BASE->toGMP(), $depositPerByte);
    }

    protected function getCreateCollectionDeposit(array $args): string
    {
        $attributesDeposit = $this->calculateDepositForAttributes($args['attributes']);
        $totalDeposit = gmp_add(TransactionDeposit::COLLECTION->toGMP(), $attributesDeposit);

        return gmp_strval($totalDeposit);
    }

    protected function getCreateTokenDeposit(array $args): ?string
    {
        $initialSupply = gmp_init($args['params']['initialSupply']);
        $unitPrice = gmp_init($args['params']['unitPrice'] ?? '10000000000000000');
        $tokenDeposit = gmp_mul($initialSupply, $unitPrice);
        $attributesDeposit = $this->calculateDepositForAttributes($args['params']['attributes']);

        return gmp_strval(gmp_add($tokenDeposit, $attributesDeposit));
    }

    protected function getMintTokenDeposit(array $args): ?string
    {
        $collection = Collection::firstWhere('collection_chain_id', $args['collectionId']);
        $tokenId = $this->encodeTokenId($args['params']);
        $token = Token::firstWhere([
            'collection_id' => $collection->id,
            'token_chain_id' => $tokenId,
        ]);

        $unitPrice = $token?->unit_price ?? '10000000000000000';
        $extraUnitPrice = Arr::get($args, 'params.unitPrice', $unitPrice);
        $extra = 0;

        if (Arr::get($args, 'params.unitPrice')) {
            $extra = gmp_mul(gmp_sub($extraUnitPrice, $unitPrice), $token?->supply ?? 1);
            $unitPrice = Arr::get($args, 'params.unitPrice');
        }

        return gmp_strval(gmp_add(gmp_mul($unitPrice, $args['params']['amount']), $extra));
    }

    protected function getSetAttributeDeposit(array $args): ?string
    {
        $attributeDeposit = $this->calculateDepositForAttributes([
            [
                'key' => $args['key'],
                'value' => $args['value'],
            ],
        ]);

        return gmp_strval($attributeDeposit);
    }

    protected function getBatchSetAttributeDeposit(array $args): ?string
    {
        $attributesDeposit = $this->calculateDepositForAttributes($args['attributes']);

        return gmp_strval($attributesDeposit);
    }

    protected function getBatchMintDeposit(array $args): ?string
    {
        $totalDeposit = gmp_init(0);
        collect($args['recipients'])->each(
            function ($rcpt) use ($args, &$totalDeposit) {
                if (isset($rcpt['createParams'])) {
                    $initialSupply = gmp_init($rcpt['createParams']['initialSupply']);
                    $unitPrice = gmp_init($rcpt['createParams']['unitPrice'] ?? '10000000000000000');
                    $tokenDeposit = gmp_mul($initialSupply, $unitPrice);
                    $totalBytes = collect($rcpt['createParams']['attributes'])->sum(
                        fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
                    );
                    $attributes = $totalBytes > 0 ? gmp_add(TransactionDeposit::ATTRIBUTE_BASE->toGMP(), gmp_mul(TransactionDeposit::ATTRIBUTE_PER_BYTE->toGMP(), $totalBytes)) : gmp_init(0);
                    $deposit = gmp_add($tokenDeposit, $attributes);
                    $totalDeposit = gmp_add($totalDeposit, $deposit);
                } else {
                    $collection = Collection::firstWhere('collection_chain_id', $args['collectionId']);
                    $tokenId = $this->encodeTokenId($rcpt['mintParams']['tokenId']);
                    $token = Token::firstWhere([
                        'collection_id' => $collection->id,
                        'token_chain_id' => $tokenId,
                    ]);

                    $unitPrice = $token?->unit_price ?? '10000000000000000';
                    $extraUnitPrice = Arr::get($rcpt, 'mintParams.unitPrice', $unitPrice);
                    $extra = gmp_mul(gmp_sub($extraUnitPrice, $unitPrice), $token?->supply ?? 1);

                    if (Arr::get($rcpt, 'mintParams.unitPrice')) {
                        $unitPrice = Arr::get($rcpt, 'mintParams.unitPrice');
                    }

                    $deposit = gmp_add(gmp_mul($unitPrice, $rcpt['mintParams']['amount']), $extra);
                    $totalDeposit = gmp_add($totalDeposit, $deposit);
                }
            }
        );

        return gmp_strval($totalDeposit);
    }
}
