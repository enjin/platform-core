<?php

namespace Enjin\Platform\Http\Controllers;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\TypeKind;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laragraph\Utils\RequestParser;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\GraphQLController as GraphQLGraphQLController;

class GraphQLController extends GraphQLGraphQLController
{
    /**
     * Handle graphql query.
     */
    public function query(Request $request, RequestParser $parser, Repository $config, GraphQL $graphql): JsonResponse
    {
        $response = parent::query($request, $parser, $config, $graphql);
        if ($this->isIntrospection($request, $parser)) {
            return $this->translateVendorTexts($response);
        }

        return $response;
    }

    /**
     * Translate vendor texts.
     */
    protected function translateVendorTexts(JsonResponse $response): JsonResponse
    {
        $data = $response->getData(true);

        $this->translateSchemaTypes($data);
        $this->translateSchemaDirectives($data);

        return response()->json(
            $data,
            200,
            config('graphql.headers', []),
            config('graphql.json_encoding_options', 0)
        );
    }

    /**
     * Translate schema types texts.
     */
    protected function translateSchemaTypes(array &$data): void
    {
        foreach (Arr::get($data, 'data.__schema.types', []) as $i => $type) {
            if (in_array($type['kind'], ['OBJECT', 'ENUM', 'SCALAR'])) {
                match ($type['name']) {
                    Type::INT => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::scalar.int.description')),
                    Type::BOOLEAN => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::scalar.boolean.description')),
                    Type::STRING => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::scalar.string.description')),
                    Type::FLOAT => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::scalar.float.description')),
                    Type::ID => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::scalar.id.description')),
                    '__Schema' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::schema.description')),
                    '__Type' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::type.description')),
                    '__Directive' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::directive.description')),
                    '__Field' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::field.description')),
                    '__InputValue' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::inputvalue.description')),
                    '__EnumValue' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::enumvalue.description')),
                    '__TypeKind' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::typekind.description')),
                    '__DirectiveLocation' => Arr::set($data, "data.__schema.types.{$i}.description", __('enjin-platform::directivelocation.description')),
                    default => ''
                };

                if (isset($type['fields'])) {
                    foreach ($type['fields'] as $k => $field) {
                        match ($field['name']) {
                            'queryType' => Arr::set($data, "data.__schema.types.{$i}.fields.{$k}.description", __('enjin-platform::schema.types.field.queryType')),
                            'mutationType' => Arr::set($data, "data.__schema.types.{$i}.fields.{$k}.description", __('enjin-platform::schema.types.field.mutationType')),
                            'subscriptionType' => Arr::set($data, "data.__schema.types.{$i}.fields.{$k}.description", __('enjin-platform::schema.types.field.subscriptionType')),
                            'directives' => Arr::set($data, "data.__schema.types.{$i}.fields.{$k}.description", __('enjin-platform::schema.types.field.directives')),
                            'types' => Arr::set($data, "data.__schema.types.{$i}.fields.{$k}.description", __('enjin-platform::schema.types.field.types')),
                            'defaultValue' => Arr::set($data, "data.__schema.types.{$i}.fields.{$k}.description", __('enjin-platform::schema.inputvalue.field.defaultValue')),
                            default => ''
                        };
                    }
                }

                if (isset($type['enumValues'])) {
                    foreach ($type['enumValues'] as $k => $value) {
                        match ($value['name']) {
                            TypeKind::SCALAR => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.SCALAR')),
                            TypeKind::OBJECT => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.OBJECT')),
                            TypeKind::INTERFACE => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.INTERFACE')),
                            TypeKind::UNION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.UNION')),
                            TypeKind::ENUM => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.ENUM')),
                            TypeKind::INPUT_OBJECT => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.INPUT_OBJECT')),
                            TypeKind::LIST => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.LIST')),
                            TypeKind::NON_NULL => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.enumValues.NON_NULL')),
                            DirectiveLocation::QUERY => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.QUERY')),
                            DirectiveLocation::MUTATION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.MUTATION')),
                            DirectiveLocation::SUBSCRIPTION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.SUBSCRIPTION')),
                            DirectiveLocation::FIELD => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.FIELD')),
                            DirectiveLocation::FRAGMENT_DEFINITION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.FRAGMENT_DEFINITION')),
                            DirectiveLocation::FRAGMENT_SPREAD => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.FRAGMENT_SPREAD')),
                            DirectiveLocation::INLINE_FRAGMENT => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.INLINE_FRAGMENT')),
                            DirectiveLocation::VARIABLE_DEFINITION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.VARIABLE_DEFINITION')),
                            DirectiveLocation::SCHEMA => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.SCHEMA')),
                            DirectiveLocation::SCALAR => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.SCALAR')),
                            DirectiveLocation::OBJECT => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.OBJECT')),
                            DirectiveLocation::FIELD_DEFINITION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.FIELD_DEFINITION')),
                            DirectiveLocation::ARGUMENT_DEFINITION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.ARGUMENT_DEFINITION')),
                            DirectiveLocation::IFACE => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.INTERFACE')),
                            DirectiveLocation::UNION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.UNION')),
                            DirectiveLocation::ENUM => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.ENUM')),
                            DirectiveLocation::ENUM_VALUE => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.ENUM_VALUE')),
                            DirectiveLocation::INPUT_OBJECT => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.INPUT_OBJECT')),
                            DirectiveLocation::INPUT_FIELD_DEFINITION => Arr::set($data, "data.__schema.types.{$i}.enumValues.{$k}.description", __('enjin-platform::schema.types.directiveLocation.INPUT_FIELD_DEFINITION')),
                            default => ''
                        };
                    }
                }
            }
        }
    }

    /**
     * Translate schema directives texts.
     */
    protected function translateSchemaDirectives(array &$data): void
    {
        foreach (Arr::get($data, 'data.__schema.directives', []) as $i => $directive) {
            match ($directive['name']) {
                'include' => Arr::set($data, "data.__schema.directives.{$i}.description", __('enjin-platform::schema.directives.include')),
                'skip' => Arr::set($data, "data.__schema.directives.{$i}.description", __('enjin-platform::schema.directives.skip')),
                'deprecated' => Arr::set($data, "data.__schema.directives.{$i}.description", __('enjin-platform::schema.directives.deprecated')),
                default => ''
            };

            foreach ($directive['args'] as $k => $arg) {
                match (true) {
                    'if' == $arg['name'] && 'include' == $directive['name'] => Arr::set($data, "data.__schema.directives.{$i}.args.{$k}.description", __('enjin-platform::schema.directives.include.args.if')),
                    'if' == $arg['name'] && 'skip' == $directive['name'] => Arr::set($data, "data.__schema.directives.{$i}.args.{$k}.description", __('enjin-platform::schema.directives.skip.args.if')),
                    'reason' == $arg['name'] && 'deprecated' == $directive['name'] => Arr::set($data, "data.__schema.directives.{$i}.args.{$k}.description", __('enjin-platform::schema.directives.deprecated.reason')),
                    default => ''
                };
            }
        }
    }

    /**
     * Check if query is doing introspection.
     */
    protected function isIntrospection(Request $request, RequestParser $parser): bool
    {
        if (!$requests = $parser->parseRequest($request)) {
            return false;
        }

        foreach (Arr::wrap($requests) as $operation) {
            if (!$operation->query) {
                return false;
            }
            if ($node = Parser::parse($operation->query)) {
                if ('__schema' == $node->definitions->offsetGet(0)?->selectionSet?->selections?->offsetGet(0)?->name?->value) {
                    return true;
                }
            }
        }

        return false;
    }
}
