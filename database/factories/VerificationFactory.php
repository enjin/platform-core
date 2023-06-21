<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Verification;
use Facades\Enjin\Platform\Services\Database\VerificationService;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Verification
     */
    protected $model = Verification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $verification = VerificationService::generate();

        return [
            'verification_id' => $verification['verification_id'],
            'code' => $verification['code'],
            'public_key' => null,
        ];
    }
}
