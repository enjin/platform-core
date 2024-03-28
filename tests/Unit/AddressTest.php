<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\TestCase;

class AddressTest extends TestCase
{
    public function test_it_can_decode_generic_address()
    {
        $address = SS58Address::getPublicKey('14E5nqKAp3oAJcmzgZhUD2RcptBeUBScxKHgJKU4HPNcKVf3');

        $this->assertEquals('0x8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48', $address);
    }

    public function test_it_can_get_daemon_account()
    {
        $address = HexConverter::unPrefix(Account::daemonPublicKey());

        $this->assertEquals('6a03b1a3d40d7e344dfb27157931b14b59fe2ff11d7352353321fe400e956802', $address);
    }

    public function test_it_can_decode_efinity_address()
    {
        $address = SS58Address::getPublicKey('efTwqopZgd4Yqefg2NzVPW4THfFmsSsSbxTLN2uq7kmadDaC5');

        $this->assertEquals('0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d', $address);
    }

    public function test_it_can_decode_rocfinity_address()
    {
        $address = SS58Address::getPublicKey('rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ');

        $this->assertEquals('0xd43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d', $address);
    }

    public function test_it_can_encode_address_from_public_key()
    {
        $address = SS58Address::encode('8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48', 0);

        $this->assertEquals('14E5nqKAp3oAJcmzgZhUD2RcptBeUBScxKHgJKU4HPNcKVf3', $address);
    }

    public function test_it_can_encode_efinity_address_from_public_key()
    {
        $address = SS58Address::encode('d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d', 1110);

        $this->assertEquals('efTwqopZgd4Yqefg2NzVPW4THfFmsSsSbxTLN2uq7kmadDaC5', $address);
    }

    public function test_it_can_encode_rocfinity_address_from_public_key()
    {
        $address = SS58Address::encode('d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d', 195);

        $this->assertEquals('rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ', $address);
    }

    /**
     * @test
     *
     * @define-env usesNullDaemonAccount
     */
    public function test_it_can_get_null_daemon_account()
    {
        $address = HexConverter::unPrefix(Account::daemonPublicKey());

        $this->assertEquals('0000000000000000000000000000000000000000000000000000000000000000', $address);
    }

    /**
     * @test
     *
     * @define-env usesEnjinNetwork
     */
    public function test_it_will_encode_matrix_address_if_enjin_is_the_selected_chain()
    {
        $address = SS58Address::encode('d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

        $this->assertEquals('efTwqopZgd4Yqefg2NzVPW4THfFmsSsSbxTLN2uq7kmadDaC5', $address);
    }

    /**
     * @test
     *
     * @define-env usesLocalNetwork
     */
    public function test_it_will_encode_rocfinity_address_if_developer_is_the_selected_chain()
    {
        $address = SS58Address::encode('d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d');

        $this->assertEquals('rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ', $address);
    }

    public function test_it_fails_to_decode_invalid_address()
    {
        $this->expectExceptionMessage(__(
            'enjin-platform::ss58_address.error.cannot_decode_address',
            [
                'address' => '14E5nqKAp3oAJcmzgZhUD2RcptBeUBScxKHgJKU4HP123invalid',
                'message' => 'Data contains invalid characters "l"',
            ]
        ));

        SS58Address::decode('14E5nqKAp3oAJcmzgZhUD2RcptBeUBScxKHgJKU4HP123invalid');
    }

    public function test_it_fails_to_decode_eth_address()
    {
        $this->expectExceptionMessage(__('enjin-platform::ss58_address.error.invalid_empty_address'));

        SS58Address::decode('');
    }
}
