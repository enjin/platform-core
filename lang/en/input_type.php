<?php

return [
    'balance_transfer.description' => 'The params to make a balance transfer.',
    'burn_params.description' => 'The params to burn a token.',
    'burn_params.field.removeTokenStorage' => 'If true, the token storage will be removed if no tokens are left. Defaults to False.',
    'collection_mutation.description' => 'The params that can be mutated for a collection.',
    'collection_mutation.field.owner' => 'The new owner account of the collection.',
    'collection_mutation.field.royalty' => 'The new royalty of the collection.',
    'create_token_params.description' => 'The params to create a token.',
    'create_token_params.field.attributes' => 'Set initial attributes for this token.',
    'create_token_params.field.cap' => 'The token cap (if required). A cap of 1 will create this token as a Single Mint type to produce an NFT.',
    'create_token_params.field.initialSupply' => 'The initial supply of tokens to mint to the specified recipient. Must not exceed the token cap if set.',
    'create_token_params.field.listingForbidden' => 'If the token can be listed in the marketplace.',
    'create_token_params.field.unitPrice' => '(DEPRECATED) The price of each token. The price cannot be zero and unitPrice * totalSupply must be greater than the token account deposit.',
    'create_token_params.field.accountDepositCount' => 'The number of tokens accounts that can be created before a deposit is required.',
    'create_token_params.field.infusion' => 'The amount of ENJ infused into each token.',
    'create_token_params.field.anyoneCanInfuse' => 'If anyone can infuse ENJ into this token.',
    'create_token_params.field.metadata' => 'The metadata for the token.',
    'encode_token_id.field.data' => 'The data to encode into a token ID.  Check the docs for the different encoder payload requirements.',
    'encode_token_id.field.type' => 'The encoding strategy to use to encode the token ID. Defaults to HASH.',
    'encodeable_token_id.description' => 'The params to encode the token ID.',
    'erc1155_encoder.description' => 'ERC1155 Style Token ID.',
    'market_policy.description' => 'The marketplace policy for a collection.',
    'market_policy.field.royalty' => 'The royalty set to this marketplace policy.',
    'mint_policy.description' => 'The mint policy for a new collection.',
    'mint_policy.field.forceCollapsingSupply' => 'Set whether the tokens in this collection will be minted with a collapsing supply. This would indicate that tokens burned will not be able to be re-minted.',
    'mint_policy.field.forceSingleMint' => '(DEPRECATED) Set whether the tokens in this collection will be minted as SingleMint types. This would indicate the tokens in this collection are NFTs.',
    'mint_recipient.description' => 'The recipient account for a mint.',
    'mint_recipient.field.account' => 'The recipient account of the token.',
    'mint_recipient.field.createParams' => 'The params for creating a new token.',
    'mint_recipient.field.mintTokenParams' => 'The params for minting a token.',
    'mint_token_params.description' => 'The params to mint a token.',
    'mint_token_params.field.unitPrice' => '(DEPRECATED) Leave as null if you want to keep the same unitPrice. You can also put a value if you want to change the unitPrice. Please note you can only increase it and a deposit to the difference of every token previously minted will also be needed.',
    'multi_token_id.description' => 'The unique identifier for a token. Composed using a collection ID and a token ID.',
    'multi_token_id.field.collectionId' => 'The collection id of a multi token.',
    'multi_token_id.field.tokenId' => 'The token ID of a multi token.',
    'mutation_royalty.description' => 'The royalty for a new collection or token.',
    'mutation_royalty.field.beneficiary' => 'The account that will receive the royalty.',
    'mutation_royalty.field.isCurrency' => 'If the token is a currency.',
    'mutation_royalty.field.percentage' => 'The amount of royalty the beneficiary receives in percentage.',
    'operator_transfer_params.description' => "The params to make an operator transfer. Operator transfers are transfers that you make using tokens from somebody else's wallet as the source. To make this type of transfer the source wallet owner must approve you for transferring their tokens.",
    'operator_transfer_params.field.source' => 'The source account of the token.',
    'simple_transfer_params.description' => 'The params to make a simple transfer.',
    'token_data.description' => 'Data for a token on the Ethereum network.',
    'token_freeze_state.description' => 'The freeze state of the token.',
    'token_id_encoder.erc1155.description' => 'Creates an integer representation from an ERC1155 style token input.',
    'token_id_encoder.erc1155.index.description' => 'A 64bit integer index.  This will be converted to hex and concatenated with the tokenId to make the final unique NFT id.  Defaults to 0 is not supplied.',
    'token_id_encoder.erc1155.token_id.description' => 'A 16 character hex formatted ERC1155 style token id, e.g. 0x1080000000000123.',
    'token_id_encoder.hash.description' => 'Hashes an arbitrary object into an integer.',
    'token_id_encoder.integer.description' => 'A 128bit unsigned integer, the native format for Substrate.',
    'token_id_encoder.string_id.description' => 'Converts a string into a hex value, then converts that to an integer.  This encoding is reversible.',
    'token_market_behavior.description' => 'The market behavior for a token.',
    'token_mint_cap.description' => 'The token mint cap type and value.',
    'token_mint_cap.field.amount' => 'The cap amount when using the SUPPLY type.',
    'token_mint_cap.field.type' => 'The type of mint cap for this token. A SINGLE_MINT type means a token can only be minted once, and cannot be re-minted once burned. A SUPPLY type allows you to set a limit on the total number of circulating tokens that can be minted, this type allows for burned tokens to be re-minted even if the supply amount is 1.',
    'token_mutation.description' => 'The params that can be mutated for a token.',
    'token_mutation.field.behavior' => 'Set if the token has royalty or is a currency. If null, the behavior will not be changed.',
    'token_mutation.field.listingForbidden' => 'Set if the token can be listed on the marketplace. If null, the listingForbidden property will not be changed.',
    'transfer_recipient.description' => 'The recipient account for a transfer.',
    'transfer_recipient.field.operatorTransferParams' => 'The params for an operator transfer.',
    'transfer_recipient.field.simpleTransferParams' => 'The params for a simple transfer.',
    'transfer_recipient.field.transferBalanceParams' => 'The params for a balance transfer.',
];
