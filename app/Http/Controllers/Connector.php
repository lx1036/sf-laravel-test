<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class Connector
{
    public function call(string $path): array
    {
        $client = new Client();

        $response = $client->request(Request::METHOD_GET, config('app.url') . DIRECTORY_SEPARATOR . $path);

        return \GuzzleHttp\json_decode($response);
    }
}
