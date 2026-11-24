# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [日本語](README.ja.md) · [Español](README.es.md) · [简体中文](README.zh-CN.md) · [Português (BR)](README.pt-BR.md) · **한국어**

ext-cURL 기반의 작고, 불변이며, 의존성이 없는 PHP HTTP 클라이언트입니다.
[andy87/knock-knock](https://github.com/andy87/knock-knock)을 깔끔하게 다시 작성한 것으로, 아이디어(인증, 이벤트, 가짜 응답을 갖춘
최소한의 cURL 퍼사드)는 그대로 두고 PHP 8.2+에 맞게 구조를 재정비했습니다.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## 목차

- [설치](#설치)
- [빠른 시작](#빠른-시작)
- [요청 보내기](#요청-보내기)
- [옵션 레퍼런스](#옵션-레퍼런스)
- [응답](#응답)
- [오류 처리](#오류-처리)
- [이벤트](#이벤트)
- [예제](#예제)
- [네트워크 없이 테스트하기](#네트워크-없이-테스트하기)
- [고급](#고급)

## 설치

```
composer require andy87/knock-knock
```

Composer 없이: `require '/path/to/knock-knock/autoload.php';`

PHP >= 8.2, ext-curl, ext-json이 필요합니다.

## 빠른 시작

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // 스킴은 생략 가능(기본값 https)

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // 원본 본문
print_r($response->json());      // 디코딩된 JSON(배열)
```

기본 URL은 클라이언트마다 고정됩니다. 경로는 항상 이에 대한 상대 경로이며, 절대 URL을 경로로 넘기면
`InvalidRequestException`이 발생합니다.

## 요청 보내기

모든 메서드는 `Response`를 반환합니다. 마지막 인자는 해당 요청에만 적용되는 헤더 배열(선택)입니다.

| 메서드 | 시그니처 | `$data`가 가는 곳 |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | 쿼리 문자열 |
| `head` | `head($path, array $query = [], array $headers = [])` | 쿼리 문자열 |
| `options` | `options($path, array $query = [], array $headers = [])` | 쿼리 문자열 |
| `trace` | `trace($path, array $query = [], array $headers = [])` | 쿼리 문자열 |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | 본문 |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | 본문 |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | 본문 |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | 본문 |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | 메서드에 따름 |

**쿼리 문자열.** 배열은 RFC 3986으로 인코딩되고(`a b` → `a%20b`), 경로에 이미 쿼리가 있는지에 따라 `?` 또는 `&`로 붙습니다.
문자열은 그대로 덧붙여집니다(앞의 `?`/`&`는 제거).

**본문.** 배열은 클라이언트의 content type에 따라 인코딩됩니다(기본값 JSON). 문자열은 `"0"`을 포함해 그대로 전송됩니다.
빈 배열/문자열은 본문도 `Content-Type`도 보내지 않습니다.
`Content-Type` 헤더는 직접 지정하지 않으면 자동으로 추가됩니다.

```php
$client->post('/users', ['name' => 'Ann']);                     // JSON 본문
$client->post('/raw', '{"already":"encoded"}');                  // 그대로 전송
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // 요청별 헤더
$client->request(Method::Put, '/things/1', ['a' => 1]);         // 범용 형태
```

## 옵션 레퍼런스

### 생성자

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // 기본값: new CurlTransport()
    array $headers = [],                // 기본 헤더
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // 초, 요청 전체
    float $connectTimeout = 10.0,       // 초, 연결 단계
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // 보통 on()으로 설정
);
```

모든 것을 생성자에 넘기기보다 체이닝 방식의 `with*` 메서드를 권장합니다.

### 체이닝 설정

각 호출은 **새로운** 클라이언트를 반환하므로(원본은 바뀌지 않음) 기본 클라이언트를 안전하게 공유할 수 있습니다.
트랜스포트(와 그 cURL 핸들)는 복사본끼리 공유되어 연결이 유지됩니다.

| 메서드 | 효과 | 기본값 |
|---|---|---|
| `withHeaders(array $headers)` | 기본 헤더 병합. 이름은 대소문자 구분 없음, 나중 값이 우선 | 없음 |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | 배열 본문의 인코딩 방식 | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | 전체 및 (선택) 연결 타임아웃, 초 단위 | 30 / 10 |
| `withSslVerification(bool $verify)` | 인증서 검증(peer 및 host, 레벨 2) | `true` |
| `withRedirects(bool $follow = true)` | `3xx` 리디렉션 따라가기 | 꺼짐 |
| `on(Event $event, callable $listener)` | 이벤트 리스너 추가 | – |

### `ContentType` 값

| 값 | 헤더 | 배열 인코딩 |
|---|---|---|
| `Json` | `application/json` | `json_encode`(유니코드와 슬래시는 이스케이프하지 않음) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query`(RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | 배열 미지원 — 문자열을 전달하세요 |

### `Method` 값

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace`는 데이터를 쿼리 문자열에 넣습니다.

## 응답

`KnockKnock\Http\Response`는 불변입니다.

| 멤버 | 설명 |
|---|---|
| `status` | HTTP 상태 코드(`int`) |
| `body` | 원본 본문(`string`, `HEAD`는 빈 값) |
| `headers` | 모든 헤더, 이름은 소문자. 반복된 헤더는 `, `로 연결 |
| `header($name)` | 대소문자 구분 없이 헤더 하나 조회, 없으면 `null` |
| `json()` | 본문을 배열로 디코딩. JSON 객체/배열이 아니면 `InvalidRequestException` |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | 4xx/5xx면 `HttpException`, 아니면 응답 자신을 반환(체이닝 가능) |
| `request` | 실제로 전송된 `Request`(`BeforeSend` 적용 후) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

3xx는 오류가 아닙니다. 리디렉션을 끄면 그대로 응답을 받고 `header('Location')`을 읽을 수 있습니다.

## 오류 처리

라이브러리의 모든 예외는 `KnockKnock\Exception\KnockKnockException`(`RuntimeException`)을 상속합니다.

| 예외 | 발생 시점 |
|---|---|
| `InvalidRequestException` | 잘못된 기본 URL, 경로로 쓴 절대 URL, 잘못된 헤더 이름/값(CR/LF/NUL), 인코딩할 수 없는 데이터, `json()`에서의 비 JSON 본문 |
| `TransportException` | 응답 없음: DNS, 연결 거부, 타임아웃, TLS 실패. `getCode()`는 cURL errno |
| `HttpException` | 4xx/5xx에서 `->throw()`로만 발생. 응답은 `$e->response`에 있음 |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // 재시도/백오프는 여기서
}
```

## 이벤트

`on()`으로 이벤트마다 원하는 만큼 리스너를 등록할 수 있으며, 등록 순서대로 실행됩니다.

| 이벤트 | 리스너가 받는 값 | 반환값 |
|---|---|---|
| `Event::BeforeSend` | `Request` | 대체할 `Request`(선택, `null`이면 유지) |
| `Event::AfterSend` | `Response` | 대체할 `Response`(선택) |
| `Event::Failure` | `TransportException`, `Request` | 무시됨. 이후 예외가 다시 던져짐 |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request`도 불변입니다: `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

## 예제

### 인증이 있는 JSON API와 오류 처리

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

### Basic 인증과 폼 인코딩

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### 헤더 읽기, 리디렉션, 진단 정보

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, 기본적으로 따라가지 않음
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // 최종 페이지까지 따라감
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### 자체 서명 인증서를 쓰는 로컬 개발

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // 운영 환경에서는 절대 금지
```

### 네트워크 오류와 5xx에 대한 간단한 재시도

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

### 여러 서비스에서 하나의 기본 클라이언트 재사용

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## 네트워크 없이 테스트하기

`FakeTransport`를 전달하면 네트워크를 전혀 사용하지 않습니다. 응답은 큐 순서대로 반환되며,
큐가 비어 있으면 `TransportException`이 발생합니다.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // 상태, 본문, 헤더
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // 전송된 모든 요청 검사
$fake->sent[0]->header('authorization');
```

저장소의 자체 테스트 스위트도 PHP 내장 서버(`tests/Support/router.php`)를 상대로 실제 왕복 요청을 수행합니다.
통합 테스트의 좋은 본보기입니다.

## 고급

### 원시 cURL 옵션

`BeforeSend` 리스너에서 `Request::withCurlOptions()`를 사용하면 한 요청에 대해 임의의 `CURLOPT_*`를 설정할 수 있습니다.
원시 옵션은 클라이언트 설정에서 파생된 옵션보다 우선합니다.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // gzip/deflate를 수락하고 디코딩
]));
```

트랜스포트의 모든 요청에 적용할 옵션은 `CurlTransport`에 직접 전달하세요:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(타임아웃과 SSL 플래그처럼 클라이언트에서 파생된 옵션은 여전히 이 기본값보다 우선합니다.)

### 사용자 정의 트랜스포트

`KnockKnock\Transport\Transport`(메서드는 `send(Request): Response` 하나)를 구현하면 다른 HTTP 스택 사용,
캐시 추가, 트래픽 기록/재생 등이 가능합니다. 네트워크 실패에는 `TransportException`을 던지세요.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### 참고

- 트랜스포트마다 cURL 핸들 하나를 재사용하며 요청 사이에 초기화됩니다. 옵션이 다음 요청으로 새지 않습니다.
- 하나의 핸들을 여러 Fiber/스레드에서 동시에 쓰는 용도는 고려되지 않았습니다.
- 헤더 값은 생성 시점에 검증됩니다. 빈 값은 빈 헤더(`Name;`)로 전송됩니다.

프로젝트 구조, 원본과의 비교, 테스트 실행 방법은 [영문 README](../../README.md)를 참고하세요.

라이선스: CC BY-SA 4.0(원본과 동일).
