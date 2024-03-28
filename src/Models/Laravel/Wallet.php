<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\WalletFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\Wallet as WalletMethods;
use Enjin\Platform\Observers\WalletObserver;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends BaseModel
{
    use EagerLoadSelectFields;
    use HasFactory;
    use WalletMethods;

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
        'public_key',
        'external_id',
        'managed',
        'verification_id',
        'network',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'managed' => false,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['address'];

    /**
     * The tokens attribute accessor.
     */
    public function tokens(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->tokenAccounts->pluck('token');
            }
        );
    }

    /**
     * The address attribute accessor.
     */
    protected function address(): Attribute
    {
        return new Attribute(
            get: fn () => match (true) {
                is_null($this->public_key) => null,
                $this->public_key === '0x00' => $this->public_key,
                default => SS58Address::encode($this->public_key)
            }
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(new WalletObserver());
    }

    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id,
        );
    }
}
