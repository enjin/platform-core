<?php

namespace Enjin\Platform\Channels;

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Str;

class PlatformAppChannel extends Channel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        parent::__construct(Str::lower(Str::slug(Str::kebab(config('enjin-platform.platform_channel')))));
    }
}
