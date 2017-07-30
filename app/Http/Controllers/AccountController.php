<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class AccountController extends Controller
{
    /**
     * @var Connector
     */
    private $connector;

    public function index()
    {
        $connector = $this->getConnector();

        return $connector->call('accounts');
    }

    public function show(string $id)
    {
        $connector = $this->getConnector();

        return $connector->call('accounts/' . $id);
    }

    private function getConnector()
    {
        if (!$this->connector) {
            $this->connector = new Connector();
        }

        return $this->connector;
    }
}
