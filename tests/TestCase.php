<?php

namespace BancoGeneral\YappyCheckout\Tests;

use BancoGeneral\YappyCheckout\YappyCheckout;
use BancoGeneral\YappyCheckout\YappyCheckoutServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            YappyCheckoutServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('yappy.secret_key', 'eWV5by50ZXN0');
        $app['config']->set('yappy.merchant_id', 'abc123');
        $app['config']->set('yappy.logs_enabled', false);
    }

    protected function getPackageAliases($app)
    {
        return [
            'YappyCheckout' => YappyCheckout::class,
        ];
    }
}
