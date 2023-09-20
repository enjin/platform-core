<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class QrControllerTest extends TestCaseGraphQL
{
    public function test_it_can_get_a_qr_image(): void
    {
        $response = $this->get('/qr?s=128&f=svg&d=test_data');

        $this->assertTrue($response->isOk());
        $this->assertEquals(
            "<img src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgd2lkdGg9IjEyOCIgaGVpZ2h0PSIxMjgiIHZpZXdCb3g9IjAgMCAxMjggMTI4Ij48cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgZmlsbD0iI2ZmZmZmZiIvPjxnIHRyYW5zZm9ybT0ic2NhbGUoNi4wOTUpIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLDApIj48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik04IDBMOCAxTDkgMUw5IDBaTTEwIDBMMTAgMkw4IDJMOCA1TDkgNUw5IDZMOCA2TDggN0w5IDdMOSA2TDEwIDZMMTAgN0wxMSA3TDExIDhMOCA4TDggOUw3IDlMNyA4TDYgOEw2IDlMNSA5TDUgMTBMNCAxMEw0IDEyTDMgMTJMMyAxMUwxIDExTDEgMTBMMCAxMEwwIDExTDEgMTFMMSAxMkwwIDEyTDAgMTNMMSAxM0wxIDEyTDMgMTJMMyAxM0w1IDEzTDUgMTBMNiAxMEw2IDExTDcgMTFMNyAxMEw4IDEwTDggMTFMMTEgMTFMMTEgMTJMMTIgMTJMMTIgMTNMMTEgMTNMMTEgMTRMMTAgMTRMMTAgMTZMOSAxNkw5IDE3TDggMTdMOCAyMUwxMSAyMUwxMSAyMEwxMCAyMEwxMCAxOUw5IDE5TDkgMThMMTAgMThMMTAgMTZMMTEgMTZMMTEgMTVMMTMgMTVMMTMgMTRMMTIgMTRMMTIgMTNMMTMgMTNMMTMgMTJMMTIgMTJMMTIgMTBMMTQgMTBMMTQgMTFMMTUgMTFMMTUgMTJMMTQgMTJMMTQgMTRMMTUgMTRMMTUgMTZMMTQgMTZMMTQgMTlMMTMgMTlMMTMgMThMMTIgMThMMTIgMTdMMTMgMTdMMTMgMTZMMTIgMTZMMTIgMTdMMTEgMTdMMTEgMThMMTIgMThMMTIgMTlMMTMgMTlMMTMgMjFMMTQgMjFMMTQgMTlMMTUgMTlMMTUgMjFMMTYgMjFMMTYgMjBMMTcgMjBMMTcgMjFMMTggMjFMMTggMjBMMTcgMjBMMTcgMTlMMTUgMTlMMTUgMTdMMTYgMTdMMTYgMTVMMTcgMTVMMTcgMTZMMTggMTZMMTggMTdMMTcgMTdMMTcgMThMMTggMThMMTggMTlMMTkgMTlMMTkgMThMMjAgMThMMjAgMTdMMTkgMTdMMTkgMTZMMjAgMTZMMjAgMTVMMTkgMTVMMTkgMTRMMjAgMTRMMjAgMTFMMjEgMTFMMjEgOEwyMCA4TDIwIDEwTDE5IDEwTDE5IDhMMTYgOEwxNiAxMEwxNCAxMEwxNCA4TDEzIDhMMTMgNUwxMiA1TDEyIDRMMTMgNEwxMyAzTDEyIDNMMTIgMUwxMyAxTDEzIDBaTTEwIDJMMTAgNEw5IDRMOSA1TDExIDVMMTEgNEwxMiA0TDEyIDNMMTEgM0wxMSAyWk0xMSA2TDExIDdMMTIgN0wxMiA2Wk0wIDhMMCA5TDIgOUwyIDEwTDMgMTBMMyA5TDQgOUw0IDhaTTYgOUw2IDEwTDcgMTBMNyA5Wk05IDlMOSAxMEwxMCAxMEwxMCA5Wk0xNiAxMEwxNiAxMUwxOCAxMUwxOCAxNEwxOSAxNEwxOSAxMFpNNiAxMkw2IDEzTDcgMTNMNyAxMlpNOCAxMkw4IDE0TDkgMTRMOSAxM0wxMCAxM0wxMCAxMlpNMTYgMTJMMTYgMTNMMTUgMTNMMTUgMTRMMTYgMTRMMTYgMTNMMTcgMTNMMTcgMTJaTTE4IDE3TDE4IDE4TDE5IDE4TDE5IDE3Wk0yMCAxOUwyMCAyMEwyMSAyMEwyMSAxOVpNMCAwTDAgN0w3IDdMNyAwWk0xIDFMMSA2TDYgNkw2IDFaTTIgMkwyIDVMNSA1TDUgMlpNMTQgMEwxNCA3TDIxIDdMMjEgMFpNMTUgMUwxNSA2TDIwIDZMMjAgMVpNMTYgMkwxNiA1TDE5IDVMMTkgMlpNMCAxNEwwIDIxTDcgMjFMNyAxNFpNMSAxNUwxIDIwTDYgMjBMNiAxNVpNMiAxNkwyIDE5TDUgMTlMNSAxNloiIGZpbGw9IiMwMDAwMDAiLz48L2c+PC9nPjwvc3ZnPgo=' alt='QR Code'>",
            $response->content()
        );
    }

    public function test_it_fails_to_get_a_qr_with_no_data(): void
    {
        $response = $this->get('/qr?s=128&f=svg');

        $this->assertTrue($response->isServerError());
        $this->assertEquals(__('enjin-platform::error.qr.data_must_not_be_empty'), $response->exception->getMessage());
    }

    public function test_it_fails_to_get_a_qr_with_no_invalid_filetype(): void
    {
        $response = $this->get('/qr?s=128&f=jpg&d=test_data');

        $this->assertTrue($response->isServerError());
        $this->assertEquals(__('enjin-platform::error.qr.image_format_not_supported'), $response->exception->getMessage());
    }
}
