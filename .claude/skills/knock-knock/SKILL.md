---
name: knock-knock
description: Use when writing or reviewing PHP code that makes HTTP requests with the andy87/knock-knock library (namespace KnockKnock, class KnockKnock\Client), or when asked to add an API call, auth, events, or tests with FakeTransport in a project that depends on it.
---

# KnockKnock (PHP HTTP client) — guide for LLMs

PHP >= 8.2, ext-curl, ext-json. Namespace `KnockKnock\`. Install: `composer require andy87/knock-knock`
(or `require 'autoload.php'`). Full docs: README.md. Do not invent methods: everything usable is listed here.

## Mental model

- `Client` is **immutable**. `with*()` and `on()` return a NEW client; ignoring the return value does nothing.
- Base URL is fixed per client; verb paths are **relative**. Absolute URL as a path throws.
- Network failure → exception. HTTP 4xx/5xx → normal `Response` unless you call `->throw()`.
- `Request` / `Response` are readonly value objects (no setters).

## API cheat sheet

```php
use KnockKnock\{Client, Event};
use KnockKnock\Http\{ContentType, Method, Request, Response};
use KnockKnock\Exception\{HttpException, TransportException, InvalidRequestException, KnockKnockException};
use KnockKnock\Transport\{CurlTransport, FakeTransport, Transport};

$api = (new Client('https://api.example.com'))   // scheme optional → https
    ->withBearer($token)                          // or withBasic($user, $pass)
    ->withHeaders(['Accept' => 'application/json'])
    ->withContentType(ContentType::Json)          // Json (default) | Form | Text | Xml | Html
    ->withTimeout(15, 5)                          // total s, connect s (defaults 30 / 10)
    ->withSslVerification(true)                   // default true
    ->withRedirects(false)                        // default false
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Id', $id));

$api->get($path, array $query = [], array $headers = []);       // also head, options, trace
$api->post($path, array|string $data = [], array $headers = []); // also put, patch, delete
$api->request(Method::Post, $path, $data, $headers);
```

Data placement: Get/Head/Options/Trace → query string; Post/Put/Patch/Delete → body
(array encoded per content type; string sent as-is; empty → no body).

Response: `->status`, `->body`, `->headers` (lower-case keys), `->header('Name')`, `->json()` (array),
`->successful()` (2xx), `->failed()` (>=400), `->throw()` (HttpException on 4xx/5xx, returns self),
`->request`, `->info`.

Events: `BeforeSend(Request)` may return a replacement Request; `AfterSend(Response)` may return a replacement
Response; `Failure(TransportException, Request)` is observe-only. Multiple listeners allowed. Return `null` to keep.

Raw cURL: in a `BeforeSend` listener, `$request->withCurlOptions([CURLOPT_PROXY => '...'])`.

## Default recipe for an API call

```php
try {
    $data = $api->get('/items', ['page' => 1])->throw()->json();
} catch (HttpException $e) {
    // $e->response->status / ->body
} catch (TransportException $e) {
    // DNS, timeout, refused; $e->getCode() is the cURL errno
}
```

Build one configured client once (container/service), reuse it; derive variants with `with*`.
Reusing a client shares one cURL handle → keep-alive for free.

## Testing (no network)

```php
$fake = (new FakeTransport())->queueJson(['id' => 1])->queue(500, 'oops');
$api  = new Client('https://api.test', $fake);       // 2nd constructor arg = transport
$api->get('/x')->json();                              // ['id' => 1]
$fake->sent[0]->url;                                  // inspect sent Request
```

Responses return in queue order; empty queue throws `TransportException`. Accept a `Client` (or `Transport`)
via constructor injection in the user's code so tests can pass a fake.

## Pitfalls — check before finishing

1. `$api->withBearer($t);` without assignment is a no-op. Assign the result.
2. Don't pass full URLs to verbs; put the host in the `Client` constructor.
3. Don't expect exceptions for 404/500; call `->throw()` or check `->status`.
4. `post($path, $array)` sends JSON by default; for forms use `->withContentType(ContentType::Form)`.
5. Redirects are NOT followed unless `->withRedirects()`.
6. Never disable SSL verification outside local dev. Never hard-code tokens; read from env/config.
7. `Response::json()` throws `InvalidRequestException` on non-JSON/scalar bodies — don't call it on `HEAD` or 204.
8. Header names/values with CR/LF/NUL or invalid names throw `InvalidRequestException` at construction.
9. Array bodies with `ContentType::Text/Xml/Html` are unsupported: pass a pre-encoded string.
10. Catch `KnockKnockException` to handle every library error at once.

## Not supported (don't promise it)

Multipart/file uploads (pass a string body + `Content-Type` header, or raw `CURLOPT_POSTFIELDS` via
`withCurlOptions`), async/parallel requests, built-in retries, cookies jar (use `CURLOPT_COOKIEJAR` via raw options),
PSR-7/PSR-18 interfaces.
