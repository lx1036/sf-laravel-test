<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestResponse;
use RuntimeException;

trait AssertAgainstBaseline
{
    public static function assertResponse(TestResponse $response, string $message = ''): TestResponse
    {
        // xxx

        return $response;
    }

    public static function assertResponseCode(TestResponse $response, string $message = ''): void
    {
        static::assert($response->status());
    }

    public static function assertResponseHeaders(TestResponse $response, string $message = ''): void
    {
        static::assert($response->headers->all());
    }

    public static function assertResponseContent(TestResponse $response, string $message = ''): void
    {
        static::assert($response->content());
    }

    public static function assert($actual, string $message = ''): void
    {
        static $test_counters = [];
        static $baselines     = [];

        $fqcn     = get_called_class();
        $function  = static::getTestFunctionName();
        $signature = "$fqcn::$function";
        $baseline_file = static::getBaselineDataFile($fqcn, $function);

        if (isset($test_counters[$signature])) {
            $test_counters[$signature]++;
        } else {
            $test_counters[$signature] = 0;
        }

        $test_id       = $test_counters[$signature];

        if (!array_key_exists($signature, $baselines)) {
            if (file_exists($baseline_file)) {
                $baselines[$signature] = \GuzzleHttp\json_decode(file_get_contents($baseline_file), true);
            } else {
                $baselines[$signature] = [];
            }
        }

//        $actual = static::prepareVariableForAssertion($actual);

        if (array_key_exists($test_id, $baselines[$signature])) {
            $expected = $baselines[$signature][$test_id];

            parent::assertEquals($expected, $actual, $message);
        } else {
            $baselines[$signature][$test_id] = $actual;

            if (!file_exists($baseline_file)) {
                mkdir(pathinfo($baseline_file, PATHINFO_DIRNAME), 0755, true);
            }

            if (file_put_contents($baseline_file, \GuzzleHttp\json_encode($baselines[$signature], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                throw new RuntimeException("Can not save test response of [$signature][$test_id] to $baseline_file");
            }

            echo  'R';
        }
    }

    private static function getBaselineDataFile(string $fqcn, string $function): string
    {
        assert(defined('static::TEST_NAMESPACE'), 'TEST_NAMESPACE must be defined in your base TestCase class');

        $class_basename = class_basename($fqcn);

        $dir = substr($fqcn, 6, -strlen($class_basename)); // remove "Tests\" and class_basename

        return base_path('tests/' . str_replace('\\', '/', $dir) . 'baseline/' . $class_basename . DIRECTORY_SEPARATOR . $function . '.json');
    }

    /**
     * @return string e.g. 'Options'
     */
    private static function getTestFunctionName(): string
    {
        $calls = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        do {
            $call = array_pop($calls);
        } while ($call && (!isset($call['function']) || substr($call['function'], 0, 4) !== 'test'));

        if (!$call) {
            throw new RuntimeException('Cannot find any test function: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
        }

        return substr($call['function'], 4); // remove 'test' prefix
    }
}
