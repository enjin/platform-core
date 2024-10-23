<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec;

use Codec\Base;
use Codec\Types\ScaleInstance;
use Codec\Utils;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Support\JSON;
use Illuminate\Support\Facades\Cache;

class Codec
{
    protected ScaleInstance $scaleInstance;
    protected Decoder $decoder;
    protected Encoder $encoder;

    public function __construct()
    {
        $generator = Base::create();
        Base::regCustom($generator, $this->loadCustomTypes());
        $this->scaleInstance = new ScaleInstance($generator);
        // $this->decoder = new Decoder($this->scaleInstance);
        // $this->encoder = new Encoder($this->scaleInstance);
    }

    public function decoder(): Decoder
    {
        return $this->decoder;
    }

    public function encoder(): Encoder
    {
        return $this->encoder;
    }

    private function loadCustomTypes()
    {
        return Cache::remember(PlatformCache::CUSTOM_TYPES->key(), now()->addWeek(), function () {
            $moduleFiles = array_filter(Utils::getDirContents(__DIR__ . '/Types/'), function ($var) {
                $slice = explode('.', $var);

                return $slice[count($slice) - 1] === 'json';
            });
            $moduleTypes = [];
            foreach ($moduleFiles as $file) {
                $content = JSON::decode(file_get_contents($file), true);
                // merge all array to one $moduleTypes array
                $moduleTypes = array_merge($moduleTypes, $content);
            }

            // reg custom type
            return $moduleTypes;
        });
    }
}
