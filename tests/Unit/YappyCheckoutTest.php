<?php

namespace BancoGeneral\YappyCheckout\Tests\Unit;

use BancoGeneral\YappyCheckout\YappyCheckout;
use BancoGeneral\YappyCheckout\Facades\YappyCheckoutFacade;
use BancoGeneral\YappyCheckout\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class YappyCheckoutTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app()->bind('yappy_checkout', function () {
            $mock = new MockHandler([
                new Response(200, [], null),
                new Response(200, [], json_encode(['success' => false])),
                new Response(200, [], json_encode([
                    'success' => true,
                    'accessToken' => '123',
                ])),
            ]);
            $client = new Client(['handler' => HandlerStack::create($mock)]);
            return new YappyCheckout($client);
        });
    }

    public function testPaymentUrlGetter()
    {
        $order = 1000;
        $subtotal = 10;
        $tax = 0.07 * $subtotal;
        $total = $subtotal + $tax;

        $this->assertNull(YappyCheckoutFacade::getPaymentUrl($order, $subtotal, $tax, $total));

        $this->assertNull(YappyCheckoutFacade::getPaymentUrl($order, $subtotal, $tax, $total));

        $this->assertIsString(filter_var(
            YappyCheckoutFacade::getPaymentUrl($order, $subtotal, $tax, $total),
            FILTER_VALIDATE_URL
        ));
    }

    public function testPaymentStatusGetter()
    {
        $request = [
            'orderId' => '123',
            'status' => 'E',
            'domain' => 'http://domain.com',
            'hash' => 'badhash',
        ];

        $this->assertNull(YappyCheckoutFacade::getPaymentStatus([]));

        $this->assertNull(YappyCheckoutFacade::getPaymentStatus($request));

        $request['hash'] = hash_hmac(YappyCheckout::HASH_TYPE, implode([
            $request['orderId'],
            $request['status'],
            $request['domain'],
        ]), 'yeyo');
        $succes = YappyCheckoutFacade::getPaymentStatus($request);
        $this->assertArrayHasKey('order_id', $succes);
        $this->assertArrayHasKey('status', $succes);
    }
}
