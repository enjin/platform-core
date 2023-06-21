<?php

namespace Enjin\Platform\Services\Blockchain\Interfaces;

interface BlockchainServiceInterface
{
    /**
     * Call the method in the client service.
     */
    public function callMethod(string $name, array $args): mixed;

    /**
     * Verify the message.
     */
    public function verifyMessage(string $message, string $signature, string $publicKey, string $cryptoSignatureType): bool;

    /**
     * Append balance details to the wallet object.
     */
    public function walletWithBalanceAndNonce($wallet): mixed;
}
