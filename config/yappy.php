<?php

return [
    /**
     * Clave secreta del comercio.
     */
    'secret_key' => env('YAPPY_SECRET_KEY'),

    /**
     * ID del comercio.
     */
    'merchant_id' => env('YAPPY_MERCHANT_ID'),

    /**
     * URL de comercio. Normalmente es igual a APP_URL, pero en ambientes de desarrollo APP_URL
     * puede tomar valores locales.
     */
    'merchant_url' => env('YAPPY_MERCHANT_URL'),

    /**
     * URL a la cual se redireccionará al usuario cuando se ejecute correctamente la transacción.
     */
    'success_url' => env('YAPPY_SUCCESS_URL', ''),

    /**
     * URL a la cual se redireccionará al usuario en caso de que cancele la transacción o haya
     * algún error.
     */
    'fail_url' => env('YAPPY_FAIL_URL', ''),

    /**
     * Indica si registra errores en los logs.
     */
    'logs_enabled' => env('YAPPY_LOGS_ENABLED', true),

    /**
     * Versión del plugin.
     */
    'plugin_version' => 'P1.0.0',
];
