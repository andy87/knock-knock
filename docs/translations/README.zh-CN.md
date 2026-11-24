# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [日本語](README.ja.md) · [Español](README.es.md) · **简体中文** · [Português (BR)](README.pt-BR.md) · [한국어](README.ko.md)

一个基于 ext-cURL 的小巧、不可变、零依赖的 PHP HTTP 客户端。是对
[andy87/knock-knock](https://github.com/andy87/knock-knock) 的整洁重写:理念不变(一个带认证、事件和伪造响应的极简 cURL 门面),
但针对 PHP 8.2+ 重新组织了结构。

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## 目录

- [安装](#安装)
- [快速开始](#快速开始)
- [发送请求](#发送请求)
- [选项参考](#选项参考)
- [响应](#响应)
- [错误处理](#错误处理)
- [事件](#事件)
- [示例](#示例)
- [无网络测试](#无网络测试)
- [进阶](#进阶)

## 安装

```
composer require andy87/knock-knock
```

不使用 Composer:`require '/path/to/knock-knock/autoload.php';`

需要 PHP >= 8.2、ext-curl、ext-json。

## 快速开始

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // 协议可省略,默认 https

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // 原始响应体
print_r($response->json());      // 解码后的 JSON(数组)
```

基础 URL 在每个客户端中是固定的。路径始终相对于它;把绝对 URL 当作路径传入会抛出
`InvalidRequestException`。

## 发送请求

每个方法都返回一个 `Response`。最后一个参数是可选的、仅用于该请求的请求头数组。

| 方法 | 签名 | `$data` 去向 |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | 查询字符串 |
| `head` | `head($path, array $query = [], array $headers = [])` | 查询字符串 |
| `options` | `options($path, array $query = [], array $headers = [])` | 查询字符串 |
| `trace` | `trace($path, array $query = [], array $headers = [])` | 查询字符串 |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | 请求体 |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | 请求体 |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | 请求体 |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | 请求体 |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | 取决于方法 |

**查询字符串。** 数组按 RFC 3986 编码(`a b` → `a%20b`),并根据路径是否已有查询部分用 `?` 或 `&` 拼接。
字符串按原样追加(去掉开头的 `?`/`&`)。

**请求体。** 数组按客户端的 content type 编码(默认 JSON)。字符串原样发送,包括 `"0"`。
空数组/空字符串既不发送请求体,也不发送 `Content-Type`。
除非你自己设置,否则会自动添加 `Content-Type` 请求头。

```php
$client->post('/users', ['name' => 'Ann']);                     // JSON 请求体
$client->post('/raw', '{"already":"encoded"}');                  // 原样发送
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // 单次请求的请求头
$client->request(Method::Put, '/things/1', ['a' => 1]);         // 通用形式
```

## 选项参考

### 构造函数

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // 默认:new CurlTransport()
    array $headers = [],                // 默认请求头
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // 秒,整个请求
    float $connectTimeout = 10.0,       // 秒,连接阶段
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // 通常通过 on() 设置
);
```

建议使用链式的 `with*` 方法,而不是把所有参数都传给构造函数。

### 链式配置

每次调用都会返回一个**新的**客户端(原客户端保持不变),因此基础客户端可以安全共享。
传输层(及其 cURL 句柄)在各副本之间共享,连接得以保持。

| 方法 | 作用 | 默认值 |
|---|---|---|
| `withHeaders(array $headers)` | 合并默认请求头;名称不区分大小写,后者覆盖前者 | 无 |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | 数组请求体的编码方式 | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | 总超时和(可选)连接超时,单位秒 | 30 / 10 |
| `withSslVerification(bool $verify)` | 验证证书(peer 与 host,级别 2) | `true` |
| `withRedirects(bool $follow = true)` | 跟随 `3xx` 重定向 | 关闭 |
| `on(Event $event, callable $listener)` | 添加事件监听器 | – |

### `ContentType` 取值

| 取值 | 请求头 | 数组编码 |
|---|---|---|
| `Json` | `application/json` | `json_encode`(不转义 Unicode 和斜杠) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query`(RFC 3986) |
| `Text`、`Xml`、`Html` | `text/plain`、`application/xml`、`text/html` | 不支持数组 — 请传入字符串 |

### `Method` 取值

`Get Post Put Patch Delete Head Options Trace`。`Get/Head/Options/Trace` 把数据放入查询字符串。

## 响应

`KnockKnock\Http\Response` 是不可变的。

| 成员 | 说明 |
|---|---|
| `status` | HTTP 状态码(`int`) |
| `body` | 原始响应体(`string`;`HEAD` 时为空) |
| `headers` | 所有响应头,名称为小写;重复的头用 `, ` 连接 |
| `header($name)` | 获取单个响应头(不区分大小写),不存在则为 `null` |
| `json()` | 将响应体解码为数组;若不是 JSON 对象/数组则抛出 `InvalidRequestException` |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | 4xx/5xx 时抛出 `HttpException`,否则返回响应本身(可链式调用) |
| `request` | 实际发送的 `Request`(经过 `BeforeSend` 之后) |
| `info` | `total_time`、`primary_ip`、`size_download`、`effective_url` |

3xx 不是错误;关闭重定向时你会收到该响应,并可读取 `header('Location')`。

## 错误处理

库中所有异常都继承自 `KnockKnock\Exception\KnockKnockException`(一个 `RuntimeException`)。

| 异常 | 触发时机 |
|---|---|
| `InvalidRequestException` | 基础 URL 无效、把绝对 URL 当路径、请求头名称/值无效(CR/LF/NUL)、数据无法编码、`json()` 遇到非 JSON 响应体 |
| `TransportException` | 没有响应:DNS、连接被拒、超时、TLS 失败。`getCode()` 为 cURL 的 errno |
| `HttpException` | 仅由 4xx/5xx 上的 `->throw()` 抛出;响应在 `$e->response` 中 |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // 重试/退避放在这里
}
```

## 事件

用 `on()` 为每个事件注册任意数量的监听器,按注册顺序执行。

| 事件 | 监听器接收 | 返回值 |
|---|---|---|
| `Event::BeforeSend` | `Request` | 可选的替换 `Request`(返回 `null` 表示保持不变) |
| `Event::AfterSend` | `Response` | 可选的替换 `Response` |
| `Event::Failure` | `TransportException`、`Request` | 被忽略;之后异常会被重新抛出 |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` 同样是不可变的:`withHeader()`、`withHeaders()`、`withBody()`、`withCurlOptions()`。

## 示例

### 带认证的 JSON API 与错误处理

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

### Basic 认证与表单编码

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### 读取响应头、重定向与诊断信息

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301,默认不跟随
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // 跟随到最终页面
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### 使用自签名证书的本地开发

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // 切勿用于生产环境
```

### 针对网络错误和 5xx 的简单重试

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

### 多个服务共用一个基础客户端

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## 无网络测试

传入 `FakeTransport`,就不会接触网络。响应按队列顺序返回;队列为空时会抛出 `TransportException`。

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // 状态码、响应体、响应头
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // 检查每个已发送的请求
$fake->sent[0]->header('authorization');
```

仓库自带的测试套件还会对 PHP 内置服务器(`tests/Support/router.php`)做真实的往返请求,
是编写集成测试的好模板。

## 进阶

### 原始 cURL 选项

在 `BeforeSend` 监听器中使用 `Request::withCurlOptions()`,可为单个请求设置任意 `CURLOPT_*`。
原始选项优先于由客户端设置派生出的选项。

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // 接受并自动解码 gzip/deflate
]));
```

对传输层所有请求都生效的选项,请直接传给 `CurlTransport`:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(由客户端派生的选项,如超时和 SSL 标志,仍然优先于这些默认值。)

### 自定义传输层

实现 `KnockKnock\Transport\Transport`(只有一个方法 `send(Request): Response`),即可使用其他 HTTP 栈、
添加缓存,或录制/回放流量。网络故障时请抛出 `TransportException`。

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### 说明

- 每个传输层复用一个 cURL 句柄,并在请求之间重置;选项不会泄漏到下一次请求。
- 客户端不适用于多个 Fiber/线程并发共用同一个句柄的场景。
- 请求头的值在构造时校验;空值会发送一个空请求头(`Name;`)。

项目结构、与原版的对比以及运行测试的方法,请参阅[英文 README](../../README.md)。

许可证:CC BY-SA 4.0(与原项目相同)。
