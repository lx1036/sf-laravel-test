<?php

declare(strict_types=1);

namespace Tests;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Testing\TestResponse;

trait AssertApiBaseline
{
    private static $middlewareGroup = 'web';

    private static $cookies = [
        'web' => [
            'D' => 'DiJeb7IQHo8FOFkXulieyA',
        ],
        'api' => [
        ],
    ];

    private static $servers = [
        'web' => [
            'HTTP_ACCEPT'  => 'application/json',
            'HTTP_ORIGIN'  => 'https://test.company.com',
            'HTTP_REFERER' => 'https://test.company.com',
        ],
        'api' => [
            'HTTP_ACCEPT' => 'application/json',
        ],
    ];

    public static function assertJsonResponse(TestResponse $response, string $message = '', array $ignores = []): TestResponse
    {
        static::assertJsonResponseCode($response, $message);
        static::assertJsonResponseContent($response, $message);
        static::assertJsonResponseHeaders($response, $message);

        return $response;
    }

    public static function assertJsonResponseCode(TestResponse $response, string $message = ''): void
    {
        static::assert($response->getStatusCode(), $message);
    }

    public static function assertJsonResponseContent(TestResponse $response, string $message = '', array $ignores = []): void
    {
        static::assert($response->json(), $message);
    }

    public static function assertJsonResponseHeaders(TestResponse $response, string $message = ''): void
    {
        $headers = $response->headers->all();

        $headers = array_except($headers, [
            'date',
            'set-cookie',
        ]); // except useless headers

        static::assert($headers, $message);
    }

    public static function assert($actual, string $message = '', float $delta = 0.0, int $maxDepth = 10, bool $canonicalize = false, bool $ignoreCase = false): void
    {
        // assert $actual with $expected which is from baseline json file
        // if there is no baseline json file, put $actual data into baseline file (or -d rebase)
        // baseline file path
        // support multiple assertion in a test case

        static $assert_counters = [];
        static $baselines       = [];

        $class     = get_called_class();
        $function  = static::getFunctionName(); // 'testList'
        $signature = "$class::$function";

        if (!isset($assert_counters[$signature])) {
            $assert_counters[$signature] = 0;
        } else {
            $assert_counters[$signature]++;
        }

        $test_id = $assert_counters[$signature];

        $baseline_path = static::getBaselinesPath($class, $function);

        if (!array_key_exists($signature, $baselines)) {
            if (file_exists($baseline_path) && array_search('rebase', $_SERVER['argv'], true) === false) { // '-d rebase'
                $baselines[$signature] = \GuzzleHttp\json_decode(file_get_contents($baseline_path), true);
            } else {
                $baselines[$signature] = [];
            }
        }

        $actual = static::prepareActual($actual);

        if (array_key_exists($test_id, $baselines[$signature])) {
            static::assertEquals($baselines[$signature][$test_id], $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
        } else {
            $baselines[$signature][$test_id] = $actual;

            file_put_contents($baseline_path, \GuzzleHttp\json_encode($baselines[$signature], JSON_PRETTY_PRINT));

            static::assertTrue(true);

            echo 'R';
        }
    }

    /**
     * @param string|string[]|null  $route_parameters
     * @param array $parameters
     *
     * @return mixed
     */
    protected function assertApiIndex($route_parameters = null, array $parameters = [])
    {
        return static::assertApiCall('index', $route_parameters ? (array) $route_parameters : null, $parameters);
    }

    protected function assertApiShow($route_parameters, array $parameters = [])
    {
        assert($route_parameters !== null, '$route_parameters cannot be null');

        return static::assertApiCall('show', (array) $route_parameters, $parameters);
    }

    protected static function getFunctionName(): string
    {
        $stacks = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        do {
            $stack = array_pop($stacks);
        } while ($stack && substr($stack['function'], 0, 4) !== 'test');

        return $stack['function']; // 'testList'
    }

    protected static function getBaselinesPath(string $class, string $function): string
    {
        $class = explode('\\', $class);

        $dir = implode('/', array_merge(
            [strtolower($class[0])],
            array_slice($class, 1, -1),
            ['_baseline', array_pop($class)]
        ));

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        return base_path() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $function . '.json';
    }

    protected static function prepareActual($actual)
    {
        if ($actual instanceof Arrayable) {
            $actual = $actual->toArray();
        }

        if (is_array($actual)) {
            array_walk_recursive($actual, function (&$value, $key): void {
                if ($value instanceof Arrayable) {
                    $value = $value->toArray();
                } elseif ($value instanceof Carbon) {
                    $value = 'Carbon:' . $value->toIso8601String();
                } elseif (in_array($key, ['created_at', 'updated_at', 'deleted_at'], true)) {
                    $value = Carbon::now()->format(DATE_RFC3339);
                }
            });
        }

        return $actual;
    }

    private function assertApiCall(string $route_action, array $route_parameters = null, array $parameters = [])
    {
        [$uri, $method] = static::resolveRouteUrlAndMethod(static::resolveRouteName($route_action), $route_parameters);

        /** @var \Illuminate\Foundation\Testing\TestResponse $response */
        $response = $this->call($method, $uri, $parameters, $this->getCookies(), [], $this->getServers(), null);

        return static::assertJsonResponse($response, '');
    }

    private static function resolveRouteName(string $route_action): string
    {
        return static::ROUTE_NAME . '.' . $route_action;
    }

    private static function resolveRouteUrlAndMethod(string $route_name, array $route_parameters = null)
    {
        $route = \Route::getRoutes()->getByName($route_name);
        assert($route, "Route [$route_name] must be existed.");

        return [route($route_name, $route_parameters), $route->methods()[0]];
    }

    private function getCookies(array $overrides = []): array
    {
        $cookies = $overrides + self::$cookies[static::$middlewareGroup];

        return $cookies;
    }

    private function getServers(array $overrides = []): array
    {
        return $overrides + self::$servers[static::$middlewareGroup];
    }
}
