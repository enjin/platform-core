<?php

namespace Enjin\Platform\Services\Database;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Support\Blake2;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Model;

class VerificationService
{
    private string $alphanumerics = 'ABCDEFGHJKLMNPQRTUVWXYZ0123456789';
    private string $letters = 'ABCDEFGHJKLMNPQRTUVWXYZ';
    private ?string $network;
    private ?string $platform;

    /**
     * Create a new instance.
     */
    public function __construct(
        protected WalletService $walletService,
        protected BlockchainServiceInterface $blockchainService
    ) {
        $chain = config('enjin-platform.chains.selected');
        $network = config('enjin-platform.chains.network');

        $this->network = config("enjin-platform.chains.supported.{$chain}.{$network}.network-id");
        $this->platform = config("enjin-platform.chains.supported.{$chain}.{$network}.platform-id");

        $this->walletService = $walletService;
        $this->blockchainService = $blockchainService;
    }

    /**
     * Get a verification by column and value.
     */
    public function get(string $key, string $column = 'verification_id'): Model
    {
        $verification = Verification::find([$column => $key]);
        if (!$verification) {
            throw new PlatformException(__('enjin-platform::error.verification.verification_not_found'), 404);
        }

        return $verification;
    }

    /**
     * Create a new verification.
     */
    public function store(array $data): Model
    {
        return Verification::create($data);
    }

    /**
     * Update a verification.
     */
    public function update(Model $verification, array $data): bool
    {
        return $verification
            ->fill($data)
            ->save();
    }

    /**
     * Verify a verification.
     */
    public function verify(string $verificationId, string $signature, string $address, string $cryptoSignatureType): bool
    {
        $verification = Verification::where(['verification_id' => $verificationId])->firstOrFail();
        $publicKey = SS58Address::getPublicKey($address);
        $message = HexConverter::prefix(Blake2::hash(HexConverter::stringToHex('Enjin Signed Message:' . $verification->code)));
        $isValid = $this->blockchainService->verifyMessage($message, $signature, $publicKey, $cryptoSignatureType);

        if (!$isValid) {
            throw new PlatformException(__('enjin-platform::error.verification.invalid_signature'));
        }

        $wallet = Wallet::query()->firstWhere(['public_key' => $publicKey]);
        if (empty($wallet)) {
            $wallet = $this->walletService->firstOrStore(['verification_id' => $verificationId]);
        }

        $this->update($verification, ['public_key' => $publicKey]);
        $this->walletService->update($wallet, [
            'public_key' => $publicKey,
            'verification_id' => $verificationId,
        ]);

        return true;
    }

    /**
     * Generate a readable string using all upper case letters that are easy to recognize.
     */
    public function generate(): array
    {
        $verificationId = $this->generateVerificationId();

        while (Verification::firstWhere(['verification_id' => $verificationId])) {
            // TODO: Should report this as in theory this should not happen.
            $verificationId = $this->generateVerificationId();
        }

        return [
            'verification_id' => $verificationId,
            'code' => $this->generateCode(),
        ];
    }

    /**
     * Generate a QR code for a verification.
     */
    public function qr(string $verificationId, string $code, string $callback, int $size = 512): string
    {
        $encodedCallback = base64_encode($callback);
        $deepLink = config('enjin-platform.deep_links.proof') . "{$verificationId}:{$code}:{$encodedCallback}";

        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$deepLink}";
    }

    /**
     * Generate a random verification ID.
     */
    private function generateVerificationId(): string
    {
        try {
            $randomBytes = random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES);
            sodium_crypto_sign_seed_keypair($randomBytes);
            $keypair = sodium_crypto_sign_keypair();
            $key = sodium_crypto_sign_publickey($keypair);
            $hexed = sodium_bin2hex($key);

            return HexConverter::prefix($hexed);
        } catch (\Exception $e) {
            throw new PlatformException(__('enjin-platform::error.verification.unable_to_generate_verification_id'));
        }
    }

    /**
     * Generate a random code.
     */
    private function generateCode(): string
    {
        $code = $this->num2alpha($this->platform) . $this->network . $this->letters[random_int(0, strlen($this->letters) - 1)];

        for ($i = 0; $i < 3; $i++) {
            $code .= $this->alphanumerics[random_int(0, strlen($this->alphanumerics) - 1)];
        }

        return $code;
    }

    /**
     * Converts an integer into the alphabet base (A-Z).
     */
    private function num2alpha($n): string
    {
        $r = '';

        for ($i = 1; $n >= 0 && $i < 10; $i++) {
            $r = chr(0x41 + ($n % 26 ** $i / 26 ** ($i - 1))) . $r;
            $n -= 26 ** $i;
        }

        return $r;
    }
}
