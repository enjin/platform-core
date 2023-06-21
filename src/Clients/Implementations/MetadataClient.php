<?php

namespace Enjin\Platform\Clients\Implementations;

use Enjin\Platform\Clients\Abstracts\CachedHttpAbstract;

class MetadataClient extends CachedHttpAbstract
{
    /**
     * Get the data from the given url.
     */
    public function fetch(string $url): mixed
    {
        try {
            $result = $this->getClient()->get($url);

            return $this->getResponse($result);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
