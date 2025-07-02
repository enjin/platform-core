<?php

namespace Enjin\Platform\Providers\Faker;

use Crypto\sr25519;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Support\Blake2;
use Enjin\Platform\Support\SS58Address;
use Exception;
use Faker\Provider\Base;
use SodiumException;

class SubstrateProvider extends Base
{
    public function hash(): string
    {
        return HexConverter::prefix(fake()->sha256());
    }

    public function signature(): string
    {
        return HexConverter::prefix(fake()->sha256() . fake()->sha256());
    }

    public function fee_details(): array
    {
        return [
            'baseFee' => HexConverter::intToHexPrefixed($base = fake()->numberBetween()),
            'lenFee' => HexConverter::intToHexPrefixed($len = fake()->numberBetween()),
            'adjustedWeightFee' => HexConverter::intToHexPrefixed($adjusted = fake()->numberBetween()),
            'fakeSum' => (string) ($base + $len + $adjusted),
        ];
    }

    /**
     * Get a random substrate public key.
     */
    public function public_key(): string
    {
        $publicKey = null;

        while ($publicKey === null) {
            try {
                $key = HexConverter::hexToBytes($hexKey = bin2hex(random_bytes(32)));

                if (SS58Address::encode($key)) {
                    $publicKey = HexConverter::prefix($hexKey);
                }
            } catch (Exception) {
            }
        }

        return $publicKey;
    }

    /**
     * Get a random substrate address.
     */
    public function chain_address(): string
    {
        $address = null;

        while ($address === null) {
            try {
                $key = HexConverter::hexToBytes(bin2hex(random_bytes(32)));
                $address = SS58Address::encode($key);
            } catch (Exception) {
            }
        }

        return $address;
    }

    /**
     * Get a random substrate address with a signed message using ed25519.
     */
    public function ed25519_signature(string $message, ?bool $isCode = false): array
    {
        $address = null;

        while ($address === null) {
            try {
                $keypair = sodium_crypto_sign_keypair();
                $publicKey = sodium_crypto_sign_publickey($keypair);

                $signature = $isCode ? $this->signWithCode($message, $keypair) : $this->signWithMessage($message, $keypair);
                $address = SS58Address::encode(HexConverter::hexToBytes($publicKey = bin2hex($publicKey)));

                return [
                    'address' => $address,
                    'publicKey' => HexConverter::prefix($publicKey),
                    'signature' => $signature,
                ];
            } catch (Exception) {
            }
        }

        return [];
    }

    /**
     * Get a random substrate address with a signed message using sr25519.
     *
     * @throws SodiumException|PlatformException
     */
    public function sr25519_signature(string $message, ?bool $isCode = false): array
    {
        $sr = new sr25519();
        $seed = sodium_crypto_sign_publickey(sodium_crypto_sign_keypair());
        $pair = $sr->InitKeyPair(bin2hex($seed));

        $message = $isCode ? Blake2::hash(HexConverter::stringToHex('Enjin Signed Message:' . $message)) : $message;

        return [
            'address' => SS58Address::encode($pair->publicKey),
            'publicKey' => HexConverter::prefix($pair->publicKey),
            'signature' => $sr->Sign($pair, HexConverter::prefix($message)),
        ];
    }

    /**
     * Sign a message using the provided keypair.
     *
     * @throws SodiumException
     */
    public function sign(string $message, string $keypair): string
    {
        $privateKey = sodium_crypto_sign_secretkey($keypair);

        return HexConverter::prefix(bin2hex(sodium_crypto_sign_detached(sodium_hex2bin(HexConverter::unPrefix($message)), $privateKey)));
    }

    /**
     * Sign a code using the provided keypair.
     *
     * @throws SodiumException
     */
    public function signWithCode(string $code, ?string $keypair = null): string
    {
        $keypair ??= sodium_crypto_sign_keypair();
        $message = Blake2::hash(HexConverter::stringToHex('Enjin Signed Message:' . $code));

        return $this->sign($message, $keypair);
    }

    /**
     * Sign a message using the provided keypair.
     *
     * @throws SodiumException
     */
    public function signWithMessage(string $message, ?string $keypair = null): string
    {
        $keypair ??= sodium_crypto_sign_keypair();

        return $this->sign($message, $keypair);
    }
}
