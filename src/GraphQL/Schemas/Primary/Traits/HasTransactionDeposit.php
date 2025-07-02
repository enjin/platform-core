<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Codec\Utils;
use Enjin\Platform\BlockchainConstant;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use GMP;
use ReflectionClass;

trait HasTransactionDeposit
{
    use HasEncodableTokenId;

    protected function getDeposit($args): ?string
    {
        return match (new ReflectionClass($this)->getShortName()) {
            'CreateCollectionMutation' => $this->getCreateCollectionDeposit($args),
            'CreateTokenMutation' => $this->getCreateTokenDeposit($args),
            'MintTokenMutation' => $this->getMintTokenDeposit($args),
            'SetCollectionAttributeMutation', 'SetTokenAttributeMutation' => $this->getSetAttributeDeposit($args),
            'BatchSetAttributeMutation' => $this->getBatchSetAttributeDeposit($args),
            'BatchMintMutation' => $this->getBatchMintDeposit($args),
            'CreateListingMutation' => BlockchainConstant::DEPOSIT_PER_LISTING,
            'CreateFuelTankMutation' => BlockchainConstant::DEPOSIT_PER_FUEL_TANK,
            'AddAccountMutation' => BlockchainConstant::DEPOSIT_PER_TOKEN_ACCOUNT,
            'BatchAddAccountMutation' => $this->getBatchAddFuelTankAccountDeposit($args),
            default => null,
        };
    }

    protected function getBatchAddFuelTankAccountDeposit(array $args): string
    {
        $accountsCount = count($args['userIds'] ?? []);
        $totalDeposit = gmp_mul(BlockchainConstant::DEPOSIT_PER_TOKEN_ACCOUNT, $accountsCount);

        return gmp_strval($totalDeposit);
    }

    protected function calculateDepositForAttributes(array $attributes): GMP
    {
        if (empty($attributes)) {
            return gmp_init(0);
        }

        $totalBytes = collect($attributes)->sum(
            fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
        );

        $depositPerByte = gmp_mul(BlockchainConstant::DEPOSIT_PER_ATTRIBUTE_PER_BYTE, $totalBytes);

        return gmp_add(BlockchainConstant::DEPOSIT_PER_ATTRIBUTE_BASE, $depositPerByte);
    }

    protected function getCreateCollectionDeposit(array $args): string
    {
        $attributesDeposit = $this->calculateDepositForAttributes($args['attributes']);
        $totalDeposit = gmp_add(BlockchainConstant::DEPOSIT_PER_COLLECTION, $attributesDeposit);

        return gmp_strval($totalDeposit);
    }

    protected function calculateDepositForToken(array $args): GMP
    {
        $initialSupply = gmp_init($args['initialSupply']);
        $unitPrice = gmp_init($args['unitPrice'] ?? BlockchainConstant::DEPOSIT_PER_TOKEN_ACCOUNT);

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
        $depositPerTokenAccount = gmp_init(BlockchainConstant::DEPOSIT_PER_TOKEN_ACCOUNT);

        return gmp_mul($depositPerTokenAccount, $params['amount']);
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
            function ($rcpt) use ($args, &$totalDeposit): void {
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
