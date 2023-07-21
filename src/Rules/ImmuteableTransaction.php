<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Transaction;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ImmuteableTransaction implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     */
    protected array $data = [];

    public function __construct(protected string $column = 'transaction_chain_id')
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (($id = $this->data['id']) && ($transaction = Transaction::find($id))) {
            if (filled($value) && $transaction->{$this->column}) {
                $fail('enjin-platform::mutation.update_transaction.error.hash_and_id_are_immutable')->translate();
            }
        }
    }

    /**
     * Set the data under validation.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
