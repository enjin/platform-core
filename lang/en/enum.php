<?php

return [
    'crypto_signature.description' => 'The type of encryption algorithm used to sign messages.',
    'event_type.description' => 'The event types related to blockchain token transactions.',
    'filter_type.description' => 'The type of filter to use when chaining queries.',
    'freeze_state_type.description' => <<<'MD'
Defines the token's freeze state, determining if and how it can be frozen. Options include:
- **PERMANENT**: The token will be permanently frozen, and the freeze state cannot be changed.
- **TEMPORARY**: The token will be temporarily frozen and can become transferable again if [thawed](https://docs.enjin.io/docs/freezing-thawing#thawing-an-entire-collection).
- **NEVER**: The token cannot be frozen, ensuring it remains transferable at all times.
MD,
    'freezable_type.description' => <<<'MD'
Configures the target of the operation, determining which tokens or token groups will have their transfer restrictions applied or removed. [Learn more](https://docs.enjin.io/docs/freezing-thawing).

Options include:

- **COLLECTION**: Applies or removes transfer restrictions for the entire collection.
- **COLLECTION_ACCOUNT**: Applies or removes transfer restrictions for all tokens in the collection for a specific account, affecting only that account's transfers.
- **TOKEN**: Applies or removes transfer restrictions for a specific token.
- **TOKEN_ACCOUNT**: Applies or removes transfer restrictions for a specific token held by a particular account, affecting only that account's ability to transfer the token.
MD,
    'model_type.description' => 'The model type.',
    'network_type.description' => 'The network type.',
    'pallet_identifier.description' => 'The on-chain pallet identifier.',
    'token_market_behavior_type.description' => 'The market behavior types a token supports.',
    'token_mint_cap_type.description' => <<<'MD'
Configures the supply type for the token. Options include:

- **SUPPLY**: A fixed supply model where the cap amount sets the maximum tokens that can exist in circulation. Burned tokens can be re-minted.
- **COLLAPSING_SUPPLY**: A dynamic supply model where the cap amount defines the total tokens that can ever be minted. Burned tokens cannot be re-minted.
- **SINGLE_MINT (DEPRECATED)**: Allows the token to be minted only once. Functions as a collapsing supply model with the cap amount set to the initial supply.
- **INFINITE**: Allows unlimited token creation with no supply cap.
MD,
    'token_type.description' => 'The token type, fungible or non-fungible.',
    'transaction_method.description' => 'The currently supported transactions.',
    'transaction_result.description' => 'The result status of a transaction.',
    'transaction_state.description' => "The states in a transaction's lifecycle.",
    'dispatch_rule.description' => 'The dispatch rule options.',
    'dispatch_call.description' => 'The dispatch call options.',
    'coverage_policy.description' => 'The coverage policy options.',
];
