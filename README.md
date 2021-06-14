# Laravel Yappy Checkout


![Tests](https://github.com/shino47/laravel-yappy-checkout/actions/workflows/tests.yml/badge.svg)
![Latest Stable Version](https://img.shields.io/packagist/v/shino47/laravel-yappy-checkout)
![License](https://img.shields.io/packagist/l/shino47/laravel-yappy-checkout)


Implementación del Botón de Pago Yappy para Laravel.

Este paquete está basado en la [Librería en PHP](https://www.bgeneral.com/desarrolladores/boton-de-pago-yappy/sdk-en-php)
del sitio web de Banco General y nació porque a muchos no nos agrada la idea de versionar librerías.


## Instalación

Para instalar, utiliza el siguiente comando:

```shell
composer require shino47/laravel-yappy-checkout
```

Si tienes Laravel 5.5 o superior, esto es todo (gracias al auto-discovery). Para versiones
anteriores tienes agregar unas líneas a tu archivo `config/app.php`, dentro de la llave
`providers` y dentro de `aliases`:

```php
'providers' => [
    // Otros paquetes por acá
    // ...
    BancoGeneral\YappyCheckout\YappyCheckoutServiceProvider::class,
],

'aliases' => [
    // Otros aliases por acá
    // ...
    'YappyCheckout' => BancoGeneral\YappyCheckout\Facades\YappyCheckoutFacade::class,
],
```


## Configuración

Agrega las siguientes variables a tu `.env` (y a tu `.env.example` en blanco si eres un buen
muchacho):

```ini
YAPPY_SECRET_KEY=
YAPPY_MERCHANT_ID=
YAPPY_MERCHANT_URL=
YAPPY_SUCCESS_URL=
YAPPY_FAIL_URL=
YAPPY_LOGS_ENABLED=
```

Variable           | Tipo     | Descripción
------------------ | -------- | -----------
YAPPY_SECRET_KEY   | `string` | Clave secreta del comercio.
YAPPY_MERCHANT_ID  | `string` | ID del comercio.
YAPPY_MERCHANT_URL | `string` | URL de comercio. Normalmente es igual a APP_URL, pero en ambientes de desarrollo APP_URL puede tomar valores locales.
YAPPY_SUCCESS_URL  | `string` | URL a la cual se redireccionará al usuario cuando se ejecute correctamente la transacción.
YAPPY_FAIL_URL     | `string` | URL a la cual se redireccionará al usuario en caso de que cancele la transacción o haya algún error.
YAPPY_LOGS_ENABLED | `bool`   | Indica si registra errores en los logs. Por defecto es `true`.

Si quieres tener más control sobre la configuración, puedes hacer publish del archivo de
configuración:

```shell
php artisan vendor:publish --provider="BancoGeneral\YappyCheckout\YappyCheckoutServiceProvider"
```

Esto agregará el archivo `config/yappy.php` y el `public/vendor/yappy/js/yappy-checkout.js` a tu
proyecto.

Si no quieres ambos archivos, puedes usar los tags `config` y `assets`:

```shell
# Si sólo necesitas el archivo de configuración
php artisan vendor:publish \
    --provider="BancoGeneral\YappyCheckout\YappyCheckoutServiceProvider" \
    --tag=config

# Si sólo necesitas los assets para el front end
php artisan vendor:publish \
    --provider="BancoGeneral\YappyCheckout\YappyCheckoutServiceProvider" \
    --tag=assets
```


## Uso

El flujo del pago es sencillo y se resume en lo siguiente:

1. El usuario presiona el botón de Pagar/Donar.
2. Nuestra aplicación recibe la petición, valida las credenciales del comercio y genera la URL del
pago.
3. Si todo sale bien, el usuario es redireccionado a la URL generada. Ya aquí el usuario está
fuera de nuestra aplicación. El flujo continúa del lado de Yappy.
4. Luego de que se haga, falle o cancele la transacción, Yappy nos hará una petición a un endpoint
de nuestra aplicación.
5. En esta petición Yappy nos envía el número de orden y el estado de la transacción. Somos libres
de jugar con esta información en nuestra aplicación.

### Agregar el botón a nuestras vistas

Primero, agregamos lo siguiente en donde queramos nuestro botón.

```html
<!-- Este es un botón con el tema por defecto y dice Pagar -->
<button type="submit">
    <div id="Yappy_Checkout_Button"></div>
</button>

<!-- Este es un botón con el tema oscuro y dice Donar -->
<a href="/pagar-con-yappy">
    <div
        id="Yappy_Checkout_Button"
        data-color="dark"
        data-donacion
    ></div>
</a>
```

Lamentablemente, hicieron que el botón por fuerza sea un `div` (en el CSS). En nuestro caso, no nos
vamos a complicar y vamos en envolver ese `div` en una etiqueda `a` o `button`.

Como vemos, el botón acepta atributos de tipo data.

Atributo   | Descripción
---------- | -----------
`color`    | Define el color del botón. Las opciones son `dark` y `brand` (por defecto).
`donacion` | Define el texto del botón. Por defecto es _Pagar_, pero si está presente dirá _Donar_.

Lo siguiente será agregar el script que le dará estilo a nuestro botón. Podemos hacerlo de alguna
de las siguientes maneras.

#### Usando un CDN

```html
<!-- Para la versión 1.0.1 -->
<script src="https://cdn.jsdelivr.net/gh/shino47/laravel-yappy-checkout@1.0.1/resources/assets/js/yappy-checkout.js"></script>

<!-- Para la última versión. Inestable, sólo para machotes. -->
<script src="https://cdn.jsdelivr.net/gh/shino47/laravel-yappy-checkout@main/resources/assets/js/yappy-checkout.js"></script>
```
#### Usando el generado en el vendor:publish

Si hiciste `vendor:publish` ya tienes este script en la carpeta `public/vendor/yappy/js`, por lo que
podemos referenciarlo así:

```html
<script src="{{ asset('vendor/yappy/js/yappy-checkout.js') }}"></script>
```

#### Usando Laravel Mix

El método anterior tiene un inconveniente: si se actualiza, el cliente no lo notará si el navegador
no refresca el cache. Para solucionar eso, nos apoyamos en Laravel Mix. Abrimos el archivo
`webpack.mix.js` de nuestra aplicación y agregamos lo siguiente:

```js
mix.scripts([
    // Otras librerías por acá...
    // ...
    'vendor/shino47/laravel-yappy-checkout/resources/assets/js/yappy-checkout.js',
], 'public/js/vendor.js');
```

Y en nuestras vistas:

```html
<script src="{{ asset(mix('js/vendor.js')) }}"></script>
```

### Redireccionar al cliente

Una vez el usuario haya presionado el enlace o botón, la petición será recibida en nuestro
controlador de la siguiente manera.

```php
use YappyCheckout;

class YeyoController extends Controller
{
    public function redirectToYappyPayment()
    {
        $url = YappyCheckout::getPaymentUrl($orderId, $subtotal, $tax, $total);
        abort_if(is_null($url), 500);
        return redirect()->away($url);
    }
}
```

El método `getPaymentUrl` devuelve `null` si ha habido un error generando la URL; en ese caso
verifica las credenciales del comercio. Este método recibe los siguientes parámetros:

Variable   | Tipo            | Descripción
---------- | --------------- | -----------
`orderId`  | `string`, `int` | ID de la orden. Será usada por Yappy al finalizar la transacción.
`subtotal` | `float`         | Subtotal de la compra.
`tax`      | `float`         | Impuesto de la compra.
`total`    | `float`         | Total de la compra.
`phone`    | `string`        | El número de teléfono del usuario (opcional).

Si todo sale bien, el usuario será redirigido a esa URL generada.

### Recibir el estado de la transacción

Una vez terminada la transacción, Yappy nos enviará el estado a nuestro servidor a
`mi-dominio.com/pagosbg.php`. Lamentablemente, no podemos cambiar esa ruta, así que toca trabajar
con ese `.php` feíto en la URL.

Creamos nuestra ruta en `routes/web.php`.

```php
// Para Laravel 8 en adelante
Route::get('/pagosbg.php', [YeyoController::class, 'yappyPaymentStatus']);

// O para versiones anteriores
Route::get('/pagosbg.php', 'YeyoController@yappyPaymentStatus');
```

Y en nuestro controlador.

```php
use YappyCheckout;
use Illuminate\Http\Request;

class YeyoController extends Controller
{
    public function yappyPaymentStatus(Request $request)
    {
        $data = YappyCheckout::getPaymentStatus($request->all());
        $success = isset($data);
        if ($success) {
            // Mi lógica de negocio a continuación
            $order = \App\Models\Order::find($data['order_id']);
            $order->status = $data['status'];
            $order->save();
        }
        return response()->json([
            'success' => $success,
        ]);
    }
}
```

El método `getPaymentStatus` recibe un `array` con los parámetros de la petición y devuelve
`null` en caso de error o un `array` con el ID de la orden (`order_id`) y el estado de la
transacción (`status`). Los valores de `status` pueden ser:

Código | Descripción
------ | -----------
E      | **Ejecutado**. El cliente confirmó el pago y se completó la compra.
R      | **Rechazado**. El cliente no confirma el pago dentro de los cinco minutos que dura la vida del pedido.
C      | **Cancelado**. El cliente inició el proceso, pero canceló el pedido en el app de Banco General.

**Nota**: Yappy no está enviando peticiones cuando las transacciones quedan rechazadas (`R`). El
tiempo de espera actualmente es cinco minutos, así que pasado este tiempo tendrás que actualizar
el estado a _rechazado_.


## Contribuir

Si deseas contribuir, siéntete libre de subir tu pull request usando el estándar [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
que es el que usa Laravel.

Usa inglés para el código y español para documentar. Código en inglés para que combine con tus
aplicaciones Laravel y documentación en español, porque hay muchos desarrolladores con inglés
malito.





