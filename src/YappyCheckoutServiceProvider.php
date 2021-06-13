<?php

namespace BancoGeneral\YappyCheckout;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class YappyCheckoutServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/yappy.php' => config_path('yappy.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/yappy'),
        ], 'assets');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/yappy.php', 'yappy'
        );

        $this->app->bind('yappy_checkout', function($app) {
            return new YappyCheckout(new Client());
        });
    }
}
