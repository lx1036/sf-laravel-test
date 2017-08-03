<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;

class AccountControllerTest extends TestCase
{
    public function testIndex()
    {
        $response = $this->call(Request::METHOD_GET, 'api/v1/accounts');

//        dump($response->json());

//dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        static::assert($response->json());
    }

    public function testShow()
    {
        $response = $this->call(Request::METHOD_GET, 'api/v1/accounts/1');

//        dump($response->json());

//        static::assert($response->json());
    }
}
