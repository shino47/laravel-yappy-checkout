<?php

namespace BancoGeneral\YappyCheckout\Tests;

use BancoGeneral\YappyCheckout\YappyCheckout;
use BancoGeneral\YappyCheckout\YappyCheckoutServiceProvider;
use GuzzleHttp\Client;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app()->bind(YourService::class, function() {
            // TODO: return new YappyCheckout(new Client());
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            YappyCheckoutServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // TODO: config vars
    }

    protected function getPackageAliases($app)
    {
        return [
            'YappyCheckout' => YappyCheckout::class,
        ];
    }
}
