<?php

namespace BancoGeneral\YappyCheckout\Facades;

use Illuminate\Support\Facades\Facade;

class YappyCheckoutFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'yappy_checkout';
    }
}
