<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

class QrControllerTest extends TestCaseGraphQL
{
    #[RequiresOperatingSystem('Linux')]
    public function test_it_can_get_a_qr_image(): void
    {
        $response = $this->get('/qr?s=32&f=png&d=test_data');
        $content = base64_encode($response->content());

        error_log($content);

        $this->assertTrue($response->isOk());
        $this->assertSame(
            'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAAAAABWESUoAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QA/4ePzL8AAAIWSURBVDjLfZM/aFNRFMZ/iY8KabtEnrQgJeLktSJWLQrh6SZOPoxLVDq8zSVLNyEZ+mYdYl0cQuqfQSQ4K9SKhFKCfVSUjOUqyiOEPuEhNlGT63CTvKRaz3TPx8c953zfOTHF/8Ng8+MgWeAR18efdC69GUCzqHLE/tkBvyWoRlDZABz9LkHMYdq9eD4xhMUBJoIgCJbg/bXdo2z6QQeWgiAIJgBUGccGkFBFbHtAFSSA7egSg0hVDjzNQyU1hMWHCTJzJ2aCfXg/AnA6Dw8+7SUsSynlJABezhW5BkxKKeUyYACl0t8CHtqvxD+kXrhRY54aZ99xvBpfBWAjEUJKHjyFgeqGsNvt6+ACYC0CFeyOUj0vKoAQ6wIQAjEoEKqhHq5kV7NAFh6O9KDDf567G6E+7RQ+0wDqtQsu4LoWCBdXgKUkdH+5bkuhVAgeIIQAISKC2FK3xePIrDo0ti73klfpl7fqjbfteiem1O8aJz9oPC2y+Z5vKSoZu/DjyMjKbVhgAViW1KOPjAlza3AOgJv3B6Aq43yRUsodaEmNVeUiIHcgVHtWDopFrWPRxNMENUrwTRMA08QEc8VgJephvvuC9SZUG5xJQROaamRpaWcAvGNpJFq+KQNgxtau1NJgQ3MODBvGTvTN+p4ECvqT5AXvGVBIwr2pxPbV0dsMQ21WL2yHckwNX3eMtc8zXzv9fHzs2+wf9EbWAZDvJPoAAAAASUVORK5CYII=',
            $content
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
