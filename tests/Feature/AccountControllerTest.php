<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\AssertApiBaseline;

final class AccountControllerTest extends TestCase
{
    use AssertApiBaseline;

    protected const ROUTE_NAME = 'accounts';

    public function testIndex()
    {
        $this->assertApiIndex();
    }

    public function testShow()
    {
        $this->assertApiShow(1);
    }
}
