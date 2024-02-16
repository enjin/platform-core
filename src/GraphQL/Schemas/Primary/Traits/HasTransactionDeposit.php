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

    protected function calculateDepositForToken(array $args): GMP
    {
        $initialSupply = gmp_init($args['initialSupply']);
        $unitPrice = gmp_init($args['unitPrice'] ?? TransactionDeposit::TOKEN_ACCOUNT->value);

        return gmp_mul($initialSupply, $unitPrice);
    }

    protected function getCreateTokenDeposit(array $args): ?string
    {
        $tokenDeposit = $this->calculateDepositForToken($args['params']);
        $attributesDeposit = $this->calculateDepositForAttributes($args['params']['attributes']);

        return gmp_strval(gmp_add($tokenDeposit, $attributesDeposit));
    }

    protected function calculateDepositForMint(string $collectionId, array $params): GMP
    {
        $token = null;
        if ($collection = Collection::firstWhere('collection_chain_id', $collectionId)) {
            $token = Token::firstWhere([
                'collection_id' => $collection->id,
                'token_chain_id' => $tokenId = $this->encodeTokenId($params),
            ]);
        }

        $unitPrice = $token?->unit_price ?? TransactionDeposit::TOKEN_ACCOUNT->value;
        $extraUnitPrice = Arr::get($params, 'unitPrice', $unitPrice);
        $extra = 0;

        if (Arr::get($params, 'unitPrice')) {
            $extra = gmp_mul(gmp_sub($extraUnitPrice, $unitPrice), $token?->supply ?? 1);
            $unitPrice = Arr::get($params, 'unitPrice');
        }

        return gmp_add(gmp_mul($unitPrice, $params['amount']), $extra);
    }

    protected function getMintTokenDeposit(array $args): string
    {
        $mintDeposit = $this->calculateDepositForMint($args['collectionId'], $args['params']);

        return gmp_strval($mintDeposit);
    }

    protected function getSetAttributeDeposit(array $args): string
    {
        $attributeDeposit = $this->calculateDepositForAttributes([
            [
                'key' => $args['key'],
                'value' => $args['value'],
            ],
        ]);

        return gmp_strval($attributeDeposit);
    }

    protected function getBatchSetAttributeDeposit(array $args): string
    {
        $attributesDeposit = $this->calculateDepositForAttributes($args['attributes']);

        return gmp_strval($attributesDeposit);
    }

    protected function getBatchMintDeposit(array $args): string
    {
        $totalDeposit = gmp_init(0);

        collect($args['recipients'])->each(
            function ($rcpt) use ($args, &$totalDeposit) {
                if (isset($rcpt['createParams'])) {
                    $tokenDeposit = $this->calculateDepositForToken($rcpt['createParams']);
                    $attributesDeposit = $this->calculateDepositForAttributes($rcpt['createParams']['attributes']);
                    $totalDeposit = gmp_add($totalDeposit, gmp_add($tokenDeposit, $attributesDeposit));
                } else {
                    $mintDeposit = $this->calculateDepositForMint($args['collectionId'], $rcpt['mintParams']);
                    $totalDeposit = gmp_add($totalDeposit, $mintDeposit);
                }
            }
        );

        return gmp_strval($totalDeposit);
    }
}
