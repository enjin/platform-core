<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ImmutableTransaction implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    public function __construct(protected string $column = 'transaction_chain_id') {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (($id = $this->data['id']) && ($transaction = Transaction::find($id))) {
            if (filled($value) && $v = $transaction->{$this->column}) {
                if ($value !== $v) {
                    $fail('enjin-platform::mutation.update_transaction.error.hash_and_id_are_immutable')->translate();
                }
            }
        }
    }
}
