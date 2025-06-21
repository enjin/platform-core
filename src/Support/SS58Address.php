<?php

namespace Enjin\Platform\Support;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Exceptions\PlatformException;
use Exception;
use SodiumException;
use Tuupola\Base58;

class SS58Address
{
    public const string CONTEXT = '53533538505245';
    public const int PREFIX = 1110;
    public const array ALLOWED_DECODED_LENGTHS = [
        1, 2, 4, 8, 32, 33,
    ];

    /**
     * Compares the two given addresses if they are the same.
     */
    public static function isSameAddress(string $address1, string $address2): bool
    {
        try {
            return self::getPublicKey($address1) === self::getPublicKey($address2);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get the public key from the given address.
     */
    public static function getPublicKey(string|array|null $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        try {
            return HexConverter::prefix(HexConverter::bytesToHex(self::decode($address)));
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Decodes a given address with different format.
     * @throws PlatformException
     */
    public static function decode(string|array $address, ?bool $ignoreChecksum = false, ?int $ss58Format = -1): array
    {
        if (empty($address)) {
            throw new PlatformException(__('enjin-platform::ss58_address.error.invalid_empty_address'));
        }

        if (is_string($address) && ctype_xdigit(HexConverter::unPrefix($address))) {
            return HexConverter::hexToBytes($address);
        }

        if (is_array($address)) {
            foreach ($address as $c) {
                if ($c > 255 || $c < 0) {
                    throw new PlatformException(__('enjin-platform::ss58_address.error.invalid_uint8array'));
                }
            }

            return $address;
        }

        try {
            $base58check = new Base58(['characters' => Base58::BITCOIN]);
            $buffer = $base58check->decode(HexConverter::unPrefix($address));
            $array = unpack('C*', $buffer);
            $bytes = array_values($array);

            [$isValid, $endPos, $ss58Length, $ss58Decoded] = self::checkAddressChecksum($bytes);

            if (!$ignoreChecksum && !$isValid) {
                throw new PlatformException(__('enjin-platform::ss58_address.error.invalid_decoded_address_checksum'));
            }

            if (!in_array($ss58Format, [-1, $ss58Decoded], true)) {
                throw new PlatformException(__('enjin-platform::ss58_address.error.unexpected_format', ['ss58Format' => $ss58Format, 'ss58Decoded' => $ss58Decoded]));
            }

            return array_slice($bytes, $ss58Length, $endPos - $ss58Length);
        } catch (Exception $e) {
            throw new PlatformException(__('enjin-platform::ss58_address.error.cannot_decode_address', ['address' => $address, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Checks the checksum of the given decoded address.
     * @throws SodiumException
     */
    public static function checkAddressChecksum(array $decoded): array
    {
        $ss58Length = $decoded[0] & 0b01000000 ? 2 : 1;

        $ss58Decoded = $ss58Length === 1
            ? $decoded[0]
            : (($decoded[0] & 0b00111111) << 2) | ($decoded[1] >> 6) | (($decoded[1] & 0b00111111) << 8);

        // 32/33 bytes public + 2 bytes checksum and prefix
        $isPublicKey = in_array(count($decoded), [34 + $ss58Length, 35 + $ss58Length]);

        $length = count($decoded) - ($isPublicKey ? 2 : 1);

        // calculates the hash and do the checksum byte checks
        $hash = HexConverter::hexToBytes(bin2hex(sodium_crypto_generichash(
            hex2bin(self::CONTEXT . HexConverter::bytesToHex(array_slice($decoded, 0, $length))),
             '',
            64
        )));

        $isValid = ($decoded[0] & 0b10000000) === 0 && !in_array($decoded[0], [46, 47], true) && (
            $isPublicKey
                ? $decoded[count($decoded) - 2] === $hash[0] && $decoded[count($decoded) - 1] === $hash[1]
                : $decoded[count($decoded) - 1] === $hash[0]
        );

        return [$isValid, $length, $ss58Length, $ss58Decoded];
    }

    /**
     * Checks if the given address is valid.
     */
    public static function isValidAddress(string $address): bool
    {
        try {
            self::encode(self::decode($address));

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Encodes a given address to a SS58 format.
     * @throws PlatformException
     * @throws SodiumException
     */
    public static function encode(string|array|null $key, ?int $ss58Format = null): ?string
    {
        if (empty($key)) {
            return null;
        }

        if ($ss58Format === null) {
            $selectedFormat = sprintf(
                'enjin-platform.chains.supported.substrate.%s.ss58-prefix',
                config('enjin-platform.chains.network')
            );
            $ss58Format = (int) (config($selectedFormat) ?? self::PREFIX);
        }

        if ($ss58Format < 0 || $ss58Format > 16383 || in_array($ss58Format, [46, 47], true)) {
            throw new PlatformException(__('enjin-platform::ss58_address.error.format_out_of_range'));
        }

        $u8a = self::decode($key);
        if (!in_array(count($u8a), self::ALLOWED_DECODED_LENGTHS)) {
            throw new PlatformException(__('enjin-platform::ss58_address.error.valid_key_expected', ['length' => implode(', ', self::ALLOWED_DECODED_LENGTHS)]));
        }

        $prefixBytes = $ss58Format < 64
            ? [$ss58Format]
            : [
                (($ss58Format & 0b0000000011111100) >> 2) | 0b01000000,
                ($ss58Format >> 8) | (($ss58Format & 0b0000000000000011) << 6),
            ];

        $input = array_merge($prefixBytes, $u8a);
        $hash = HexConverter::hexToBytes(bin2hex(sodium_crypto_generichash(
            hex2bin(self::CONTEXT . HexConverter::bytesToHex($input)),
            '',
            64
        )));

        $remove = in_array(count($u8a), [32, 33]) ? 2 : 1;
        $subarray = array_slice($hash, 0, $remove);
        $final = array_merge($input, $subarray);

        return (new Base58(['characters' => Base58::BITCOIN]))->encode(hex2bin(HexConverter::bytesToHex($final)));
    }
}
