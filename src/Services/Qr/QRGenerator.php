<?php

declare(strict_types=1);

namespace Enjin\Platform\Services\Qr;

use BaconQrCode\Renderer\Eye\CompositeEye;
use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Eye\ModuleEye;
use BaconQrCode\Renderer\Eye\SimpleCircleEye;
use BaconQrCode\Renderer\Eye\SquareEye;
use Enjin\Platform\Services\Qr\CustomEyes\RoundedSquareEye;
use InvalidArgumentException;
use Override;
use SimpleSoftwareIO\QrCode\Generator as SimpleQRGenerator;

class QRGenerator extends SimpleQRGenerator
{
    /**
     * The style to apply to the internal eye.
     * Possible values are circle, square and rounded.
     *
     * @var string|null
     */
    protected $eyeStyle;

    /**
     * The style to apply to the external eye.
     * Possible values are circle, square, pointy and rounded.
     */
    protected ?string $externalEyeStyle = null;

    /**
     * Sets the eye style.
     *
     * @throws InvalidArgumentException
     *
     * @return BeamQRGenerator
     */
    #[Override]
    public function eye(string $style): self
    {
        if (!in_array($style, ['square', 'circle', 'rounded'])) {
            throw new InvalidArgumentException("\$style must be square, rounded or circle. {$style} is not a valid eye style.");
        }

        $this->eyeStyle = $style;

        return $this;
    }

    /**
     * Sets the external eye style.
     *
     * @throws InvalidArgumentException
     *
     * @return BeamQRGenerator
     */
    public function externalEye(string $style): self
    {
        if (!in_array($style, ['square', 'circle', 'rounded'])) {
            throw new InvalidArgumentException("\$style must be square, rounded or circle. {$style} is not a valid eye style.");
        }

        $this->externalEyeStyle = $style;

        return $this;
    }

    /**
     * Fetches the eye style.
     */
    #[Override]
    public function getEye(): EyeInterface
    {
        // defaults
        $internalEye = new ModuleEye($this->getModule());
        $externalEye = new ModuleEye($this->getModule());

        // external eye
        if ($this->externalEyeStyle === 'square') {
            $externalEye = SquareEye::instance();
        }

        if ($this->externalEyeStyle === 'circle') {
            $externalEye = SimpleCircleEye::instance();
        }

        if ($this->externalEyeStyle === 'rounded') {
            $externalEye = RoundedSquareEye::instance();
        }

        // internal eye
        if ($this->eyeStyle === 'square') {
            $internalEye = SquareEye::instance();
        }

        if ($this->eyeStyle === 'circle') {
            $internalEye = SimpleCircleEye::instance();
        }

        if ($this->eyeStyle === 'rounded') {
            $internalEye = RoundedSquareEye::instance();
        }

        return new CompositeEye($externalEye, $internalEye);
    }
}
