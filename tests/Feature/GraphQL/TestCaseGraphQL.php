<?php

namespace Enjin\Platform\Tests\Feature\GraphQL;

use Enjin\Platform\Facades\Package;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasConvertableObject;
use Enjin\Platform\Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\ExpectationFailedException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class TestCaseGraphQL extends TestCase
{
    use HasConvertableObject;

    protected static array $queries = [];
    protected static bool $initialized = false;
    protected bool $fakeEvents = true;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$initialized) {
            $this->artisan('migrate:fresh');
            $this->loadQueries();

            self::$initialized = true;
        }
    }

    public function graphql(string $query, array $arguments = [], ?bool $expectError = false, ?array $opts = [])
    {
        $result = GraphQL::queryAndReturnResult(self::$queries[$query], $arguments, $opts);
        $data = $result->toArray();

        $assertMessage = null;

        if (!$expectError && isset($data['errors'])) {
            $appendErrors = '';

            if (isset($data['errors'][0]['trace'])) {
                $appendErrors = "\n\n" . $this->formatSafeTrace($data['errors'][0]['trace']);
            }

            $assertMessage = "Probably unexpected error in GraphQL response:\n"
                . var_export($data, true)
                . $appendErrors;
        }

        unset($data['errors'][0]['trace']);

        if ($assertMessage) {
            throw new ExpectationFailedException($assertMessage);
        }

        $previous = Arr::first($result->errors)?->getPrevious();

        if (!is_null($previous) && $previous::class === ValidationException::class) {
            $data['errors'] = $previous->validator->errors()->getMessages();
            $data['error'] = $previous->getMessage();
        } elseif (Arr::get($data, 'errors.0.message') === 'validation') {
            $data['error'] = $previous->getValidatorMessages()->toArray();
        } elseif (Arr::get($data, 'errors.0.message') !== null) {
            $data['error'] = $data['errors'][0]['message'];
        }

        return $expectError ? $data : Arr::get($data['data'], $query);
    }

    /**
     * Get the package aliases.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Package' => Package::class,
        ];
    }

    /**
     * Helper to dispatch an HTTP GraphQL requests.
     */
    protected function httpGraphql(string $method, array $options = [], array $headers = []): mixed
    {
        $query = self::$queries[$method];
        $expectedHttpStatusCode = $options['httpStatusCode'] ?? 200;
        $expectErrors = $options['expectErrors'] ?? false;
        $variables = $options['variables'] ?? null;
        $schemaName = $options['schemaName'] ?? null;

        $payload = ['query' => $query];
        if ($variables) {
            $payload['variables'] = $variables;
        }

        $response = $this->json(
            'POST',
            '/graphql' . ($schemaName ? "/{$schemaName}" : ''),
            $payload,
            $headers
        );
        $result = $response->getData(true);

        $httpStatusCode = $response->getStatusCode();
        if ($expectedHttpStatusCode !== $httpStatusCode) {
            self::assertSame($expectedHttpStatusCode, $httpStatusCode, var_export($result, true) . "\n");
        }

        $assertMessage = null;
        if (!$expectErrors && isset($result['errors'])) {
            $appendErrors = '';
            if (isset($result['errors'][0]['trace'])) {
                $appendErrors = "\n\n" . $this->formatSafeTrace($result['errors'][0]['trace']);
            }

            $assertMessage = "Probably unexpected error in GraphQL response:\n"
                . var_export($result, true)
                . $appendErrors;
        }
        unset($result['errors'][0]['trace']);

        if ($assertMessage) {
            throw new ExpectationFailedException($assertMessage);
        }

        return Arr::first($result['data']);
    }

    /**
     * Load all queries from the Resources directory.
     */
    protected function loadQueries(): void
    {
        $files = scandir(__DIR__ . '/Resources');
        collect($files)
            ->filter(fn ($file) => str_ends_with($file, '.gql') || str_ends_with($file, '.graphql'))
            ->each(
                fn ($file) => self::$queries[str_replace(['.gql', '.graphql'], '', $file)] = file_get_contents(__DIR__ . '/Resources/' . $file)
            );
    }

    /**
     * Converts the trace as generated from \GraphQL\Error\FormattedError::toSafeTrace
     * to a more human-readable string for a failed test.
     */
    private function formatSafeTrace(array $trace): string
    {
        return implode(
            "\n",
            array_map(static function (array $row, int $index): string {
                $line = "#{$index} ";
                $line .= $row['file'] ?? '';

                if (isset($row['line'])) {
                    $line .= "({$row['line']}) :";
                }

                if (isset($row['call'])) {
                    $line .= ' ' . $row['call'];
                }

                if (isset($row['function'])) {
                    $line .= ' ' . $row['function'];
                }

                return $line;
            }, $trace, array_keys($trace))
        );
    }
}
