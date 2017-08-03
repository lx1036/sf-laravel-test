<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, AssertAgainstBaseline;

    protected const TEST_NAMESPACE = __NAMESPACE__;
}
