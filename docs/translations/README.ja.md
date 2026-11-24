# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · **日本語** · [Español](README.es.md) · [简体中文](README.zh-CN.md) · [Português (BR)](README.pt-BR.md) · [한국어](README.ko.md)

ext-cURL をベースにした、小さく・イミュータブルで・依存ゼロの PHP HTTP クライアントです。
[andy87/knock-knock](https://github.com/andy87/knock-knock) をすっきり書き直したもので、
コンセプト(認証・イベント・フェイクレスポンスを備えた最小限の cURL ファサード)はそのままに、PHP 8.2+ 向けに再構成しています。

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## 目次

- [インストール](#インストール)
- [クイックスタート](#クイックスタート)
- [リクエストの送信](#リクエストの送信)
- [オプションリファレンス](#オプションリファレンス)
- [レスポンス](#レスポンス)
- [エラー処理](#エラー処理)
- [イベント](#イベント)
- [使用例](#使用例)
- [ネットワークなしのテスト](#ネットワークなしのテスト)
- [応用](#応用)

## インストール

```
composer require andy87/knock-knock
```

Composer を使わない場合: `require '/path/to/knock-knock/autoload.php';`

PHP >= 8.2、ext-curl、ext-json が必要です。

## クイックスタート

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // スキーム省略可(デフォルトは https)

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // 生のボディ
print_r($response->json());      // デコード済み JSON(配列)
```

ベース URL はクライアントごとに固定です。パスは常にそれに対する相対パスで、絶対 URL をパスに渡すと
`InvalidRequestException` が投げられます。

## リクエストの送信

どのメソッドも `Response` を返します。最後の引数はリクエスト単位のヘッダー配列(省略可)です。

| メソッド | シグネチャ | `$data` の送り先 |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | クエリ文字列 |
| `head` | `head($path, array $query = [], array $headers = [])` | クエリ文字列 |
| `options` | `options($path, array $query = [], array $headers = [])` | クエリ文字列 |
| `trace` | `trace($path, array $query = [], array $headers = [])` | クエリ文字列 |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | ボディ |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | ボディ |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | ボディ |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | ボディ |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | メソッドによる |

**クエリ文字列。** 配列は RFC 3986 でエンコードされ(`a b` → `a%20b`)、パスにすでにクエリがあるかどうかで
`?` か `&` で連結されます。文字列はそのまま追加されます(先頭の `?`/`&` は除去)。

**ボディ。** 配列はクライアントの content type に従ってエンコードされます(デフォルトは JSON)。文字列は
`"0"` も含めてそのまま送信されます。空の配列/文字列の場合、ボディも `Content-Type` ヘッダーも送りません。
`Content-Type` ヘッダーは、自分で設定していなければ自動的に付与されます。

```php
$client->post('/users', ['name' => 'Ann']);                     // JSON ボディ
$client->post('/raw', '{"already":"encoded"}');                  // そのまま送信
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // リクエスト単位のヘッダー
$client->request(Method::Put, '/things/1', ['a' => 1]);         // 汎用形式
```

## オプションリファレンス

### コンストラクタ

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // デフォルト: new CurlTransport()
    array $headers = [],                // デフォルトヘッダー
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // 秒、リクエスト全体
    float $connectTimeout = 10.0,       // 秒、接続フェーズ
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // 通常は on() で設定
);
```

すべてをコンストラクタに渡すより、メソッドチェーンの `with*` を使うことを推奨します。

### メソッドチェーンによる設定

各呼び出しは**新しい**クライアントを返します(元のクライアントは変更されません)。そのため、ベースのクライアントを安全に共有できます。
トランスポート(とその cURL ハンドル)はコピー間で共有され、接続は維持されます。

| メソッド | 効果 | デフォルト |
|---|---|---|
| `withHeaders(array $headers)` | デフォルトヘッダーをマージ。名前は大文字小文字を区別せず、後のものが優先 | なし |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | 配列ボディのエンコード方式 | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | 全体および(任意で)接続のタイムアウト(秒) | 30 / 10 |
| `withSslVerification(bool $verify)` | 証明書を検証(ピアとホスト、レベル 2) | `true` |
| `withRedirects(bool $follow = true)` | `3xx` リダイレクトを追従 | オフ |
| `on(Event $event, callable $listener)` | イベントリスナーを追加 | – |

### `ContentType` の値

| 値 | ヘッダー | 配列のエンコード |
|---|---|---|
| `Json` | `application/json` | `json_encode`(Unicode とスラッシュはエスケープしない) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query`(RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | 配列は非対応 — 文字列を渡してください |

### `Method` の値

`Get Post Put Patch Delete Head Options Trace`。`Get/Head/Options/Trace` はデータをクエリ文字列に入れます。

## レスポンス

`KnockKnock\Http\Response` はイミュータブルです。

| メンバー | 説明 |
|---|---|
| `status` | HTTP ステータスコード(`int`) |
| `body` | 生のボディ(`string`。`HEAD` では空) |
| `headers` | 全ヘッダー(名前は小文字)。繰り返されたヘッダーは `, ` で連結 |
| `header($name)` | 大文字小文字を区別せず 1 つのヘッダーを取得、なければ `null` |
| `json()` | ボディを配列として取得。JSON オブジェクト/配列でなければ `InvalidRequestException` |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | 4xx/5xx なら `HttpException`、それ以外はレスポンス自身を返す(チェーン可) |
| `request` | 実際に送信された `Request`(`BeforeSend` 適用後) |
| `info` | `total_time`、`primary_ip`、`size_download`、`effective_url` |

3xx はエラーではありません。リダイレクトを無効にしていればそのまま受け取り、`header('Location')` を読めます。

## エラー処理

ライブラリの例外はすべて `KnockKnock\Exception\KnockKnockException`(`RuntimeException`)を継承します。

| 例外 | 発生条件 |
|---|---|
| `InvalidRequestException` | 不正なベース URL、パスとしての絶対 URL、不正なヘッダー名/値(CR/LF/NUL)、エンコードできないデータ、`json()` での非 JSON ボディ |
| `TransportException` | レスポンスなし: DNS、接続拒否、タイムアウト、TLS 失敗。`getCode()` は cURL の errno |
| `HttpException` | 4xx/5xx に対する `->throw()` のみ。レスポンスは `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // リトライ/バックオフはここ
}
```

## イベント

`on()` でイベントごとに複数のリスナーを登録できます。登録順に実行されます。

| イベント | リスナーが受け取るもの | 戻り値 |
|---|---|---|
| `Event::BeforeSend` | `Request` | 置き換え用の `Request`(任意。`null` なら変更なし) |
| `Event::AfterSend` | `Response` | 置き換え用の `Response`(任意) |
| `Event::Failure` | `TransportException`, `Request` | 無視される。その後、例外は再スローされる |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` もイミュータブルです: `withHeader()`、`withHeaders()`、`withBody()`、`withCurlOptions()`。

## 使用例

### 認証付き JSON API とエラー処理

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

### Basic 認証とフォームエンコード

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### ヘッダー、リダイレクト、診断情報

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301。デフォルトでは追従しない
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // 最終ページまで追従
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### 自己署名証明書でのローカル開発

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // 本番では絶対に使わない
```

### ネットワークエラーと 5xx の簡易リトライ

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

### 複数サービスでベースクライアントを共有

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## ネットワークなしのテスト

`FakeTransport` を渡せば、ネットワークには一切触れません。レスポンスはキューの順に返され、
キューが空だと `TransportException` が投げられます。

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // ステータス、ボディ、ヘッダー
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // 送信された各リクエストを検査
$fake->sent[0]->header('authorization');
```

リポジトリ自身のテストスイートは、PHP 組み込みサーバー(`tests/Support/router.php`)に対する実際の往復通信も行っています。
結合テストのひな形として使えます。

## 応用

### 生の cURL オプション

`BeforeSend` リスナー内で `Request::withCurlOptions()` を使うと、1 リクエストに任意の `CURLOPT_*` を設定できます。
生のオプションは、クライアント設定から導出されるものより優先されます。

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // gzip/deflate を受け入れてデコード
]));
```

トランスポートの全リクエストに適用したいオプションは、`CurlTransport` に直接渡します:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(タイムアウトや SSL フラグなど、クライアントから導出されるオプションは、これらのデフォルトより優先されます。)

### カスタムトランスポート

`KnockKnock\Transport\Transport`(メソッドは `send(Request): Response` のみ)を実装すれば、別の HTTP スタックの利用、
キャッシュの追加、通信の記録/再生などができます。ネットワーク障害には `TransportException` を投げてください。

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### 補足

- cURL ハンドルはトランスポートごとに 1 つを再利用し、リクエスト間でリセットされます。オプションが次のリクエストに漏れることはありません。
- 1 つのハンドルを複数の Fiber/スレッドから同時に使う用途は想定していません。
- ヘッダー値は生成時に検証されます。空の値は空のヘッダー(`Name;`)として送信されます。

プロジェクト構成、オリジナルとの比較、テストの実行方法は[英語版 README](../../README.md)を参照してください。

ライセンス: CC BY-SA 4.0(オリジナルと同じ)。
