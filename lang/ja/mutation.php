<?php

return [
    'acknowledge_events.args.uuids' => '確認するイベントUUID。',
    'acknowledge_events.description' => 'このミューテーションを使って、キャッシュ済みのイベントを確認してキャッシュから削除します。',
    'approve_collection.args.collectionId' => '承認されるコレクション。',
    'approve_collection.args.operator' => 'コレクションの運営を承認されるアカウント。',
    'approve_collection.description' => '別のアカウントがコレクションアカウントのトークンを転送することを承認します。また、この承認が期限切れとなるブロック番号を指定することもできます。',
    'approve_token.args.amount' => '運営を承認されるトークンの数量。',
    'approve_token.args.collectionId' => '承認されるトークンが属するコレクション。',
    'approve_token.args.currentAmount' => 'オペレーターが所持するトークンの現在の数量。',
    'approve_token.args.expiration' => '承認が期限切れとなるブロック番号。無期限の場合はnullのままにします。',
    'approve_token.args.operator' => 'トークンの運営を承認されるアカウント。',
    'approve_token.args.tokenId' => '承認されるトークンID。',
    'approve_token.description' => '別のアカウントがトークンアカウントから転送を行うことを承認します。また、この承認が期限切れとなるブロック番号とこのアカウントが転送できるトークンの数量を指定することもできます。',
    'batch_mint.description' => 'このメソッドは、複数の発行を1回のトランザクションで一括して行うために使用します。Create TokeとMint Tokenパラメーターを混合させたり、チェーンで失敗する発行を後で修正できるように、continueOnFailureフラグを使って省略したりできます。',
    'batch_set_attribute.args.amount' => '転送する金額。',
    'batch_set_attribute.args.collectionId' => 'コレクションID。',
    'batch_set_attribute.args.keepAlive' => 'trueの場合、残高が最低要件を下回ると、トランザクションは失敗します。デフォルトはfalseです。',
    'batch_set_attribute.args.key' => '属性キー。',
    'batch_set_attribute.args.recipient' => '転送を受け取る受取人のアカウント。',
    'batch_set_attribute.args.value' => '属性値。',
    'batch_set_attribute.description' => 'これは、1回のトランザクションで、コレクションまたはトークンに複数の属性を設定するために使用します。continueOnFailureフラグをtrueに設定すると、すべての有効な属性を設定して、無効な属性を省略することができます。これらは、修正されてから別のトランザクションでもう一度試行されます。',
    'batch_transfer.args.signingAccount' => 'このトランザクションで署名するウォレット。デフォルトはウォレットデーモンです。',
    'batch_transfer.description' => 'このメソッドは、1回のトランザクションで複数のトークンを転送するために使用します。バッチあたり最大250回の異なる転送を含めることができます。continueOnFailureをtrueに設定すると、すべての有効な転送を完了させて、失敗する転送を省略することができます。これらは、修正されてから別のトランザクアクションでもう一度試行されます。',
    'burn.args.params' => 'トークンをバーンするために必要なパラメーター。',
    'burn.description' => 'コレクションを削除し、予約された値を戻します。コレクションはすべてのトークンがバーンされてからのみ、破棄することができます。',
    'common.args.collectionId' => 'このトークンを作成するコレクションID。',
    'common.args.continueOnFailure' => 'trueに設定した場合、バッチ全体を失敗させるデータが省略されます。デフォルトはfalseです。',
    'create_collection.args.attributes' => 'このコレクションの初期属性を設定します。',
    'create_collection.args.explicitRoyaltyCurrencies' => 'このコレクションのトークンの明示的なロイヤルティ通貨を設定します。',
    'create_collection.args.marketPolicy' => 'トークンIDをエンコードするために使用するエンコーディング手法。',
    'create_collection.args.mintPolicy' => 'このコレクションのトークンの発行ポリシーを設定します。',
    'create_collection.description' => '新しいオンチェーンコレクションを作成します。新しいコレクションIDは、オンチェーンで処理された後に、トランザクションイベントで返されます。',
    'create_token.args.recipient' => '初期発行のトークンの受取人アカウント。',
    'create_token.description' => 'コレクションに新しいトークンを作成します。新しいトークンは、指定された受取人アカウントに自動的に転送されます。',
    'create_wallet.args.externalId' => 'このウォレットに設定された外部ID。',
    'create_wallet.description' => '外部IDを使用して、認証されていない新しいウォレットレコードを保存します。',
    'freeze.args.collectionAccount' => '凍結するコレクションアカウント。',
    'freeze.args.collectionId' => '凍結するコレクションID。',
    'freeze.args.freezeType' => '実行する凍結のタイプ。',
    'freeze.args.tokenAccount' => '凍結するトークンアカウント。',
    'freeze.args.tokenId' => '凍結するトークンID。',
    'freeze.description' => 'コレクション、トークン、コレクションアカウント、またはトークンアカウントを凍結します。トークンが凍結している場合、これらを転送またはバーンできません。コレクションまたはコレクションアカウントを凍結すると、その中のすべてのトークンが凍結されます。',
    'link_wallet.description' => '注意：このワークフローとミューテーションはプレースホルダーです。VerifyAccountフローを使用して、ウォレットアカウントをこのプラットフォームに関連付けてください。',
    'mark_and_list_pending_transactions.args.accounts' => 'トランザクションをフィルターするアカウント。',
    'mark_and_list_pending_transactions.description' => '新しい保留中のトランザクションのリストを取得し、それらを処理中としてマークします。',
    'mint_token.args.collectionId' => '発行元のコレクションID。',
    'mint_token.args.recipient' => '発行されているトークンの受取人アカウント。',
    'mint_token.description' => '既存のトークンをさらに発行します。これは、供給上限が1を上回るトークンにのみ適用されます。',
    'mutate_collection.args.collectionId' => 'ミューテーションされるコレクション。',
    'mutate_collection.args.mutation' => 'ミューテーションされるパラメーター。',
    'mutate_collection.args.tokenId' => 'ミューテーションされるトークン。',
    'mutate_collection.description' => 'コレクションのデフォルト値を変更します。',
    'mutate_token.description' => 'トークンのデフォルト値を変更します。',
    'operator_transfer_token.description' => '他の誰かのウォレットのオペレーターとしてトークンを転送します。オペレーター転送は他の誰かのウォレットのトークンをソースとして使用する転送です。この種の転送を行うには、ソースウォレットのオーナーがトークンの転送を承認する必要があります。',
    'remove_collection.description' => '指定されたコレクションから属性を削除します。',
    'remove_token_attribute.description' => '指定されたトークンから属性を削除します。',
    'set_collection_attribute.description' => 'コレクションに属性を設定します。',
    'set_token_attribute.description' => 'トークンに属性を設定します。',
    'set_wallet_account.description' => 'ウォレットモデルにアカウントを設定します。',
    'simple_transfer_token.description' => '単一のトークンを受取人アカウントに転送します。',
    'thaw.args.collectionId' => '解凍するコレクションID。',
    'thaw.args.freezeType' => '実行する解凍のタイプ。',
    'thaw.args.tokenAccount' => '解凍するトークンアカウント。',
    'thaw.args.tokenId' => '解凍するトークンID。',
    'thaw.description' => '以前に凍結されたコレクションまたはトークンを解凍します。',
    'transfer_all_balance.description' => 'アカウントから別のアカウントにすべての残高を転送します。少なくとも既存のデポジットを維持する場合は、keepAlive引数を渡すことができます。',
    'transfer_balance.description' => 'アカウントから別のアカウントに残高を転送します。アカウントに少なくとも既存のデポジットが残されることを確認する場合は、keepAlive引数を渡すことができます。',
    'unapprove_collection.args.collectionId' => '承認が削除されるコレクション。',
    'unapprove_collection.args.operator' => 'コレクション承認が削除されるアカウント。',
    'unapprove_collection.description' => 'コレクションアカウントから転送を行うために、特定のアカウントの承認を削除します。',
    'unapprove_token.args.collectionId' => 'トークンが属するコレクション。',
    'unapprove_token.args.operator' => 'トークン承認が削除されるアカウント。',
    'unapprove_token.args.tokenId' => '承認が削除されるトークン。',
    'unapprove_token.description' => 'トークンアカウントから転送を行うために、特定のアカウントの承認を削除します。',
    'update_external_id.description' => 'ウォレットモデルの外部IDを変更します。',
    'update_transaction.args.state' => '転送の新しい状態。',
    'update_transaction.args.transactionHash' => 'オンチェーントランザクションのハッシュ。',
    'update_transaction.args.transactionId' => 'オンチェーントランザクションid',
    'update_transaction.description' => '新しい状態、トランザクションID、およびトランザクションハッシュでトランザクションを更新します。トランザクションIDとトランザクションハッシュは、一度設定されるとイミュータブルになることに注意してください。',
    'update_transaction.error.hash_and_id_are_immutable' => 'トランザクションのidとハッシュは、一度設定されるとイミュータブルになります。',
    'update_wallet_external_id.cannot_update_id_on_managed_wallet' => 'マネージドウォレットの外部idを更新できません。',
    'verify_account.description' => 'ウォレットはこのミューテーションを呼び出して、ユーザーアカウントのオーナーシップを証明します。',
];
