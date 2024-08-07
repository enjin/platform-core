<?php

return [
    'account_exists_in_collection' => 'コレクション :collectionIdで:accountのコレクションアカウントが見つかりませんでした。',
    'account_exists_in_token' => 'コレクション :collectionIdとトークン :tokenIdで:accountのトークンアカウントが見つかりませんでした。',
    'account_exists_in_wallet' => '指定された:attributeが見つかりませんでした。',
    'approval_exists_in_collection' => 'コレクション :collectionIdで:operatorの承認が見つかりませんでした。',
    'approval_exists_in_token' => 'コレクション :collectionIdとトークン :tokenIdで:operatorの承認が見つかりませんでした。',
    'attribute_exists_in_collection' => 'キーは指定されたコレクションに存在しません。',
    'check_token_count' => 'トークン総量の:totalは:maxTokenトークンの上限を超えました。',
    'distinct_attribute' => ':attributeは異なる属性キーの配列である必要があります。',
    'distinct_multi_asset' => ':attributeは異なるマルチアセットの配列である必要があります。',
    'future_block' => ':attributeは少なくとも:blockである必要があります。',
    'is_collection_owner' => 'あなたは指定された:attributeを所有していません。',
    'is_managed_wallet' => ':attributeはこのプラットフォームが管理するウォレットではありません。',
    'key_doesnt_exit_in_token' => 'キーは指定されたトークンに存在しません。',
    'max_big_int' => ':attributeが大きすぎます。可能な最大値は:maxです。',
    'max_token_balance' => ':attributeは無効です。トークンアカウント残高より大きい金額が指定されています。',
    'min_big_int' => ':attributeが小さすぎます。可能な最低値は:minです。',
    'min_token_deposit' => ':attributeが小さすぎます。最低トークンデポジットは0.01 ENJであるため、「initialSupply × unitPrice」は10^16より大きくなる必要があります。',
    'mutation.behavior.isCurrency.accepted' => 'isCurrencyパラメーターはtrueのみを受け入れます。通貨にしない場合は省略できます。',
    'no_tokens_in_collection' => ':attributeには既存のトークンがあってはいけません。',
    'token_doesnt_exist_in_collection' => ':attributeは指定されたコレクションに既に存在します。',
    'token_encode_doesnt_exist_in_collection' => ':attributeは指定されたコレクションに既に存在します。',
    'token_encode_exist_in_collection' => ':attributeは指定されたコレクションに存在しません。',
    'token_encode_exists' => ':attributeは存在しません。',
    'token_exists_in_collection' => ':attributeは指定されたコレクションに存在しません。',
    'valid_hex' => ':attributeには無効な16進文字列があります。',
    'valid_royalty_percentage' => 'ロイヤルティに有効な:attributeは、0.1%～50%で、小数点以下 7 桁までです。',
    'valid_substrate_account' => ':attributeは有効なSubstrateアカウントではありません。',
    'valid_substrate_address' => ':attributeは有効なSubstrateアドレスではありません。',
    'valid_substrate_transaction_id' => ':attributeには有効なSubstrateトランザクションIDがありません。',
    'valid_verification_id' => '認証IDは有効ではありません。',
    'numeric' => ':attributeは数値である必要があります。',
];
