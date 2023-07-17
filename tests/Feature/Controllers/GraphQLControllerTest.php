<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;

class GraphQLControllerTest extends TestCaseGraphQL
{
    use HasHttp;

    public function test_it_can_query_introspsection(): void
    {
        $response = $this->json(
            'POST',
            '/graphql',
            ['query' => '
                query IntrospectionQuery {
                    __schema {
                        queryType
                        {
                            name
                        }
                        mutationType
                        {
                            name
                        }
                        types
                        {
                            ...FullType
                        }
                        directives
                        {
                            name
                            description
                            locations
                            args
                            {
                                ...InputValue
                            }
                        }
                    }
                }
                fragment FullType on __Type
                {
                    kind
                    name
                    description
                    fields(includeDeprecated: true)
                    {
                        name
                        description
                        args
                        {
                            ...InputValue
                        }
                        type
                        {
                            ...TypeRef
                        }
                        isDeprecated
                        deprecationReason
                    }
                    inputFields
                    {
                        ...InputValue
                    }
                    interfaces
                    {
                        ...TypeRef
                    }
                    enumValues(includeDeprecated: true)
                    {
                        name
                        description
                        isDeprecated
                        deprecationReason
                    }
                    possibleTypes
                    {
                        ...TypeRef
                    }
                }
                fragment InputValue on __InputValue
                {
                    name
                    description
                    type
                    {
                        ...TypeRef
                    }
                    defaultValue
                }
                fragment TypeRef on __Type
                {
                    kind
                    name
                    ofType
                    {
                        kind
                        name
                        ofType
                        {
                            kind
                            name
                            ofType
                            {
                                kind
                                name
                                ofType
                                {
                                    kind
                                    name
                                    ofType
                                    {
                                        kind
                                        name
                                        ofType
                                        {
                                            kind
                                            name
                                            ofType
                                            {
                                                kind
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            ',
            ]
        );

        $this->assertTrue($response->isOk());
    }
}
