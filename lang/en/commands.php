<?php

return [
    'sync.description' => 'Initialize Platform and sync it with a snapshot of Substrate state.',
    'sync.truncating' => 'Truncating database for a fresh sync',
    'sync.decoding' => 'Decoding, parsing and saving chain data to the database',
    'sync.fetching' => 'Wait while we are fetching the storage from the RPC node',
    'sync.syncing' => '** This platform is connected to :network',
    'sync.current_block' => '** Current block head is: #:blockNumber',
    'sync.overview' => '******************** Sync Overview ********************',
    'sync.header' => '******************** Enjin Platform :version ********************',
    'sync.total_time' => 'Synced chain state in: :sec seconds',
    'ingest.description' => 'Ingests the blockchain information into Platform database for processing.',
    'transactions.description' => 'Updates Platform transactions with the blockchain data.',
    'transactions.header' => '******************** Enjin Platform Syncer ********************',
    'transactions.specify_start' => 'Please specify the block number to start from with: --from=<block>',
    'transactions.syncing' => 'Syncing transactions from block #:fromBlock to #:toBlock',
    'transactions.start_lower_than_end' => 'The block number to start from must be lower or equal than the block number to end at.',
    'transactions.overview' => '******************** Sync Overview ********************',
    'transactions.total_time' => 'Synced extrinsics in: :sec seconds',
    'transactions.fetching' => 'Starting to fetch storage values from the RPC node',
    'transactions.decoding' => 'Decoding, parsing and saving updates to the database',
    'transactions.total_blocks' => 'Blocks: :blocks',
    'transactions.total_extrinsics' => 'Extrinsics: :extrinsics',
    'clear_cache.description' => 'Clears the Enjin Platform cache.',
    'clear_cache.finished' => 'Enjin Platform cache cleared.',
];
