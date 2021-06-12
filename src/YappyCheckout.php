<?php

namespace BancoGeneral\YappyCheckout;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class YappyCheckout
{
    const HASH_TYPE = 'sha256';
    const API_URL = 'https://apipagosbg.bgeneral.cloud/validateapikeymerchand';
    const PAYMENT_URL = 'https://pagosbg.bgeneral.com';
    const PAYMENT_METHOD = 'YAP';
    const TRANSACTION_TYPE = 'VEN';
    const PLATFORM = 'desarrollopropiophp';

    private string $secretKey;
    private string $merchantId;
    private string $merchantUrl;
    private string $successUrl;
    private string $failUrl;
    private string $logsEnabled;
    private string $pluginVersion;
    private string $sandbox;

    public function __construct()
    {
        $this->secretKey = config('yappy.secret_key');
        $this->merchantId = config('yappy.merchant_id');
        $this->merchantUrl = config('yappy.merchant_url') ?? config('app.url');
        $this->successUrl = config('yappy.success_url');
        $this->failUrl = config('yappy.fail_url');
        $this->logsEnabled = config('yappy.logs_enabled');
        $this->pluginVersion = config('yappy.plugin_version');
        $this->sandbox = config('app.env') === 'production' ? 'no' : 'yes';
    }

    /**
     * Devuelve el API Key del comercio.
     *
     * @param  int  $index
     * @return string|null
     */
    private function getApiKey($index): ?string
    {
        $value = base64_decode($this->secretKey);
        $parts = explode('.', $value);
        return $parts[$index] ?? null;
    }

    /**
     * Decodifica la respuesta de la validación y devuelve un array con los datos recibidos.
     * Devuelve null en caso de que la validación de credenciales falle.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return array|null
     */
    private function parseCredentialsValidationResponse($response): ?array
    {
        try {
            $content = json_decode($response->getBody()->getContents(), true);
            if ($content && $content['success']) {
                if (isset($content['unixTimestamp'])) {
                    $content['unixTimestamp'] = $content['unixTimestamp'] * 1000;
                }
                return $content;
            }
            throw new \Exception(json_encode($content));
        }
        catch (\Exception $e) {
            if ($this->logsEnabled) {
                Log::error('[YAPPY] ' . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Valida las credenciales del comercio. Devuelve un array con:
     * - accessToken
     * - unixTimestamp
     * En caso de que la validación falle, retorna null.
     *
     * @return array|null
     */
    private function validateCredentials(): ?array
    {
        $client = new Client();
        $response = $client->post(self::API_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->getApiKey(1),
                'version' => $this->pluginVersion,
            ],
            'json' => [
                'merchantId' => $this->merchantId,
                'urlDomain' => $this->merchantUrl,
            ],
        ]);
        return $this->parseCredentialsValidationResponse($response);
    }

    /**
     * Devuelve el monto en formato de dinero.
     *
     * @param  float  $amount
     * @return string
     */
    private function getMoneyFormat(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Devuelve el campo signature.
     *
     * @param  int  $unixTimestamp
     * @param  string|int  $orderId
     * @param  float  $subtotal
     * @param  float  $tax
     * @param  float  $total
     * @return string
     */
    private function getSignature($unixTimestamp, $orderId, $subtotal, $tax, $total): string
    {
        return hash_hmac(self::HASH_TYPE, implode([
            $this->getMoneyFormat($total),
            $this->merchantId,
            $this->getMoneyFormat($subtotal),
            $this->getMoneyFormat($tax),
            $unixTimestamp,
            self::PAYMENT_METHOD,
            self::TRANSACTION_TYPE,
            $orderId,
            $this->successUrl,
            $this->failUrl,
            $this->merchantUrl,
        ]), $this->getApiKey(0));
    }

    /**
     * Obtiene la URL de la página de pago.
     *
     * @param  string|int  $orderId
     * @param  float  $subtotal
     * @param  float  $tax
     * @param  float  $total
     * @return string|null
     */
    public function getPaymentUrl($orderId, float $subtotal, float $tax, float $total): ?string
    {
        $credentials = $this->validateCredentials();
        if (is_null($credentials)) {
            return null;
        }
        ['unixTimestamp' => $timestamp, 'accessToken' => $jwtToken] = $credentials;
        return self::PAYMENT_URL . '?' . http_build_query([
            'merchantId' => $this->merchantId ,
            'orderId' => $orderId,
            'subtotal' => $this->getMoneyFormat($subtotal),
            'taxes' => $this->getMoneyFormat($tax),
            'total' => $this->getMoneyFormat($total),
            'paymentDate' => $timestamp,
            'paymentMethod' => self::PAYMENT_METHOD,
            'transactionType' => self::TRANSACTION_TYPE,
            'successUrl' => $this->successUrl,
            'failUrl' => $this->failUrl,
            'cancelUrl' => $this->failUrl,
            'domain' => $this->merchantUrl,
            'platform' => self::PLATFORM,
            'jwtToken' => $jwtToken,
            'signature' => $this->getSignature($timestamp, $orderId, $subtotal, $tax, $total),
            'sbx' => $this->sandbox,
        ], '', '&', \PHP_QUERY_RFC3986);
    }

    /**
     * Verifica el estado del pago. Si los datos son inválidos, devuelve null, de lo contrario
     * devuelve un array con el ID de la orden y el estado.
     *
     * @param  array  $data
     * @return array|null
     */
    public function getPaymentStatus(array $data): ?array
    {
        $signature = hash_hmac(self::HASH_TYPE, implode([
            $data['orderId'],
            $data['status'],
            $data['domain'],
        ]), $this->getApiKey(0));
        if (strcmp($data['hash'], $signature) !== 0) {
            Log::error('[YAPPY] Error al verificar el estado: ' . json_encode($data));
            return null;
        }
        return [
            'order_id' => $data['orderId'],
            'status' => $data['status'],
        ];
    }
}
