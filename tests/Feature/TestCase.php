<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Connector;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected const MOCK_PATH = 'tests/fixtures/simple_dataset/vendor'; // tests/fixtures/{$dataset_name}/{$vendor_name}

    public function setUp()
    {
        parent::setUp();
    
        /** @see http://docs.mockery.io/en/latest/cookbook/mocking_hard_dependencies.html?highlight=overload */
        $mock      = \Mockery::mock('overload:' . Connector::class); // Mock hard dependencies
        $mock_path = base_path(static::MOCK_PATH);

        /** @var SplFileInfo[] $files */
        $files = \File::allFiles($mock_path);

        foreach ($files as $file) {
            $api_name = substr($file->getRelativePathname(), 0, -5); // remove '.json'

            // mock Connector::call('accounts/1') && Connector::call('accounts')
            $mock->shouldReceive('call')->with($api_name)->andReturn(\GuzzleHttp\json_decode(file_get_contents($file->getRealPath()), true));
        }
    }
}
