<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchTransferBalanceMutation;
use Enjin\Platform\Services\Processor\Substrate\Codec\Encoder as BaseEncoder;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Package;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PermittedExtrinsicsParams extends FuelTankRules
{
    protected ?array $extrinsics;

    /**
     * Creates a new instance.
     */
    public function __construct(?array $extrinsics = [])
    {
        $this->extrinsics = array_map(
            function ($extrinsic) {
                if (is_string($extrinsic)) {
                    return $extrinsic;
                }

                if (($palletName = Arr::get($extrinsic, 'palletName')) && ($methodName = Arr::get($extrinsic, 'extrinsicName'))) {
                    return HexConverter::hexToString($palletName) . '.' . HexConverter::hexToString($methodName);
                }

                $palletName = array_key_first($extrinsic);
                $methodName = array_key_first($extrinsic[$palletName]);

                return $palletName . '.' . $methodName;
            },
            $extrinsics
        );
    }

    public function fromMethods(array $methods): self
    {
        $mutations = collect(Package::getClassesThatImplementInterface(PlatformBlockchainTransaction::class))
            ->map(fn ($class) => resolve($class)->getMethodName())
            ->all();

        return new self(
            collect($methods)
                ->filter(fn ($method) => in_array($method, $mutations))
                ->map(fn ($method) => [
                    explode('.', (string) Arr::get(BaseEncoder::getCallIndexKeys(), $method))[0] => [
                        explode('.', (string) Arr::get(BaseEncoder::getCallIndexKeys(), $method))[1] => null,
                    ],
                ])->all()
        );
    }

    public function toMethods(): array
    {
        return array_map(
            fn ($extrinsic) => collect(BaseEncoder::getCallIndexKeys())->filter(fn ($item) => $item == $extrinsic)->keys()->first(),
            $this->extrinsics
        );
    }

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            extrinsics: Arr::get($params, 'PermittedExtrinsics.extrinsics') ?? Arr::get($params, 'PermittedExtrinsics')
        );
    }

    public function toArray(): array
    {
        return [
            'PermittedExtrinsics' => $this->extrinsics,
        ];
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        $methods = array_unique($this->toMethods());
        $encodedData = '07'; // TODO: This should come from the metadata and not hardcode it.
        $encodedData .= HexConverter::intToHex(count($methods) * 4);
        $encodedData .= collect($methods)->reduce(fn ($data, $mutation) => Str::of($data)->append($this->getEncodedData($mutation))->toString(), '');

        return [
            'PermittedExtrinsics' =>  ['extrinsics' => $encodedData],
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }

    protected function getEncodedData(string $mutationName): string
    {
        $transactionMutation = Package::getClassesThatImplementInterface(PlatformBlockchainTransaction::class)
            ->filter(fn ($class) => $class !== BatchTransferBalanceMutation::class)
            ->filter(fn ($class) => Str::contains(class_basename($class), $mutationName))->first();

        return HexConverter::unPrefix(TransactionSerializer::encode(new $transactionMutation()->getMethodName(), $transactionMutation::getEncodableParams()));
    }
}
