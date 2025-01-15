<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

class QrControllerTest extends TestCaseGraphQL
{
    #[RequiresOperatingSystem('Linux')]
    public function test_it_can_get_a_qr_image(): void
    {
        $response = $this->get('/qr?s=128&f=png&d=test_data');
        $content = base64_encode($response->content());

        error_log($content);

        $this->assertTrue($response->isOk());
        $this->assertSame(
            'iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAAAAADmVT4XAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QA/4ePzL8AAAUHSURBVHja7ZtPTFxFHMc/b2ErSCFxtbCGtKtBkT60YnsiNCRtGkmMh7Znjc3WA0ZLTIiJF7woiYc2CqbxYN008a7owQRJGiUcPAhNal0aYhPwT5FWSgMhVJa+8fDgvdl5+3bfW7ZdKPM7zfvNb37vu/O+85uZ38wagvJKBA1gpwOoBGB5Kph1IrZRylwFoM3YUPz5L0DkReD2TDBvzTUAQgghJgLCHRYbMm0rFh3FIACmEEIMB/Q2IYQQmgMaQOXmmr8KcPCTUgFY9bfbpQ7IVYDuFMAzAG93q02CecvugahfC++UpZpGPB+z0vDzltEkzEdCMadq6hWQcwKgptbV/OOUjIZsW+um6q3BKABg7klVM9mS/Xw0DTB4Bmh/GqAv5dSZv2XbTu1Xvc3GSzkM3wRY6tORcAeH4k//KgeAMQFQDfCry3+mawHjQQB4zEcf0yR8UBy4a8+GFWUDcMgNxfHjAEPlG4b9AEt1OhLu4FD83YJb/hGg4rVNA6ifVDVN/q2/TQEk3wNGTgGYCoAmj7f6QgAiLeE7ogUYyVkTbdEkDMeB0qbMRGgAu8rQuZoDGsAWGYaJgJm1VoABC6Axj1VAbwkXQOzlEJiPFTJobNQc2K5zwSszAK+/76y/DwHwTTOI5wEYOAZ03AF45y2n3c+tCk0vtANtUipqvAoWDgNwqcEfwHIaYM1VpO00i1K8k9tMbXHjljI1pXPOUJoDW4SEmRuSagZgn6EoPJLvWEStk1J//80Akb3OsyEALh9UXczH4G61+7ynD+BoK/D5GkCPW5dscxRmt1KXWzp/ksiZ+8hmXgixIj3vEdkyLdUlQx/ZdLqONAm3z97wdBkAVC0CfH0K4FYqxIJh0Sn+bSerXcVAX5geqC3ys3raRas0CcNzYKk4f0sAu42wAKqTAL+PAiQBeFwKdG4xpWpVaqbrAMY6fN8XTwLIeTUpvHYC8KEQQvRKFpOOwZikHRRCiKQaim0Zc1qsJ0hWhK9oEm6HUDySO//0WYi3fAHQeHLj0ToPwPG9wQBIy4vh55ziU4He/Ow0wLs9AL0OgHu2y8MBAXj2k4UWZJJUJDQJS7YecMUqrLGc32bl1PoAqDEdTdT0768X0gCmuxVLqQHZ3gVaBlyz1wMmwLlzsH54bUq9L0KLqYZiv57KDsXzdnFWh+KtOQpu/xLMulVKP/1Q3AuvXAGiRxQAM13BWg83Ahcsl+ljblFeD9jbzpwuugA6j2wmDrRLofhAbc7FW4cm4cM9F8iS50gvsRIewGpl2J5SUnGYVx3+ryh1MQugP//e0HdDEfg0ywhbpUno+eqW545zk3J3ccrK4+9aTu00wKP7AgG46bn/pt6mOyHlhi86pcUegLTa+iLA0H6A3rP3YRierC20TXiD/JdMNAm3eygGWM53bWPI/f7XT0hEBviotTgA41K5CrAkjpnjyviTRoVkNgrAB0X2QFXRlZqEpSShWPCvW7Mzs7H7CuAPT4KiF9avvF/qcvaGHgNZdpdoGK7LmYK5iLOahA/LKPh4zX9s9G8eQMOsqlHvv30lLUgk4wZAqGveZsngkWAAjHgY/DUFDlUicU3CcBzIhG6fASIVgHXPE5FzNoi674l6AAS7TZd4wi3XASS/BM57Tqxze5uNQ8aumnipuGH4vVs8PaojoQawabGvcIT/1+36VnQFoLqFjX/dFpYDFcBlO07XOAA0B3YygP8BGOTjwk3mr4kAAAAASUVORK5CYII=',
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
