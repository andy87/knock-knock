# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [日本語](README.ja.md) · **Español** · [简体中文](README.zh-CN.md) · [Português (BR)](README.pt-BR.md) · [한국어](README.ko.md)

Un cliente HTTP para PHP pequeño, inmutable y sin dependencias, basado en ext-cURL. Una reescritura limpia de
[andy87/knock-knock](https://github.com/andy87/knock-knock): la misma idea (una fachada mínima sobre cURL con
autenticación, eventos y respuestas falsas), reestructurada para PHP 8.2+.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## Contenido

- [Instalación](#instalación)
- [Inicio rápido](#inicio-rápido)
- [Envío de peticiones](#envío-de-peticiones)
- [Referencia de opciones](#referencia-de-opciones)
- [Respuestas](#respuestas)
- [Manejo de errores](#manejo-de-errores)
- [Eventos](#eventos)
- [Ejemplos](#ejemplos)
- [Pruebas sin red](#pruebas-sin-red)
- [Avanzado](#avanzado)

## Instalación

```
composer require andy87/knock-knock
```

Sin Composer: `require '/path/to/knock-knock/autoload.php';`

Requiere PHP >= 8.2, ext-curl, ext-json.

## Inicio rápido

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // esquema opcional, https por defecto

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // cuerpo sin procesar
print_r($response->json());      // JSON decodificado (array)
```

La URL base queda fija por cliente. Las rutas son siempre relativas a ella; pasar una URL absoluta como ruta
lanza `InvalidRequestException`.

## Envío de peticiones

Cada verbo devuelve una `Response`. El último argumento es un array opcional de cabeceras para esa petición.

| Método | Firma | `$data` va a |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | query string |
| `head` | `head($path, array $query = [], array $headers = [])` | query string |
| `options` | `options($path, array $query = [], array $headers = [])` | query string |
| `trace` | `trace($path, array $query = [], array $headers = [])` | query string |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | cuerpo |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | cuerpo |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | cuerpo |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | cuerpo |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | según el método |

**Query strings.** Los arrays se codifican según RFC 3986 (`a b` → `a%20b`) y se añaden con `?` o `&`
según la ruta ya tenga query. Una cadena se añade tal cual (se quitan `?`/`&` iniciales).

**Cuerpos.** Un array se codifica según el content type del cliente (JSON por defecto). Una cadena se envía
sin tocar, incluido `"0"`. Un array/cadena vacío no envía cuerpo ni `Content-Type`.
La cabecera `Content-Type` se añade automáticamente salvo que definas una propia.

```php
$client->post('/users', ['name' => 'Ann']);                     // cuerpo JSON
$client->post('/raw', '{"already":"encoded"}');                  // tal cual
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // cabecera por petición
$client->request(Method::Put, '/things/1', ['a' => 1]);         // forma genérica
```

## Referencia de opciones

### Constructor

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // por defecto: new CurlTransport()
    array $headers = [],                // cabeceras por defecto
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // segundos, petición completa
    float $connectTimeout = 10.0,       // segundos, fase de conexión
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // normalmente mediante on()
);
```

Prefiere los métodos fluidos `with*` en lugar de pasarlo todo al constructor.

### Configuración fluida

Cada llamada devuelve un cliente **nuevo** (el original no cambia), así que un cliente base se puede compartir sin riesgo.
El transporte (y su handle de cURL) se comparte entre copias, manteniendo las conexiones abiertas.

| Método | Efecto | Por defecto |
|---|---|---|
| `withHeaders(array $headers)` | Combina cabeceras por defecto; nombres sin distinguir mayúsculas, gana la última | ninguna |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | Codificación de cuerpos array | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | Timeout total y (opcional) de conexión, en segundos | 30 / 10 |
| `withSslVerification(bool $verify)` | Verifica el certificado (peer y host, nivel 2) | `true` |
| `withRedirects(bool $follow = true)` | Sigue redirecciones `3xx` | desactivado |
| `on(Event $event, callable $listener)` | Añade un listener de evento | – |

### Casos de `ContentType`

| Caso | Cabecera | Codificación de array |
|---|---|---|
| `Json` | `application/json` | `json_encode` (Unicode y barras sin escapar) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query` (RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | arrays no soportados — pasa una cadena |

### Casos de `Method`

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace` ponen los datos en la query string.

## Respuestas

`KnockKnock\Http\Response` es inmutable.

| Miembro | Descripción |
|---|---|
| `status` | Código HTTP (`int`) |
| `body` | Cuerpo sin procesar (`string`; vacío en `HEAD`) |
| `headers` | Todas las cabeceras, nombres en minúsculas; las repetidas se unen con `, ` |
| `header($name)` | Una cabecera, sin distinguir mayúsculas, o `null` |
| `json()` | Cuerpo decodificado como array; lanza `InvalidRequestException` si no es un objeto/array JSON |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | Lanza `HttpException` en 4xx/5xx; si no, devuelve la respuesta (encadenable) |
| `request` | El `Request` realmente enviado (tras `BeforeSend`) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

Una respuesta 3xx no es un error; con las redirecciones desactivadas la recibes y puedes leer `header('Location')`.

## Manejo de errores

Todas las excepciones de la librería extienden `KnockKnock\Exception\KnockKnockException` (una `RuntimeException`).

| Excepción | Cuándo |
|---|---|
| `InvalidRequestException` | URL base inválida, URL absoluta como ruta, nombre/valor de cabecera inválido (CR/LF/NUL), datos no codificables, cuerpo no JSON en `json()` |
| `TransportException` | No hay respuesta: DNS, conexión rechazada, timeout, fallo TLS. `getCode()` es el errno de cURL |
| `HttpException` | Solo desde `->throw()` en 4xx/5xx; la respuesta está en `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // aquí van reintentos/backoff
}
```

## Eventos

Registra cualquier número de listeners por evento con `on()`. Se ejecutan en orden de registro.

| Evento | El listener recibe | Valor de retorno |
|---|---|---|
| `Event::BeforeSend` | `Request` | `Request` de reemplazo opcional (`null` para conservar) |
| `Event::AfterSend` | `Response` | `Response` de reemplazo opcional |
| `Event::Failure` | `TransportException`, `Request` | ignorado; la excepción se relanza después |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` también es inmutable: `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

## Ejemplos

### API JSON autenticada con manejo de errores

```php
$api = (new Client('https://api.example.com/v2'))
    ->withBearer(getenv('API_TOKEN'))
    ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'my-app/1.0'])
    ->withTimeout(15, 5);

$page = $api->get('/posts', ['page' => 2, 'per_page' => 50])->throw()->json();

$post = $api->post('/posts', ['title' => 'Hello', 'tags' => ['a', 'b']])->throw()->json();
$api->patch("/posts/{$post['id']}", ['title' => 'Hello again'])->throw();
$api->delete("/posts/{$post['id']}")->throw();
```

### Autenticación Basic y codificación de formulario

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### Cabeceras, redirecciones y diagnóstico

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, no se sigue por defecto
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // sigue hasta la página final
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### Desarrollo local con certificado autofirmado

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // nunca en producción
```

### Reintento simple ante errores de red y 5xx

```php
function withRetry(Client $c, callable $call, int $tries = 3): Response
{
    for ($i = 1; ; $i++) {
        try {
            $res = $call($c);
            if ($res->status < 500 || $i === $tries) return $res;
        } catch (TransportException $e) {
            if ($i === $tries) throw $e;
        }
        usleep(200_000 * $i);
    }
}

$res = withRetry($api, fn (Client $c) => $c->get('/flaky'));
```

### Un cliente base para varios servicios

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## Pruebas sin red

Pasa un `FakeTransport` y nada toca la red. Las respuestas salen en el orden de la cola;
una cola vacía lanza `TransportException`.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // estado, cuerpo, cabeceras
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // inspecciona cada petición enviada
$fake->sent[0]->header('authorization');
```

La propia suite del repositorio también hace viajes reales contra el servidor integrado de PHP
(`tests/Support/router.php`) — una buena plantilla para pruebas de integración.

## Avanzado

### Opciones cURL en bruto

Usa `Request::withCurlOptions()` en un listener `BeforeSend` para fijar cualquier `CURLOPT_*` en una petición.
Las opciones en bruto prevalecen sobre las derivadas de la configuración del cliente.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // acepta gzip/deflate y decodifica
]));
```

Para opciones que aplican a todas las peticiones de un transporte, pásalas directamente a `CurlTransport`:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(Las opciones derivadas del cliente, como timeouts y flags SSL, siguen teniendo prioridad sobre estos valores por defecto.)

### Transporte propio

Implementa `KnockKnock\Transport\Transport` (un solo método, `send(Request): Response`) para usar otra pila
HTTP, añadir caché o grabar/reproducir tráfico. Lanza `TransportException` ante fallos de red.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### Notas

- Se reutiliza un handle de cURL por transporte y se reinicia entre peticiones; las opciones no se «filtran».
- Un cliente no está pensado para uso concurrente de un mismo handle desde varios fibers/hilos.
- Los valores de cabecera se validan al construirse; un valor vacío envía una cabecera vacía (`Name;`).

Estructura del proyecto, comparación con el original y ejecución de pruebas: consulta el [README en inglés](../../README.md).

Licencia: CC BY-SA 4.0 (igual que el original).
