# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · **Deutsch** · [Français](README.fr.md) · [日本語](README.ja.md) · [Español](README.es.md) · [简体中文](README.zh-CN.md) · [Português (BR)](README.pt-BR.md) · [한국어](README.ko.md)

Ein kleiner, unveränderlicher, abhängigkeitsfreier HTTP-Client für PHP auf Basis von ext-cURL. Eine saubere Neufassung von
[andy87/knock-knock](https://github.com/andy87/knock-knock): dieselbe Idee (minimale cURL-Fassade mit
Authentifizierung, Events und Fake-Antworten), neu strukturiert für PHP 8.2+.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## Inhalt

- [Installation](#installation)
- [Schnellstart](#schnellstart)
- [Anfragen senden](#anfragen-senden)
- [Optionen](#optionen)
- [Antworten](#antworten)
- [Fehlerbehandlung](#fehlerbehandlung)
- [Events](#events)
- [Beispiele](#beispiele)
- [Tests ohne Netzwerk](#tests-ohne-netzwerk)
- [Fortgeschritten](#fortgeschritten)

## Installation

```
composer require andy87/knock-knock
```

Ohne Composer: `require '/path/to/knock-knock/autoload.php';`

Benötigt PHP >= 8.2, ext-curl, ext-json.

## Schnellstart

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // Schema optional, Standard ist https

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // Rohinhalt
print_r($response->json());      // dekodiertes JSON (Array)
```

Die Basis-URL ist pro Client fest. Pfade sind immer relativ dazu; eine absolute URL als Pfad
wirft `InvalidRequestException`.

## Anfragen senden

Jede Methode liefert eine `Response`. Das letzte Argument ist ein optionales Array mit Headern für diese Anfrage.

| Methode | Signatur | `$data` landet in |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | Query-String |
| `head` | `head($path, array $query = [], array $headers = [])` | Query-String |
| `options` | `options($path, array $query = [], array $headers = [])` | Query-String |
| `trace` | `trace($path, array $query = [], array $headers = [])` | Query-String |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | Body |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | Body |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | Body |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | Body |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | je nach Methode |

**Query-Strings.** Arrays werden nach RFC 3986 kodiert (`a b` → `a%20b`) und mit `?` oder `&` angehängt,
je nachdem, ob der Pfad schon eine Query hat. Ein String wird unverändert angehängt (führendes `?`/`&` entfällt).

**Bodies.** Ein Array wird gemäß Content-Type des Clients kodiert (Standard JSON). Ein String wird unverändert
gesendet, auch `"0"`. Leeres Array/leerer String sendet weder Body noch `Content-Type`.
Ein `Content-Type`-Header wird automatisch ergänzt, sofern Sie keinen eigenen setzen.

```php
$client->post('/users', ['name' => 'Ann']);                     // JSON-Body
$client->post('/raw', '{"already":"encoded"}');                  // unverändert
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // Header pro Anfrage
$client->request(Method::Put, '/things/1', ['a' => 1]);         // allgemeine Form
```

## Optionen

### Konstruktor

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // Standard: new CurlTransport()
    array $headers = [],                // Standard-Header
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // Sekunden, gesamte Anfrage
    float $connectTimeout = 10.0,       // Sekunden, Verbindungsaufbau
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // normalerweise über on()
);
```

Bevorzugen Sie die fließenden `with*`-Methoden statt alles dem Konstruktor zu übergeben.

### Fließende Konfiguration

Jeder Aufruf liefert einen **neuen** Client (das Original bleibt unverändert); ein Basis-Client lässt sich also sicher teilen.
Der Transport (samt cURL-Handle) wird zwischen Kopien geteilt, Verbindungen bleiben offen.

| Methode | Wirkung | Standard |
|---|---|---|
| `withHeaders(array $headers)` | Standard-Header zusammenführen; Namen ohne Groß-/Kleinschreibung, der spätere gewinnt | keine |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | Kodierung von Array-Bodies | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | Gesamt- und (optional) Verbindungs-Timeout in Sekunden | 30 / 10 |
| `withSslVerification(bool $verify)` | Zertifikat prüfen (Peer und Host, Stufe 2) | `true` |
| `withRedirects(bool $follow = true)` | `3xx`-Weiterleitungen folgen | aus |
| `on(Event $event, callable $listener)` | Event-Listener hinzufügen | – |

### `ContentType`-Werte

| Wert | Header | Array-Kodierung |
|---|---|---|
| `Json` | `application/json` | `json_encode` (Unicode und Slashes unmaskiert) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query` (RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | Arrays nicht unterstützt — String übergeben |

### `Method`-Werte

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace` legen Daten in den Query-String.

## Antworten

`KnockKnock\Http\Response` ist unveränderlich.

| Mitglied | Beschreibung |
|---|---|
| `status` | HTTP-Statuscode (`int`) |
| `body` | Roher Body (`string`; leer bei `HEAD`) |
| `headers` | Alle Header, Namen kleingeschrieben; wiederholte Header mit `, ` verbunden |
| `header($name)` | Ein Header, unabhängig von der Schreibweise, oder `null` |
| `json()` | Body als Array; wirft `InvalidRequestException`, wenn kein JSON-Objekt/-Array |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | Wirft `HttpException` bei 4xx/5xx, sonst die Antwort (verkettbar) |
| `request` | Der tatsächlich gesendete `Request` (nach `BeforeSend`) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

Eine 3xx-Antwort ist kein Fehler; bei deaktivierten Weiterleitungen erhalten Sie sie und können `header('Location')` lesen.

## Fehlerbehandlung

Alle Ausnahmen der Bibliothek erben von `KnockKnock\Exception\KnockKnockException` (eine `RuntimeException`).

| Ausnahme | Wann |
|---|---|
| `InvalidRequestException` | Ungültige Basis-URL, absolute URL als Pfad, ungültiger Header-Name/-Wert (CR/LF/NUL), nicht kodierbare Daten, kein JSON bei `json()` |
| `TransportException` | Keine Antwort: DNS, Verbindung abgelehnt, Timeout, TLS-Fehler. `getCode()` ist die cURL-errno |
| `HttpException` | Nur durch `->throw()` bei 4xx/5xx; die Antwort steht in `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // hier Retry/Backoff
}
```

## Events

Beliebig viele Listener pro Event über `on()`. Sie laufen in der Reihenfolge der Registrierung.

| Event | Listener erhält | Rückgabewert |
|---|---|---|
| `Event::BeforeSend` | `Request` | optionaler Ersatz-`Request` (`null` = unverändert) |
| `Event::AfterSend` | `Response` | optionale Ersatz-`Response` |
| `Event::Failure` | `TransportException`, `Request` | ignoriert; die Ausnahme wird danach erneut geworfen |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` ist ebenfalls unveränderlich: `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

## Beispiele

### Authentifizierte JSON-API mit Fehlerbehandlung

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

### Basic-Auth und Formular-Kodierung

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### Header, Weiterleitungen und Diagnose

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, standardmäßig nicht gefolgt
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // folgt bis zur Zielseite
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### Lokale Entwicklung mit selbstsigniertem Zertifikat

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // niemals in Produktion
```

### Einfacher Retry bei Netzwerkfehlern und 5xx

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

### Ein Basis-Client für mehrere Dienste

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## Tests ohne Netzwerk

Mit einem `FakeTransport` wird das Netzwerk nicht berührt. Antworten kommen in Reihenfolge der Warteschlange;
eine leere Warteschlange wirft `TransportException`.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // Status, Body, Header
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // jede gesendete Anfrage prüfen
$fake->sent[0]->header('authorization');
```

Die Tests des Repositorys machen außerdem echte Aufrufe gegen den eingebauten PHP-Server
(`tests/Support/router.php`) — eine gute Vorlage für Integrationstests.

## Fortgeschritten

### Rohe cURL-Optionen

Nutzen Sie `Request::withCurlOptions()` in einem `BeforeSend`-Listener, um beliebige `CURLOPT_*` für eine Anfrage zu setzen.
Rohe Optionen haben Vorrang vor den aus den Client-Einstellungen abgeleiteten.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // gzip/deflate akzeptieren und dekodieren
]));
```

Optionen für alle Anfragen eines Transports übergeben Sie direkt an `CurlTransport`:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(Vom Client abgeleitete Optionen wie Timeouts und SSL-Flags haben weiterhin Vorrang vor diesen Standardwerten.)

### Eigener Transport

Implementieren Sie `KnockKnock\Transport\Transport` (eine Methode, `send(Request): Response`), um einen anderen
HTTP-Stack, Caching oder Aufzeichnen/Wiedergeben von Verkehr zu nutzen. Werfen Sie bei Netzwerkfehlern `TransportException`.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### Hinweise

- Pro Transport wird ein cURL-Handle wiederverwendet und zwischen Anfragen zurückgesetzt; Optionen »lecken« nicht.
- Ein Client ist nicht für die gleichzeitige Nutzung eines Handles durch mehrere Fibers/Threads gedacht.
- Header-Werte werden bei der Erzeugung geprüft; ein leerer Wert sendet einen leeren Header (`Name;`).

Projektstruktur, Vergleich mit dem Original und Testlauf: siehe das [englische README](../../README.md).

Lizenz: CC BY-SA 4.0 (wie das Original).
