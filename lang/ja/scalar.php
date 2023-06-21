<?php

return [
    'boolean.description' => '`Boolean`スカラー型は`true`または`false`です。',
    'float.description' => '`Float`スカラー型は、[IEEE 754]（http://en.wikipedia.org/wiki/IEEE_floating_point）で指定されるとおり、符号付き倍精度小数点数値です。',
    'id.description' => '`ID`スカラー型は一意の識別子で、通常オブジェクトを再取得するため、またはキャッシュのキーとして使用されます。JSONレスポンスの場合、ID型は文字列として出現しますが、人間が読み取れることは意図されていません。入力型として期待されている場合、文字列（`"4"`など）または整数（`4`など）の入力値はIDとして受け入れられます。',
    'int.description' => '`Int`スカラー型は小数値を含まない符号付き整数値です。Intは-(2^31)から2^31 - 1の値です。',
    'string.description' => '`String`スカラー型は、UTF-8文字シーケンスとして表現されるテキストデータです。String型は、GraphQLによって人間が判読できる自由形式テキストを表現するために最もよく使用されます。',
];
