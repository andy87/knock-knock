# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [日本語](README.ja.md) · [Español](README.es.md) · [简体中文](README.zh-CN.md) · **Português (BR)** · [한국어](README.ko.md)

Um cliente HTTP para PHP pequeno, imutável e sem dependências, baseado em ext-cURL. Uma reescrita limpa de
[andy87/knock-knock](https://github.com/andy87/knock-knock): a mesma ideia (uma fachada mínima sobre o cURL com
autenticação, eventos e respostas falsas), reestruturada para PHP 8.2+.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## Sumário

- [Instalação](#instalação)
- [Início rápido](#início-rápido)
- [Enviando requisições](#enviando-requisições)
- [Referência de opções](#referência-de-opções)
- [Respostas](#respostas)
- [Tratamento de erros](#tratamento-de-erros)
- [Eventos](#eventos)
- [Exemplos](#exemplos)
- [Testes sem rede](#testes-sem-rede)
- [Avançado](#avançado)

## Instalação

```
composer require andy87/knock-knock
```

Sem Composer: `require '/path/to/knock-knock/autoload.php';`

Requer PHP >= 8.2, ext-curl, ext-json.

## Início rápido

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // esquema opcional, https por padrão

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // corpo bruto
print_r($response->json());      // JSON decodificado (array)
```

A URL base é fixa por cliente. Os caminhos são sempre relativos a ela; passar uma URL absoluta como caminho
lança `InvalidRequestException`.

## Enviando requisições

Cada verbo retorna uma `Response`. O último argumento é um array opcional de cabeçalhos só para aquela requisição.

| Método | Assinatura | `$data` vai para |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | query string |
| `head` | `head($path, array $query = [], array $headers = [])` | query string |
| `options` | `options($path, array $query = [], array $headers = [])` | query string |
| `trace` | `trace($path, array $query = [], array $headers = [])` | query string |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | corpo |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | corpo |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | corpo |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | corpo |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | conforme o método |

**Query strings.** Arrays são codificados conforme a RFC 3986 (`a b` → `a%20b`) e anexados com `?` ou `&`
dependendo de o caminho já ter query. Uma string é anexada como está (`?`/`&` iniciais são removidos).

**Corpos.** Um array é codificado conforme o content type do cliente (JSON por padrão). Uma string é enviada
sem alteração, inclusive `"0"`. Array/string vazio não envia corpo nem `Content-Type`.
O cabeçalho `Content-Type` é adicionado automaticamente, a menos que você defina o seu.

```php
$client->post('/users', ['name' => 'Ann']);                     // corpo JSON
$client->post('/raw', '{"already":"encoded"}');                  // como está
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // cabeçalho por requisição
$client->request(Method::Put, '/things/1', ['a' => 1]);         // forma genérica
```

## Referência de opções

### Construtor

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // padrão: new CurlTransport()
    array $headers = [],                // cabeçalhos padrão
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // segundos, requisição inteira
    float $connectTimeout = 10.0,       // segundos, fase de conexão
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // normalmente via on()
);
```

Prefira os métodos fluentes `with*` em vez de passar tudo ao construtor.

### Configuração fluente

Cada chamada retorna um cliente **novo** (o original não muda), então um cliente base pode ser compartilhado com segurança.
O transporte (e seu handle cURL) é compartilhado entre as cópias, mantendo as conexões abertas.

| Método | Efeito | Padrão |
|---|---|---|
| `withHeaders(array $headers)` | Mescla cabeçalhos padrão; nomes sem diferenciar maiúsculas, o último vence | nenhum |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | Codificação de corpos em array | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | Timeout total e (opcional) de conexão, em segundos | 30 / 10 |
| `withSslVerification(bool $verify)` | Verifica o certificado (peer e host, nível 2) | `true` |
| `withRedirects(bool $follow = true)` | Segue redirecionamentos `3xx` | desligado |
| `on(Event $event, callable $listener)` | Adiciona um listener de evento | – |

### Casos de `ContentType`

| Caso | Cabeçalho | Codificação de array |
|---|---|---|
| `Json` | `application/json` | `json_encode` (Unicode e barras sem escape) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query` (RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | arrays não suportados — passe uma string |

### Casos de `Method`

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace` colocam os dados na query string.

## Respostas

`KnockKnock\Http\Response` é imutável.

| Membro | Descrição |
|---|---|
| `status` | Código HTTP (`int`) |
| `body` | Corpo bruto (`string`; vazio em `HEAD`) |
| `headers` | Todos os cabeçalhos, nomes em minúsculas; os repetidos são unidos com `, ` |
| `header($name)` | Um cabeçalho, sem diferenciar maiúsculas, ou `null` |
| `json()` | Corpo decodificado como array; lança `InvalidRequestException` se não for objeto/array JSON |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | Lança `HttpException` em 4xx/5xx; caso contrário retorna a resposta (encadeável) |
| `request` | O `Request` realmente enviado (após `BeforeSend`) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

Uma resposta 3xx não é erro; com os redirecionamentos desligados você a recebe e pode ler `header('Location')`.

## Tratamento de erros

Todas as exceções da biblioteca estendem `KnockKnock\Exception\KnockKnockException` (uma `RuntimeException`).

| Exceção | Quando |
|---|---|
| `InvalidRequestException` | URL base inválida, URL absoluta como caminho, nome/valor de cabeçalho inválido (CR/LF/NUL), dados não codificáveis, corpo não JSON em `json()` |
| `TransportException` | Sem resposta: DNS, conexão recusada, timeout, falha de TLS. `getCode()` é o errno do cURL |
| `HttpException` | Somente por `->throw()` em 4xx/5xx; a resposta fica em `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // retry/backoff aqui
}
```

## Eventos

Registre quantos listeners quiser por evento com `on()`. Eles rodam na ordem de registro.

| Evento | O listener recebe | Valor de retorno |
|---|---|---|
| `Event::BeforeSend` | `Request` | `Request` substituto opcional (`null` mantém o atual) |
| `Event::AfterSend` | `Response` | `Response` substituta opcional |
| `Event::Failure` | `TransportException`, `Request` | ignorado; a exceção é relançada depois |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` também é imutável: `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

## Exemplos

### API JSON autenticada com tratamento de erros

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

### Autenticação Basic e codificação de formulário

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### Cabeçalhos, redirecionamentos e diagnóstico

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, não seguido por padrão
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // segue até a página final
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### Desenvolvimento local com certificado autoassinado

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // nunca em produção
```

### Retry simples em erros de rede e 5xx

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

### Um cliente base para vários serviços

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## Testes sem rede

Passe um `FakeTransport` e nada toca a rede. As respostas saem na ordem da fila;
fila vazia lança `TransportException`.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // status, corpo, cabeçalhos
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // inspecione cada requisição enviada
$fake->sent[0]->header('authorization');
```

A própria suíte de testes do repositório também faz idas e vindas reais contra o servidor embutido do PHP
(`tests/Support/router.php`) — um bom modelo para testes de integração.

## Avançado

### Opções cURL brutas

Use `Request::withCurlOptions()` em um listener `BeforeSend` para definir qualquer `CURLOPT_*` em uma requisição.
Opções brutas têm precedência sobre as derivadas das configurações do cliente.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // aceita gzip/deflate e decodifica
]));
```

Para opções que valem para todas as requisições de um transporte, passe-as direto ao `CurlTransport`:

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(Opções derivadas do cliente, como timeouts e flags SSL, continuam tendo precedência sobre esses padrões.)

### Transporte próprio

Implemente `KnockKnock\Transport\Transport` (um método, `send(Request): Response`) para usar outra pilha
HTTP, adicionar cache ou gravar/reproduzir tráfego. Lance `TransportException` em falhas de rede.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### Observações

- Um handle cURL é reutilizado por transporte e reiniciado entre requisições; as opções não «vazam».
- Um cliente não foi projetado para uso concorrente do mesmo handle por vários fibers/threads.
- Os valores de cabeçalho são validados na construção; um valor vazio envia um cabeçalho vazio (`Name;`).

Estrutura do projeto, comparação com o original e execução dos testes: veja o [README em inglês](../../README.md).

Licença: CC BY-SA 4.0 (igual ao original).
