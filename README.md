# HELA SDK para Laravel

Paquete Laravel para integrar apps de Ometra HELA. El primer cliente incluido es
para consumir la API de Auster desde otros modulos.

## Instalacion

Instala el paquete desde Packagist:

```bash
composer require ometra/hela-sdk:^0.3
```

Laravel descubre automaticamente el service provider y el facade.

## Configuracion

Publica el archivo de configuracion:

```bash
php artisan vendor:publish --tag=hela-sdk-config
```

Variables disponibles:

```dotenv
HELA_SDK_APP_NAME=heimdal
HELA_AUSTER_URL=https://auster.example.test
HELA_AUSTER_TOKEN=
HELA_AUSTER_CLIENTS_API_TOKEN=
HELA_AUSTER_CLIENTS_API_TOKEN_TYPE=API
HELA_SDK_TIMEOUT=30
HELA_SDK_RETRY_TIMES=0
HELA_SDK_RETRY_SLEEP=100
```

`HELA_AUSTER_URL` debe apuntar al host de Auster sin el sufijo `/api`.
El token se envia como `Authorization: Bearer`, igual que espera el middleware
`App\Http\Middleware\Auth\API\ValidateAccessToken` de Auster.

Para `clients-api`, Auster usa `ValidateClientToken`, que espera un bearer con
formato `{tipo}-{token}`. SelfService trabaja con `ClientUserToken`, asi que el
SDK no requiere un token global para esa seccion: llama `clientsApiAsUser($token)`
y el SDK lo envia como `USR-{token}`. Si un flujo administrativo necesita un
token de `ClientsApiTokens`, pasalo explicitamente con `clientsApiAsClient($token)`
y se enviara como `API-{token}`.

## Uso

```php
use Ometra\HelaSdk\Facades\HelaSdk;

$offers = HelaSdk::auster()->offers(status: 'active');
$service = HelaSdk::auster()->serviceByMsisdn('525512345678');
$order = HelaSdk::auster()->order(100);

$firstOffer = $offers->first();
$price = $firstOffer?->publicPrice;
```

Los helpers tipados devuelven DTOs, no respuestas HTTP crudas:

- Listados: `Ometra\HelaSdk\Dtos\DtoCollection`
- Recursos: `OfferDto`, `ServiceDto`, `OrderDto`, `UserProfileDto`, etc.
- Acciones sin recurso principal: `ApiResponseDto`

Cada DTO conserva el payload original en `attributes`, expone `toArray()` y
permite leer campos no tipados con `get($key)` o acceso magico (`$dto->campo`).

Tambien puedes hacer llamadas directas al API de Auster cuando el SDK todavia
no tenga un helper especifico. Esas llamadas directas siguen devolviendo
`Illuminate\Http\Client\Response`:

```php
$response = HelaSdk::auster()->post('/api/log-event/example', [
    'payload' => ['status' => 'ok'],
]);
```

Atajos disponibles inicialmente:

- `offers()` y `offer($id)`
- `portabilitiesByMsisdn($msisdn)`
- `serviceByMsisdn($msisdn)`, `serviceSupplementaries($msisdn)` y `serviceReplacements($msisdn)`
- `validateActivationKey($data)`, `validateSimCard($data)` y `activateService($data)`
- `createOrder($data)`, `order($id)`, `orderByMsisdn($msisdn)`, `orderPayment($id)`, `publishOrder($id)`, `processOrder($id)`, `cancelOrder($id)` y `addOrderPayment($id, $data)`
- `validatePayment($id)` y `cancelPayment($id)`

Los helpers de pedidos usan exclusivamente el contrato REST `/api/orders`:
coleccion y alta en `/api/orders`, pagos en `/payments`, publicacion mediante
`PUT /publication`, codigo de descuento mediante `PUT /discount-code` y
articulos bajo `/items`. Las rutas antiguas no tienen alias de compatibilidad.

### Clients API de Auster

```php
use Ometra\HelaSdk\Facades\HelaSdk;

$profile = HelaSdk::auster()->clientsApi()->clientProfile();
$services = HelaSdk::auster()->clientsApi()->services(filter: '525512345678');
$service = HelaSdk::auster()->clientsApi()->service('525512345678');
$wallet = HelaSdk::auster()->clientsApiAsUser('user-session-token')->walletBalance();
$dashboard = HelaSdk::auster()->clientsApiAsClient('client-token')->dashboard('30d');
$spending = HelaSdk::auster()->clientsApiAsClient('client-token')->report(
    'spending',
    '2026-07-01',
    '2026-07-31',
    'day',
);

$availableBalance = $wallet->availableBalance;
$pendingBalance = $wallet->pendingBalance;
$currency = $wallet->currency;
$status = $wallet->status;

$notifications = HelaSdk::auster()
    ->clientsApiAsUser('user-session-token')
    ->getNotificationPreferences();

$updatedNotifications = HelaSdk::auster()
    ->clientsApiAsUser('user-session-token')
    ->updateNotificationPreferences([
        ['notification_key' => 'welcome_web', 'channels' => ['email', 'sms']],
        ['notification_key' => 'payment_reminder', 'channels' => ['email']],
    ]);
```

Las busquedas textuales en Auster usan el query param `filter`. Usa ese nombre
en listados como `catalogOffers(filter: 'Plan 20')`,
`services(filter: '525512345678')`, `orders(filter: '5551234567')`,
`simCards(filter: 'ICCID')`, `invoices(filter: 'folio')` y
`cfdi(filter: 'UUID')`. Los metodos siguen aceptando `array $query = []` para
compatibilidad y los named arguments tienen precedencia sobre ese arreglo.

Para llamar con un token de usuario devuelto por login:

```php
$login = HelaSdk::auster()->clientsApi()->login([
    'email' => 'cliente@example.test',
    'password' => 'secret',
]);

$userProfile = HelaSdk::auster()
    ->clientsApiAsUser($login->token)
    ->userProfile();
```

Atajos disponibles para `clients-api`:

- `login($data)`, `signup($data)`, `requestPasswordReset($data)`, `validatePasswordResetToken($token)`, `resetPassword($token, $data)`, `logout()` y `logoutAll()`
- `clientProfile()`, `userProfile()`, `getNotificationPreferences()`, `updateNotificationPreferences($preferences)` y `simCards($query)`
- `heartbeat($data)`
- `balance($query)`, `invoices($query)`, `invoice($id)` y `downloadInvoice($id)`
- `walletBalance($query)` —tambien disponible como `wallet($query)`— y `walletTransactions($query)`
- `catalogOffers($query)` devuelve el catalogo efectivo del cliente. `OfferDto` expone `listPrice`, `effectivePrice`, `hasClientPrice`, `purchasable` y `capabilities`, conservando `publicPrice`.
- `dashboard($period)` y `report($type, $from, $to, $groupBy)` para agregados de autogestion. Los tipos de reporte iniciales son `spending`, `services`, `inventory`, `billing` y `operations`.
- `cfdi($query)`, `cfdiOrders()`, `requestCfdi($data)` y `downloadCfdi($uid, $format)`
- `orders($query)`, `order($id)` y `createOrder($data)`
- `portabilities($query)`, `portability($id)`, `portabilityTransitories()`, `validatePortability($data)`, `requestPortability($data)` y `deletePortability($id)`.
  Portabilidad usa exclusivamente `snake_case`. Tanto `validatePortability()` como
  `requestPortability()` requieren `subscriber_type`, con valor `INDIVIDUAL` para
  persona física o `BUSINESS` para persona moral. Los valores canónicos están
  disponibles como `PortabilityDto::SUBSCRIBER_TYPE_INDIVIDUAL` y
  `PortabilityDto::SUBSCRIBER_TYPE_BUSINESS`. Cada solicitud incluye además
  `numbers[].msisdn_ported`, `numbers[].msisdn_transitory` y `numbers[].nip`.
- `services($query)`, `service($msisdn)`, `serviceProfile($msisdn)`, `serviceBags($msisdn)`, `replacementOptions($msisdn)`, `activateOptions($msisdn)`, `topupOptions($msisdn)`, `renewOptions($msisdn)`, `activateService($msisdn, $data)`, `topupService($msisdn, $data)`, `renewService($msisdn, $data)`, `replaceOffer($msisdn, $data)`, `replaceSimCard($msisdn, $data)`, `updateServiceName($msisdn, $data)`, `suspendService($msisdn)`, `resumeService($msisdn)`, `imeiLock($imei)` y `imeiUnlock($imei)`
- `users($query)`, `user($uri)`, `createUser($data)`, `updateUser($uri, $data)` y `deleteUser($uri)`

El servicio tambien se puede resolver desde el contenedor:

```php
use Ometra\HelaSdk\HelaSdk;

$sdk = app(HelaSdk::class);
$sdk->auster()->offers();
```

## Pruebas

```bash
composer test
```
