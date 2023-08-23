<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Enjin\Platform\Tests\TestCase;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;

class DepositFeeTest extends TestCase
{
    use MocksWebsocketClient;

    public function test_it_can_get_extrinsic_fee()
    {
        $extrinsic = '0x490284003a158a287b46acd830ee9a83d304a63569f8669968a20ea80720e338a565dd0901a2369c177101204aede6d1992240c436a05084f1b56321a0851ed346cb2783704fd10cbfd10a423f1f7ab321761de08c3927c082236ed433a979b19ee09ed7890064000a07003a158a287b46acd830ee9a83d304a63569f8669968a20ea80720e338a565dd0913000064a7b3b6e00d';
        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $fee = Substrate::getFee($extrinsic);
        $totalFee = gmp_add(gmp_add(gmp_init($feeDetails['baseFee']), gmp_init($feeDetails['lenFee'])), gmp_init($feeDetails['adjustedWeightFee']));

        $this->assertEquals(gmp_strval($totalFee), $fee);
    }
}
