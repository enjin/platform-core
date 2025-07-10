<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Package;
use Enjin\Platform\Services\Token\Encoder;
use Illuminate\Support\Str;
use Override;
use Rebing\GraphQL\Support\InputType;

class EncodableTokenIdInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'EncodableTokenIdInput',
            'description' => __('enjin-platform::input_type.encodeable_token_id.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        $encoders = Package::getClassesThatImplementInterface(Encoder::class);

        return $encoders->mapWithKeys(fn ($encoder) => [Str::camel(Str::afterLast($encoder, '\\')) => [
            'type' => $encoder::getType(),
            'description' => $encoder::getDescription(),
            'rules' => $encoder::getRules(),
        ]])->all();
    }
}
