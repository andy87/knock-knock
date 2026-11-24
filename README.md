# KnockKnock

**English** · [Русский](docs/translations/README.ru.md) · [Deutsch](docs/translations/README.de.md) · [Français](docs/translations/README.fr.md) · [日本語](docs/translations/README.ja.md) · [Español](docs/translations/README.es.md) · [简体中文](docs/translations/README.zh-CN.md) · [Português (BR)](docs/translations/README.pt-BR.md) · [한국어](docs/translations/README.ko.md)

A small, immutable, dependency-free PHP HTTP client built on ext-cURL. A cleaner rewrite of
[andy87/knock-knock](https://github.com/andy87/knock-knock): same idea (a minimal cURL facade with
auth, events and fake responses), restructured for PHP 8.2+.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## Contents

- [Install](#install)
- [Quick start](#quick-start)
- [Sending requests](#sending-requests)
- [Options reference](#options-reference)
- [Responses](#responses)
- [Error handling](#error-handling)
- [Events](#events)
- [Examples](#examples)
- [Testing without network](#testing-without-network)
- [Advanced: raw cURL options and custom transports](#advanced)
- [What changed vs. the original](#what-changed-vs-the-original)
- [Layout and tests](#layout-and-tests)

## Install

```
composer require andy87/knock-knock
```

Without Composer: `require '/path/to/knock-knock/autoload.php';`

Requires PHP >= 8.2, ext-curl, ext-json.

## Quick start

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // scheme optional, defaults to https

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // raw body
print_r($response->json());      // decoded JSON (array)
```

The base URL is fixed per client. Paths are always relative to it; passing an absolute URL as a path
throws `InvalidRequestException`.

## Sending requests

Every verb returns a `Response`. The last argument of each verb is an optional array of per-request headers.

| Method | Signature | `$data` goes to |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | query string |
| `head` | `head($path, array $query = [], array $headers = [])` | query string |
| `options` | `options($path, array $query = [], array $headers = [])` | query string |
| `trace` | `trace($path, array $query = [], array $headers = [])` | query string |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | body |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | body |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | body |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | body |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | by method |

**Query strings.** Arrays are encoded with RFC 3986 (`a b` → `a%20b`) and appended with `?` or `&`
depending on whether the path already has a query. A string is appended as-is (leading `?`/`&` trimmed).

**Bodies.** An array is encoded according to the client's content type (JSON by default). A string is sent
untouched, including `"0"`. An empty array/string sends no body and no `Content-Type` header.
A `Content-Type` header is added automatically unless you set one yourself.

```php
$client->post('/users', ['name' => 'Ann']);                     // JSON body
$client->post('/raw', '{"already":"encoded"}');                  // sent as-is
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // per-request header
$client->request(Method::Put, '/things/1', ['a' => 1]);         // generic form
```

## Options reference

### Constructor

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // default: new CurlTransport()
    array $headers = [],                // default headers
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // seconds, whole request
    float $connectTimeout = 10.0,       // seconds, connection phase
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // normally set via on()
);
```

Prefer the fluent `with*` methods below over passing everything to the constructor.

### Fluent configuration

Each call returns a **new** client (the original is untouched), so a base client can be shared safely.
The underlying transport (and its cURL handle) is shared between copies, keeping connections alive.

| Method | Effect | Default |
|---|---|---|
| `withHeaders(array $headers)` | Merge default headers; names are case-insensitive, later wins | none |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | Encoding for array bodies | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | Total and (optionally) connect timeout in seconds | 30 / 10 |
| `withSslVerification(bool $verify)` | Verify peer and host certificate (host check level 2) | `true` |
| `withRedirects(bool $follow = true)` | Follow `3xx` Location redirects | off |
| `on(Event $event, callable $listener)` | Add an event listener | – |

### `ContentType` cases

| Case | Header value | Array encoding |
|---|---|---|
| `Json` | `application/json` | `json_encode` (unicode and slashes unescaped) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query` (RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | arrays not supported — pass a string |

### `Method` cases

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace` put data in the query string.

## Responses

`KnockKnock\Http\Response` is immutable.

| Member | Description |
|---|---|
| `status` | HTTP status code (`int`) |
| `body` | Raw body (`string`; empty for `HEAD`) |
| `headers` | All headers, lower-cased names; repeated headers joined with `, ` |
| `header($name)` | One header, case-insensitive, or `null` |
| `json()` | Decoded body as array; throws `InvalidRequestException` if it isn't a JSON object/array |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | Throws `HttpException` on 4xx/5xx, otherwise returns the response (chainable) |
| `request` | The exact `Request` that was sent (after `BeforeSend` listeners) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

A 3xx response is not an error; with redirects disabled you receive it and can read `header('Location')`.

## Error handling

All library exceptions extend `KnockKnock\Exception\KnockKnockException` (a `RuntimeException`).

| Exception | When |
|---|---|
| `InvalidRequestException` | Bad base URL, absolute URL as path, invalid header name/value (CR/LF/NUL), unencodable data, non-JSON body on `json()` |
| `TransportException` | No response: DNS, refused connection, timeout, TLS failure. `getCode()` is the cURL errno |
| `HttpException` | Only from `->throw()` on 4xx/5xx; the response is in `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // retry/backoff goes here
}
```

## Events

Register any number of listeners per event with `on()` (it returns a new client, like every `with*`).
Listeners for one event run in registration order; each receives the value produced by the previous one.

### Event list

| Event | Fires | Listener receives | Return value |
|---|---|---|---|
| `Event::BeforeSend` | After the request is built, before it goes to the transport | `Request` | optional replacement `Request` (`null` keeps the current one) |
| `Event::AfterSend` | After a response arrives (any HTTP status, including 4xx/5xx) | `Response` | optional replacement `Response` (`null` keeps it) |
| `Event::Failure` | When the transport throws `TransportException` (no response) | `TransportException`, `Request` | ignored; the exception is rethrown afterwards |

### Order of a call

```
build Request ─► BeforeSend* ─► Transport::send ─┬─► AfterSend* ─► Response returned
                                                 └─► Failure*    ─► TransportException rethrown
```

`AfterSend` and `Failure` are mutually exclusive for one call. `->throw()` runs after the call, so
`HttpException` never triggers `Failure`. Exceptions thrown inside a listener propagate to the caller.
Listeners are not called for `InvalidRequestException` raised while building the request.

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` is immutable too: `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

**Persisting every request/response (e.g. to PostgreSQL)?** Events can't carry state from `BeforeSend`
to `AfterSend`, and a later listener may replace the request. Wrap the transport instead — see
[`examples/postgres-request-log.php`](examples/postgres-request-log.php) (INSERT before sending, UPDATE after the response).

## Examples

### Authenticated JSON API with error handling

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

### Basic auth + form encoding

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### Reading headers, redirects and diagnostics

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, not followed by default
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // follows to the final page
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### Local development with a self-signed certificate

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // never in production
```

### Simple retry on transport errors and 5xx

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

### Reusing one base client for several services

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## Testing without network

Pass a `FakeTransport` and nothing touches the network. Responses are returned in queue order;
an empty queue throws `TransportException`.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // status, body, headers
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // inspect every request that was sent
$fake->sent[0]->header('authorization');
```

The repository's own suite also runs real round trips against PHP's built-in server
(`tests/Support/router.php`) — a good template for integration tests.

## Advanced

### Raw cURL options

Use `Request::withCurlOptions()` in a `BeforeSend` listener to set any `CURLOPT_*` for one request.
Raw options override the ones derived from client settings.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // accept gzip/deflate and decode
]));
```

For options that apply to every request of a transport, give them to `CurlTransport` directly:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(Client-derived options such as timeouts and SSL flags still take precedence over these defaults.)

### Custom transport

Implement `KnockKnock\Transport\Transport` (one method, `send(Request): Response`) to use another
HTTP stack, add caching, or record/replay traffic. Throw `TransportException` for network failures.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### Notes

- One cURL handle is reused per transport and reset between requests; options never leak between calls.
- A client is not designed for concurrent use across PHP fibers/threads sharing one handle.
- Header values are checked on construction; an empty header value sends an empty header (`Name;`).

## What changed vs. the original

| Original | This version |
|---|---|
| `Operator` / `Octopus` / `Security` inheritance chain | One `Client`; auth is `withBearer()` / `withBasic()` |
| Mutable `Request` with status machine and `__get` magic | `readonly` `Request` / `Response` value objects, typed properties |
| String constants (`Method::GET`) | Backed enums `Method`, `ContentType`, `Event` |
| 17 exception classes, many unused | 4: `InvalidRequestException`, `TransportException`, `HttpException` (+ base) |
| New cURL handle per request | One handle reused per client: keep-alive, shared DNS/TLS |
| cURL errors stored in `Response.errors` | Thrown as `TransportException`; HTTP 4xx/5xx opt in via `->throw()` |
| `fakeResponse` baked into `Request` | Pluggable `Transport`; `FakeTransport` for tests |
| One handler per event, `on()` throws if taken | Any number of listeners; `BeforeSend`/`AfterSend` can replace the value |
| Hand-written autoloader + Composer | PSR-4 (`KnockKnock\`) with a tiny fallback `autoload.php` |
| Header validation at send time | Validated on construction; absolute URLs in paths rejected |

## Layout and tests

```
src/
  Client.php            facade: config, verbs, events
  Event.php
  Http/                 Method, ContentType, Request, Response
  Transport/            Transport, CurlTransport, FakeTransport
  Exception/
tests/                  zero-dependency runner
examples/basic.php
```

```
composer test        # or: php tests/run.php
```

License: CC BY-SA 4.0 (as the original).
