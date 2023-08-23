<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Codec\Utils;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Illuminate\Support\Arr;

trait HasTransactionDeposit
{
    use HasEncodableTokenId;

    /**
     * Gets the deposit necessary to execute this transaction.
     */
    protected function getCreateCollectionDeposit(array $args): ?string
    {
        ray($args);

        $collectionCreation = gmp_init('25000000000000000000');
        $depositBase = gmp_init('200000000000000000');
        $depositPerByte = gmp_init('100000000000000');
        $totalBytes = collect($args['attributes'])->sum(
            fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
        );
        $attributes = $totalBytes > 0 ? gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)) : gmp_init(0);

        return gmp_strval(gmp_add($collectionCreation, $attributes));
    }

    protected function getCreateTokenDeposit(array $args): ?string
    {
        ray($args);

        $initialSupply = gmp_init($args['params']['initialSupply']);
        $unitPrice = gmp_init($args['params']['unitPrice'] ?? '10000000000000000');
        $tokenDeposit = gmp_mul($initialSupply, $unitPrice);
        $depositBase = gmp_init('200000000000000000');
        $depositPerByte = gmp_init('100000000000000');
        $totalBytes = collect($args['params']['attributes'])->sum(
            fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
        );
        $attributes = $totalBytes > 0 ? gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)) : gmp_init(0);

        return gmp_strval(gmp_add($tokenDeposit, $attributes));
    }

        protected function getMintTokenDeposit(array $args): ?string
        {
            ray($args);

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

    protected function getSetCollectionAttributeDeposit(array $args): ?string
    {
        ray($args);

        $depositBase = gmp_init('200000000000000000');
        $depositPerByte = gmp_init('100000000000000');
        $totalBytes = count(Utils::string2ByteArray($args['key'])) + count(Utils::string2ByteArray($args['value']));

        return gmp_strval(gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)));
    }

    protected function getSetTokenAttributeDeposit(array $args): ?string
    {
        ray($args);

        $depositBase = gmp_init('200000000000000000');
        $depositPerByte = gmp_init('100000000000000');
        $totalBytes = count(Utils::string2ByteArray($args['key'] . $args['value']));

        return gmp_strval(gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)));
    }

    protected function getBatchSetAttributeDeposit(array $args): ?string
    {
        ray($args);

        $depositBase = gmp_init('200000000000000000');
        $depositPerByte = gmp_init('100000000000000');
        $totalBytes = collect($args['attributes'])->sum(
            fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
        );

        return gmp_strval(gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)));
    }

    protected function getBatchMintDeposit(array $args): ?string
    {
        ray($args);

        $totalDeposit = gmp_init(0);
        collect($args['recipients'])->each(
            function ($rcpt) use ($args, &$totalDeposit) {
                if (isset($rcpt['createParams'])) {
                    $initialSupply = gmp_init($rcpt['createParams']['initialSupply']);
                    $unitPrice = gmp_init($rcpt['createParams']['unitPrice'] ?? '10000000000000000');
                    $tokenDeposit = gmp_mul($initialSupply, $unitPrice);
                    $depositBase = gmp_init('200000000000000000');
                    $depositPerByte = gmp_init('100000000000000');
                    $totalBytes = collect($rcpt['createParams']['attributes'])->sum(
                        fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
                    );
                    $attributes = $totalBytes > 0 ? gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)) : gmp_init(0);
                    $deposit = gmp_add($tokenDeposit, $attributes);
                    $totalDeposit = gmp_add($totalDeposit, $deposit);
                } else {
                    $collection = \Enjin\Platform\Models\Collection::firstWhere('collection_chain_id', $args['collectionId']);
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
