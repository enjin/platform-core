<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\VerificationFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\Verification as VerificationMethods;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends BaseModel
{
    use HasFactory;
    use VerificationMethods;
    use EagerLoadSelectFields;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    public $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    public $fillable = [
        'verification_id',
        'code',
        'public_key',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['address'];

    /**
     * The address attribute accessor.
     */
    protected function address(): Attribute
    {
        return new Attribute(
            get: fn () => SS58Address::encode($this->public_key)
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): VerificationFactory
    {
        return VerificationFactory::new();
    }
}
