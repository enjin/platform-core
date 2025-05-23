<?php

return [
    'operator_transfer_token.args.params.keepAlive' => 'This flag has been removed from the blockchain and will be ignored.',
    'simple_transfer_token.args.params.keepAlive' => 'This flag has been removed from the blockchain and will be ignored.',
    'batch_set_attribute.args.keepAlive' => 'This flag has been removed from the blockchain and will be ignored.',
    'transfer_balance.description' => 'The extrinsic has been removed from the blockchain in favor of TransferKeepAlive and TransferAllowDeath',
    'mint_token_params.field.unitPrice' => 'Tokens no longer have a unit price in the blockchain. This parameter will be ignored.',
    'create_token_params.field.unitPrice' => 'Tokens no longer have a unit price in the blockchain. This parameter will be ignored.',
    'token.field.minimumBalance' => 'Tokens no longer have a minimum balance in the blockchain. This argument will always return null.',
    'token.field.mintDeposit' => 'Tokens no longer have a mint deposit in the blockchain. This argument will always return null.',
    'token.field.unitPrice' => 'Tokens no longer have a unit price in the blockchain. This argument will always return null.',
    'token_mint_cap_type.description' => 'The enum values `SINGLE_MINT` and `INFINITE` were deprecated.',
    'collection_type.field.forceSingleMint' => 'This field has been deprecated in favor of `forceCollapsingSupply`.',
    'mint_policy.field.forceSingleMint' => 'This field has been deprecated in favor of `forceCollapsingSupply`.',
    'fuel_tank.field.reservesExistentialDeposit' => 'This field doesn\'t exist on-chain anymore',
    'fuel_tank.field.providesDeposit' => 'This field has been replaced by the new CoveragePolicy',
    'dispatch.args.paysRemainingFee' => 'This field has been replaced by the new DispatchSettings',
];
