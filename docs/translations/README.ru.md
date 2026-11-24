# KnockKnock

[English](../../README.md) · **Русский** · [Deutsch](README.de.md) · [Français](README.fr.md) · [日本語](README.ja.md) · [Español](README.es.md) · [简体中文](README.zh-CN.md) · [Português (BR)](README.pt-BR.md) · [한국어](README.ko.md)

Небольшой неизменяемый HTTP-клиент для PHP на основе ext-cURL, без зависимостей. Чистая переработка
[andy87/knock-knock](https://github.com/andy87/knock-knock): та же идея (минималистичный фасад cURL с
авторизацией, событиями и фейковыми ответами), но со структурой под PHP 8.2+.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## Содержание

- [Установка](#установка)
- [Быстрый старт](#быстрый-старт)
- [Отправка запросов](#отправка-запросов)
- [Справочник по опциям](#справочник-по-опциям)
- [Ответы](#ответы)
- [Обработка ошибок](#обработка-ошибок)
- [События](#события)
- [Примеры](#примеры)
- [Тестирование без сети](#тестирование-без-сети)
- [Продвинутое](#продвинутое)

## Установка

```
composer require andy87/knock-knock
```

Без Composer: `require '/path/to/knock-knock/autoload.php';`

Требуется PHP >= 8.2, ext-curl, ext-json.

## Быстрый старт

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // схема необязательна, по умолчанию https

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // сырое тело
print_r($response->json());      // декодированный JSON (массив)
```

Базовый URL фиксируется для клиента. Пути всегда относительны ему; абсолютный URL в качестве пути
вызывает `InvalidRequestException`.

## Отправка запросов

Каждый метод возвращает `Response`. Последний аргумент — необязательный массив заголовков для одного запроса.

| Метод | Сигнатура | `$data` попадает в |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | строку запроса |
| `head` | `head($path, array $query = [], array $headers = [])` | строку запроса |
| `options` | `options($path, array $query = [], array $headers = [])` | строку запроса |
| `trace` | `trace($path, array $query = [], array $headers = [])` | строку запроса |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | тело |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | тело |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | тело |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | тело |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | зависит от метода |

**Строка запроса.** Массивы кодируются по RFC 3986 (`a b` → `a%20b`) и добавляются через `?` или `&`
в зависимости от наличия query в пути. Строка добавляется как есть (ведущие `?`/`&` убираются).

**Тело.** Массив кодируется согласно content type клиента (по умолчанию JSON). Строка отправляется без
изменений, включая `"0"`. Пустой массив/строка — ни тела, ни заголовка `Content-Type`.
Заголовок `Content-Type` добавляется автоматически, если вы не задали свой.

```php
$client->post('/users', ['name' => 'Ann']);                     // JSON-тело
$client->post('/raw', '{"already":"encoded"}');                  // как есть
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // заголовок для запроса
$client->request(Method::Put, '/things/1', ['a' => 1]);         // общая форма
```

## Справочник по опциям

### Конструктор

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // по умолчанию: new CurlTransport()
    array $headers = [],                // заголовки по умолчанию
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // секунды, весь запрос
    float $connectTimeout = 10.0,       // секунды, установка соединения
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // обычно через on()
);
```

Предпочитайте цепочку методов `with*` вместо передачи всего в конструктор.

### Конфигурация через цепочку

Каждый вызов возвращает **новый** клиент (исходный не меняется), поэтому базовый клиент безопасно переиспользовать.
Транспорт (и его cURL-хэндл) общий для копий, соединения остаются открытыми.

| Метод | Действие | По умолчанию |
|---|---|---|
| `withHeaders(array $headers)` | Добавить заголовки по умолчанию; имена регистронезависимы, побеждает последнее | нет |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | Кодирование массивов в теле | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | Общий и (опц.) таймаут соединения, секунды | 30 / 10 |
| `withSslVerification(bool $verify)` | Проверка сертификата (peer и host, уровень 2) | `true` |
| `withRedirects(bool $follow = true)` | Следовать редиректам `3xx` | выкл. |
| `on(Event $event, callable $listener)` | Добавить обработчик события | – |

### Значения `ContentType`

| Значение | Заголовок | Кодирование массива |
|---|---|---|
| `Json` | `application/json` | `json_encode` (юникод и слэши не экранируются) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query` (RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | массивы не поддерживаются — передайте строку |

### Значения `Method`

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace` передают данные в строке запроса.

## Ответы

`KnockKnock\Http\Response` неизменяем.

| Член | Описание |
|---|---|
| `status` | HTTP-код (`int`) |
| `body` | Сырое тело (`string`; пусто для `HEAD`) |
| `headers` | Все заголовки, имена в нижнем регистре; повторяющиеся объединяются через `, ` |
| `header($name)` | Один заголовок без учёта регистра или `null` |
| `json()` | Тело как массив; `InvalidRequestException`, если это не JSON-объект/массив |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | `HttpException` при 4xx/5xx, иначе возвращает ответ (для цепочек) |
| `request` | Точный отправленный `Request` (после `BeforeSend`) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

3xx — не ошибка; при выключенных редиректах вы получите ответ и сможете прочитать `header('Location')`.

## Обработка ошибок

Все исключения библиотеки наследуют `KnockKnock\Exception\KnockKnockException` (`RuntimeException`).

| Исключение | Когда |
|---|---|
| `InvalidRequestException` | Неверный базовый URL, абсолютный URL как путь, неверное имя/значение заголовка (CR/LF/NUL), данные нельзя закодировать, не-JSON в `json()` |
| `TransportException` | Ответа нет: DNS, отказ соединения, таймаут, ошибка TLS. `getCode()` — errno cURL |
| `HttpException` | Только из `->throw()` при 4xx/5xx; ответ в `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // здесь повторы/backoff
}
```

## События

Регистрируйте любое число обработчиков через `on()`. Они выполняются в порядке регистрации.

| Событие | Обработчик получает | Возвращаемое значение |
|---|---|---|
| `Event::BeforeSend` | `Request` | необязательный новый `Request` (`null` — оставить прежний) |
| `Event::AfterSend` | `Response` | необязательный новый `Response` |
| `Event::Failure` | `TransportException`, `Request` | игнорируется; исключение пробрасывается дальше |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` тоже неизменяем: `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

## Примеры

### API с авторизацией, JSON и обработкой ошибок

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

### Basic-авторизация и form-кодирование

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### Заголовки, редиректы и диагностика

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, по умолчанию не выполняется
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // идёт до конечной страницы
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### Локальная разработка с самоподписанным сертификатом

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // никогда в продакшене
```

### Простой повтор при сетевых ошибках и 5xx

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

### Один базовый клиент для нескольких сервисов

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## Тестирование без сети

Передайте `FakeTransport` — сеть не используется. Ответы выдаются в порядке очереди;
пустая очередь вызывает `TransportException`.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // статус, тело, заголовки
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // каждый отправленный запрос
$fake->sent[0]->header('authorization');
```

Собственный набор тестов репозитория также делает реальные запросы к встроенному серверу PHP
(`tests/Support/router.php`) — хороший шаблон для интеграционных тестов.

## Продвинутое

### Сырые опции cURL

Используйте `Request::withCurlOptions()` в обработчике `BeforeSend`, чтобы задать любой `CURLOPT_*` для одного запроса.
Сырые опции имеют приоритет над производными от настроек клиента.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // gzip/deflate с автодекодированием
]));
```

Опции для всех запросов транспорта передайте в `CurlTransport`:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(Опции, производные от клиента — таймауты и SSL — по-прежнему приоритетнее этих значений.)

### Свой транспорт

Реализуйте `KnockKnock\Transport\Transport` (один метод `send(Request): Response`), чтобы использовать другой
HTTP-стек, кэш или запись/воспроизведение трафика. При сетевых сбоях бросайте `TransportException`.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### Примечания

- На транспорт используется один cURL-хэндл, сбрасываемый между запросами; опции не «протекают».
- Клиент не рассчитан на одновременное использование одного хэндла из разных fiber/потоков.
- Значения заголовков проверяются при создании; пустое значение отправляет пустой заголовок (`Name;`).

Структура проекта, сравнение с оригиналом и запуск тестов — в [английском README](../../README.md).

Лицензия: CC BY-SA 4.0 (как у оригинала).
