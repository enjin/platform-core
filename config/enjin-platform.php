<?php

use Enjin\Platform\Enums\Global\ChainType;
use Enjin\Platform\Enums\Global\NetworkType;
use Enjin\Platform\Services\Qr\Adapters\PlatformQrAdapter;

// config for Platform/Core

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | This defines what authentication method to use to protect the APIs.
    | If set to empty|null, the endpoints will not be protected.
    | It's strongly recommended to set one.
    |
    */
    'auth' => env('AUTH_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Methods
    |--------------------------------------------------------------------------
    |
    | These are the supported authentication drivers
    |
    */
    'auth_drivers' => [
        'basic_token' => [
            'driver' => 'basic_token',
            'token' => env('BASIC_AUTH_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uses decoder container
    |--------------------------------------------------------------------------
    |
    | If you wish to use a decoder container set the host here.
    |
    */
    'decoder_container' => env('DECODER_CONTAINER', '127.0.0.1:8090'),

    /*
    |--------------------------------------------------------------------------
    | Deep links
    |--------------------------------------------------------------------------
    |
    | Here you can change the deep links used throughout the platform.
    |
    */
    'deep_links' => [
        'proof' => rtrim((string) env('PROOF_DEEPLINK', rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/proof'), '/') . '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Token ID Encoder
    |--------------------------------------------------------------------------
    |
    | This defines the default encoder to use to encode your token ID
    |
    */
    'token_id_encoder' => env('TOKEN_ID_ENCODER', 'hash'),

    /*
    |--------------------------------------------------------------------------
    | Token ID Encoders
    |--------------------------------------------------------------------------
    |
    | These are the different encoders supported base from the best practices
    | https://platform.docs.enjin.io/getting-started-with-the-platform-api/tokenid-structure-best-practices
    |
    */
    'token_id_encoders' => [
        'hash' => [
            'driver' => 'hash',
            'algo' => 'blake2',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The blockchain networks
    |--------------------------------------------------------------------------
    |
    | These are the list of networks that platform is currently supporting.
    | You may configure the network setting for each network.
    |
    */
    'chains' => [
        'supported' => [
            'substrate' => [
                NetworkType::ENJIN_MATRIX->value => $enjin = [
                    'chain-id' => 0,
                    'network-id' => 2000,
                    'testnet' => false,
                    'platform-id' => env('SUBSTRATE_ENJIN_PLATFORM_ID', 0),
                    'node' => env('SUBSTRATE_ENJIN_RPC', 'wss://rpc.matrix.blockchain.enjin.io'),
                    'ss58-prefix' => env('SUBSTRATE_ENJIN_SS58_PREFIX', 1110),
                    'genesis-hash' => env('SUBSTRATE_ENJIN_GENESIS_HASH', '0x3af4ff48ec76d2efc8476730f423ac07e25ad48f5f4c9dc39c778b164d808615'),
                    'spec-version' => env('SUBSTRATE_ENJIN_SPEC_VERSION', 1014),
                    'transaction-version' => env('SUBSTRATE_ENJIN_TRANSACTION_VERSION', 10),
                ],
                'enjin' => $enjin,
                NetworkType::CANARY_MATRIX->value => $canary = [
                    'chain-id' => 0,
                    'network-id' => 2010,
                    'testnet' => true,
                    'platform-id' => env('SUBSTRATE_CANARY_PLATFORM_ID', 0),
                    'node' => env('SUBSTRATE_CANARY_RPC', 'wss://rpc.matrix.canary.enjin.io'),
                    'ss58-prefix' => env('SUBSTRATE_CANARY_SS58_PREFIX', 9030),
                    'genesis-hash' => env('SUBSTRATE_CANARY_GENESIS_HASH', '0xa37725fd8943d2a524cb7ecc65da438f9fa644db78ba24dcd0003e2f95645e8f'),
                    'spec-version' => env('SUBSTRATE_CANARY_SPEC_VERSION', 1020),
                    'transaction-version' => env('SUBSTRATE_CANARY_TRANSACTION_VERSION', 11),
                ],
                'canary' => $canary,
                NetworkType::LOCAL_MATRIX->value => $local = [
                    'chain-id' => 0,
                    'network-id' => 104,
                    'testnet' => true,
                    'platform-id' => env('SUBSTRATE_LOCAL_PLATFORM_ID', 0),
                    'node' => env('SUBSTRATE_LOCAL_RPC', 'ws://localhost:10010'),
                    'ss58-prefix' => env('SUBSTRATE_LOCAL_SS58_PREFIX', 195),
                    'genesis-hash' => env('SUBSTRATE_LOCAL_GENESIS_HASH', '0xa37725fd8943d2a524cb7ecc65da438f9fa644db78ba24dcd0003e2f95645e8f'),
                    'spec-version' => env('SUBSTRATE_LOCAL_SPEC_VERSION', 1003),
                    'transaction-version' => env('SUBSTRATE_LOCAL_TRANSACTION_VERSION', 5),
                ],
                'local' => $local,
                NetworkType::ENJIN_RELAY->value => [
                    'chain-id' => 1,
                    'network-id' => 2000,
                    'testnet' => false,
                    'platform-id' => env('SUBSTRATE_ENJIN_RELAY_PLATFORM_ID', 0),
                    'node' => env('SUBSTRATE_ENJIN_RELAY_RPC', 'wss://rpc.relay.blockchain.enjin.io'),
                    'ss58-prefix' => env('SUBSTRATE_ENJIN_RELAY_SS58_PREFIX', 2135),
                    'genesis-hash' => env('SUBSTRATE_ENJIN_RELAY_GENESIS_HASH', '0xd8761d3c88f26dc12875c00d3165f7d67243d56fc85b4cf19937601a7916e5a9'),
                    'spec-version' => env('SUBSTRATE_ENJIN_RELAY_SPEC_VERSION', 1032),
                    'transaction-version' => env('SUBSTRATE_ENJIN_RELAY_TRANSACTION_VERSION', 10),
                ],
                NetworkType::CANARY_RELAY->value => [
                    'chain-id' => 1,
                    'network-id' => 2010,
                    'testnet' => true,
                    'platform-id' => env('SUBSTRATE_CANARY_RELAY_PLATFORM_ID', 0),
                    'node' => env('SUBSTRATE_CANARY_RELAY_RPC', 'wss://rpc.relay.canary.enjin.io'),
                    'ss58-prefix' => env('SUBSTRATE_CANARY_RELAY_SS58_PREFIX', 69),
                    'genesis-hash' => env('SUBSTRATE_CANARY_RELAY_GENESIS_HASH', '0x735d8773c63e74ff8490fee5751ac07e15bfe2b3b5263be4d683c48dbdfbcd15'),
                    'spec-version' => env('SUBSTRATE_CANARY_RELAY_SPEC_VERSION', 1032),
                    'transaction-version' => env('SUBSTRATE_CANARY_RELAY_TRANSACTION_VERSION', 14),
                ],
                NetworkType::LOCAL_RELAY->value => [
                    'chain-id' => 1,
                    'network-id' => 104,
                    'testnet' => true,
                    'platform-id' => env('SUBSTRATE_LOCAL_RELAY_PLATFORM_ID', 0),
                    'node' => env('SUBSTRATE_LOCAL_RELAY_RPC', 'ws://localhost:10010'),
                    'ss58-prefix' => env('SUBSTRATE_LOCAL_RELAY_SS58_PREFIX', 195),
                    'genesis-hash' => env('SUBSTRATE_LOCAL_RELAY_GENESIS_HASH', '0xa37725fd8943d2a524cb7ecc65da438f9fa644db78ba24dcd0003e2f95645e8f'),
                    'spec-version' => env('SUBSTRATE_LOCAL_RELAY_SPEC_VERSION', 1003),
                    'transaction-version' => env('SUBSTRATE_LOCAL_RELAY_TRANSACTION_VERSION', 5),
                ],
            ],
        ],

        'selected' => env('CHAIN', ChainType::SUBSTRATE->value),

        'network' => env('NETWORK', NetworkType::ENJIN_MATRIX->value),

        'daemon-account' => env('DAEMON_ACCOUNT') ?: '0x0000000000000000000000000000000000000000000000000000000000000000',
    ],

    /*
    |--------------------------------------------------------------------------
    | The pagination limit
    |--------------------------------------------------------------------------
    |
    | Here you may set the default pagination limit for the APIs
    |
    */
    'pagination' => [
        'limit' => env('DEFAULT_PAGINATION_LIMIT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | The sync settings
    |--------------------------------------------------------------------------
    |
    | Here you can set whether to sync everything from the blockchain.
    |
    */
    'sync' => [
        'all' => env('SYNC_ALL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | The flag to cache event
    |--------------------------------------------------------------------------
    |
    | When true, events are cached
    |
    */
    'cache_events' => env('PLATFORM_CACHE_EVENTS', true),

    /*
    |--------------------------------------------------------------------------
    | The websocket channel name
    |--------------------------------------------------------------------------
    |
    | Here you may configure the name of the websocket channel
    |
    */
    'platform_channel' => env('PLATFORM_CHANNEL', 'platform'),


    /*
    |--------------------------------------------------------------------------
    | The QR Image URL Adapter
    |--------------------------------------------------------------------------
    |
    | Set the adapter for generating the QR URL.
    |
    */
    'qr' => [
        'adapter' => PlatformQrAdapter::class,
        'size' => env('QR_CODE_SIZE', 512),
        'format' => env('QR_CODE_FORMAT', 'png'),
        'image' => env('QR_CODE_IMAGE_URL'),
        'image_size' => env('QR_CODE_IMAGE_SIZE', .20),
    ],

    /*
    |--------------------------------------------------------------------------
    | The ingest sync wait timeout
    |--------------------------------------------------------------------------
    |
    | Here you may set how long the ingest command to wait for the sync to finish
    |
    */
    'sync_max_wait_timeout' => env('SYNC_MAX_WAIT_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Prune expired pending events
    |--------------------------------------------------------------------------
    |
    | Here you may set the number of days to prune expired pending events.
    | When set to null or zero, expired events will not be pruned.
    |
    */
    'prune_expired_events' => env('PRUNE_EXPIRED_EVENTS', 30),

    /*
    |--------------------------------------------------------------------------
    | The GitHub API access info, if required
    |--------------------------------------------------------------------------
    |
    | Here you may set a GitHub auth token to help increase rate limits when
    | accessing the GitHub APIs.
    |
    */
    'github' => [
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com/'),
        'token' => env('GITHUB_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prune blocks
    |--------------------------------------------------------------------------
    |
    | Here you can specify the number of days to retain blocks data before pruning.
    | If set to null or zero, blocks will not be pruned.
    |
    */
    'prune_blocks' => env('PRUNE_BLOCKS', 7),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Here you may set the rate limiting for the APIs
    |
    */
    'rate_limit' => [
        'enabled' => env('RATE_LIMIT_ENABLED', false),
        'attempts' => env('RATE_LIMIT_ATTEMPTS', 500),
        'time' => env('RATE_LIMIT_TIME', 1), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute metadata syncing
    |--------------------------------------------------------------------------
    |
    | Here you may configure how the attribute metadata is synced
    |
    */
    'sync_metadata' => [
        'data_chunk_size' => env('SYNC_METADATA_CHUNK_SIZE', 10000),
        'refresh_max_attempts' => env('REFRESH_METADATA_MAX_ATTEMPTS', 10),
        'refresh_decay_seconds' => env('REFRESH_METADATA_DECAY_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Here you may set the dedicated queue for this package
    |
    */
    'queue' => env('PLATFORM_CORE_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Snapshot email
    |--------------------------------------------------------------------------
    |
    | Here you may set the email to send the token holder snapshot to
    |
    */
    'token_holder_snapshot_email' => env('TOKEN_HOLDER_SNAPSHOT_EMAIL'),
];
