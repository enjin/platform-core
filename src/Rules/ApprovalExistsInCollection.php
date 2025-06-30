<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Database\CollectionService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ApprovalExistsInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * The collection service.
     */
    protected CollectionService $collectionService;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->collectionService = resolve(CollectionService::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->collectionService->approvalExistsInCollection($this->data['collectionId'], $value, false)) {
            $fail('enjin-platform::validation.approval_exists_in_collection')
                ->translate([
                    'operator' => $this->data['operator'],
                    'collectionId' => $this->data['collectionId'],
                ]);
        }
    }
}
