<?php

namespace Enjin\Platform\Constants\Substrate;

use Codec\Base;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Illuminate\Support\Arr;

class Fee
{
    public static function forCall(string $call): string
    {
        $client = new Substrate(new SubstrateWebsocket());
        $codec = new ScaleInstance(Base::create());

        $extraByte = '84';
        $signer = '006802f945419791d3138b4086aa0b2700abb679f950e2721fd7d65b5d1fdf8f02';
        $signature = '01d19e04fc1a4ec115ec55d29e53676ddaeae0467134f9513b29ed3cd6fd6cd551a96c35b92b867dfd08ba37417e5733620acc4ad17c1d7c65909d6edaaffd4d0e';
        $era = '00';
        $nonce = '00';
        $tip = '00';

        $extrinsic = $extraByte . $signer . $signature . $era . $nonce . $tip . HexConverter::unPrefix($call);
        $extrinsic = $codec->createTypeByTypeString('Compact<u32>')->encode(count(HexConverter::hexToBytes($extrinsic))) . $extrinsic;
        ray($extrinsic);

        $result = $client->callMethod('payment_queryFeeDetails', [
            $extrinsic,
        ]);
        ray($result);

        $baseFee = gmp_init(Arr::get($result, 'inclusionFee.baseFee'));
        $lenFee = gmp_init(Arr::get($result, 'inclusionFee.lenFee'));
        $adjustedWeightFee = gmp_init(Arr::get($result, 'inclusionFee.adjustedWeightFee'));

        return gmp_strval(gmp_add($baseFee, gmp_add($lenFee, $adjustedWeightFee)));
    }
}
