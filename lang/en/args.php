<?php

return [
    'common.tokenId' => 'The token ID. Supporting multiple encoding formats to identify tokens accurately. [Learn more](https://docs.enjin.io/docs/tokenid-structure).',
    'idempotencyKey' => 'A unique key used to prevent duplicate operations. If multiple mutations are sent with the same key, only one will be broadcasted. Using a UUID is recommended.',
    'tokenId' => 'The token ID to create. This must be unique for this collection.',
    'simulate' => 'Simulates a transaction without broadcasting it to the network.',
];
