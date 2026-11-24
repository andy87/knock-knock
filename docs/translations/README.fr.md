# KnockKnock

[English](../../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · **Français** · [日本語](README.ja.md) · [Español](README.es.md) · [简体中文](README.zh-CN.md) · [Português (BR)](README.pt-BR.md) · [한국어](README.ko.md)

Un petit client HTTP PHP immuable, sans dépendance, basé sur ext-cURL. Une réécriture propre de
[andy87/knock-knock](https://github.com/andy87/knock-knock) : même idée (une façade cURL minimale avec
authentification, événements et fausses réponses), restructurée pour PHP 8.2+.

```php
$api = (new KnockKnock\Client('https://api.example.com'))
    ->withBearer($token)
    ->withTimeout(10);

$user  = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
$saved = $api->post('/posts', ['title' => 'Hi'])->throw()->json();
```

## Sommaire

- [Installation](#installation)
- [Démarrage rapide](#démarrage-rapide)
- [Envoyer des requêtes](#envoyer-des-requêtes)
- [Référence des options](#référence-des-options)
- [Réponses](#réponses)
- [Gestion des erreurs](#gestion-des-erreurs)
- [Événements](#événements)
- [Exemples](#exemples)
- [Tester sans réseau](#tester-sans-réseau)
- [Avancé](#avancé)

## Installation

```
composer require andy87/knock-knock
```

Sans Composer : `require '/path/to/knock-knock/autoload.php';`

Nécessite PHP >= 8.2, ext-curl, ext-json.

## Démarrage rapide

```php
use KnockKnock\Client;

$client = new Client('https://api.example.com');   // schéma facultatif, https par défaut

$response = $client->get('/status');

echo $response->status;          // 200
echo $response->body;            // corps brut
print_r($response->json());      // JSON décodé (tableau)
```

L'URL de base est fixée par client. Les chemins lui sont toujours relatifs ; une URL absolue comme chemin
lève `InvalidRequestException`.

## Envoyer des requêtes

Chaque méthode renvoie une `Response`. Le dernier argument est un tableau facultatif d'en-têtes propres à la requête.

| Méthode | Signature | `$data` va dans |
|---|---|---|
| `get` | `get($path, array $query = [], array $headers = [])` | la query string |
| `head` | `head($path, array $query = [], array $headers = [])` | la query string |
| `options` | `options($path, array $query = [], array $headers = [])` | la query string |
| `trace` | `trace($path, array $query = [], array $headers = [])` | la query string |
| `post` | `post($path, array\|string $data = [], array $headers = [])` | le corps |
| `put` | `put($path, array\|string $data = [], array $headers = [])` | le corps |
| `patch` | `patch($path, array\|string $data = [], array $headers = [])` | le corps |
| `delete` | `delete($path, array\|string $data = [], array $headers = [])` | le corps |
| `request` | `request(Method $method, $path, array\|string $data = [], array $headers = [])` | selon la méthode |

**Query strings.** Les tableaux sont encodés selon la RFC 3986 (`a b` → `a%20b`) et ajoutés avec `?` ou `&`
selon que le chemin contient déjà une query. Une chaîne est ajoutée telle quelle (`?`/`&` initiaux retirés).

**Corps.** Un tableau est encodé selon le content type du client (JSON par défaut). Une chaîne est envoyée
sans modification, y compris `"0"`. Un tableau/une chaîne vide n'envoie ni corps ni `Content-Type`.
Un en-tête `Content-Type` est ajouté automatiquement sauf si vous en définissez un.

```php
$client->post('/users', ['name' => 'Ann']);                     // corps JSON
$client->post('/raw', '{"already":"encoded"}');                  // tel quel
$client->withContentType(ContentType::Form)
       ->post('/login', ['user' => 'ann', 'pass' => 'x']);       // user=ann&pass=x
$client->post('/x', $data, ['X-Idempotency-Key' => $key]);      // en-tête par requête
$client->request(Method::Put, '/things/1', ['a' => 1]);         // forme générique
```

## Référence des options

### Constructeur

```php
new Client(
    string $baseUrl,
    ?Transport $transport = null,       // défaut : new CurlTransport()
    array $headers = [],                // en-têtes par défaut
    ContentType $contentType = ContentType::Json,
    float $timeout = 30.0,              // secondes, requête entière
    float $connectTimeout = 10.0,       // secondes, phase de connexion
    bool $verifySsl = true,
    bool $followRedirects = false,
    array $listeners = [],              // normalement via on()
);
```

Préférez les méthodes fluides `with*` plutôt que de tout passer au constructeur.

### Configuration fluide

Chaque appel renvoie un **nouveau** client (l'original n'est pas modifié) : un client de base peut donc être partagé sans risque.
Le transport (et son handle cURL) est partagé entre les copies, les connexions restent ouvertes.

| Méthode | Effet | Défaut |
|---|---|---|
| `withHeaders(array $headers)` | Fusionne des en-têtes par défaut ; noms insensibles à la casse, le dernier l'emporte | aucun |
| `withBearer(string $token)` | `Authorization: Bearer <token>` | – |
| `withBasic(string $user, string $password)` | `Authorization: Basic <base64>` | – |
| `withContentType(ContentType $type)` | Encodage des corps tableau | `Json` |
| `withTimeout(float $s, ?float $connectS = null)` | Délai total et (option) de connexion, en secondes | 30 / 10 |
| `withSslVerification(bool $verify)` | Vérifie le certificat (pair et hôte, niveau 2) | `true` |
| `withRedirects(bool $follow = true)` | Suit les redirections `3xx` | désactivé |
| `on(Event $event, callable $listener)` | Ajoute un écouteur d'événement | – |

### Cas de `ContentType`

| Cas | En-tête | Encodage d'un tableau |
|---|---|---|
| `Json` | `application/json` | `json_encode` (Unicode et slashs non échappés) |
| `Form` | `application/x-www-form-urlencoded` | `http_build_query` (RFC 3986) |
| `Text`, `Xml`, `Html` | `text/plain`, `application/xml`, `text/html` | tableaux non gérés — passez une chaîne |

### Cas de `Method`

`Get Post Put Patch Delete Head Options Trace`. `Get/Head/Options/Trace` placent les données dans la query string.

## Réponses

`KnockKnock\Http\Response` est immuable.

| Membre | Description |
|---|---|
| `status` | Code HTTP (`int`) |
| `body` | Corps brut (`string` ; vide pour `HEAD`) |
| `headers` | Tous les en-têtes, noms en minuscules ; les répétés sont joints par `, ` |
| `header($name)` | Un en-tête, insensible à la casse, ou `null` |
| `json()` | Corps décodé en tableau ; lève `InvalidRequestException` si ce n'est pas un objet/tableau JSON |
| `successful()` | `2xx` |
| `failed()` | `>= 400` |
| `throw()` | Lève `HttpException` sur 4xx/5xx, sinon renvoie la réponse (chaînable) |
| `request` | Le `Request` réellement envoyé (après `BeforeSend`) |
| `info` | `total_time`, `primary_ip`, `size_download`, `effective_url` |

Une réponse 3xx n'est pas une erreur ; redirections désactivées, vous la recevez et pouvez lire `header('Location')`.

## Gestion des erreurs

Toutes les exceptions de la bibliothèque héritent de `KnockKnock\Exception\KnockKnockException` (une `RuntimeException`).

| Exception | Quand |
|---|---|
| `InvalidRequestException` | URL de base invalide, URL absolue comme chemin, nom/valeur d'en-tête invalide (CR/LF/NUL), données non encodables, corps non JSON pour `json()` |
| `TransportException` | Pas de réponse : DNS, connexion refusée, délai dépassé, échec TLS. `getCode()` est l'errno cURL |
| `HttpException` | Uniquement via `->throw()` sur 4xx/5xx ; la réponse est dans `$e->response` |

```php
try {
    $data = $client->get('/orders')->throw()->json();
} catch (HttpException $e) {
    log("HTTP {$e->response->status}: {$e->response->body}");
} catch (TransportException $e) {
    log("network: {$e->getMessage()}");        // nouvelle tentative/backoff ici
}
```

## Événements

Enregistrez autant d'écouteurs que voulu par événement avec `on()`. Ils s'exécutent dans l'ordre d'enregistrement.

| Événement | L'écouteur reçoit | Valeur de retour |
|---|---|---|
| `Event::BeforeSend` | `Request` | `Request` de remplacement facultatif (`null` pour conserver) |
| `Event::AfterSend` | `Response` | `Response` de remplacement facultative |
| `Event::Failure` | `TransportException`, `Request` | ignorée ; l'exception est ensuite relancée |

```php
$api = $api
    ->on(Event::BeforeSend, fn (Request $r) => $r->withHeader('X-Request-Id', bin2hex(random_bytes(8))))
    ->on(Event::AfterSend,  function (Response $r) use ($logger) {
        $logger->info($r->request->method->value . ' ' . $r->request->url . ' ' . $r->status);
    })
    ->on(Event::Failure,    fn ($e, $r) => $logger->error($e->getMessage()));
```

`Request` est lui aussi immuable : `withHeader()`, `withHeaders()`, `withBody()`, `withCurlOptions()`.

## Exemples

### API JSON authentifiée avec gestion des erreurs

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

### Authentification Basic et encodage formulaire

```php
$legacy = (new Client('https://legacy.example.com'))
    ->withBasic('svc-user', $password)
    ->withContentType(ContentType::Form);

$legacy->post('/submit.php', ['a' => 1, 'b' => 'two words']);
```

### En-têtes, redirections et diagnostic

```php
$r = $client->head('/file.zip');
echo $r->header('Content-Length');

$r = $client->get('/old-url');                       // 301, non suivie par défaut
echo $r->header('Location');

$r = $client->withRedirects()->get('/old-url');      // suit jusqu'à la page finale
echo $r->info['effective_url'], ' in ', $r->info['total_time'], 's';
```

### Développement local avec certificat auto-signé

```php
$dev = (new Client('https://localhost:8443'))->withSslVerification(false);   // jamais en production
```

### Nouvelle tentative simple sur erreurs réseau et 5xx

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

### Un client de base pour plusieurs services

```php
$base = (new Client('https://api.example.com'))->withTimeout(10)->on(Event::AfterSend, $logger);
$users  = $base->withBearer($userToken);
$admin  = $base->withBearer($adminToken)->withHeaders(['X-Scope' => 'admin']);
```

## Tester sans réseau

Passez un `FakeTransport` : rien ne touche le réseau. Les réponses sortent dans l'ordre de la file ;
une file vide lève `TransportException`.

```php
use KnockKnock\Transport\FakeTransport;

$fake = (new FakeTransport())
    ->queueJson(['id' => 1])                       // 200 + JSON + content-type
    ->queue(500, 'oops')                           // statut, corps, en-têtes
    ->queue(204);

$api = new Client('https://api.test', $fake);

$api->get('/x')->json();                           // ['id' => 1]
$api->get('/x')->status;                           // 500

$fake->sent[0]->url;                               // inspecter chaque requête envoyée
$fake->sent[0]->header('authorization');
```

La suite de tests du dépôt effectue aussi de vrais allers-retours vers le serveur intégré de PHP
(`tests/Support/router.php`) — un bon modèle pour des tests d'intégration.

## Avancé

### Options cURL brutes

Utilisez `Request::withCurlOptions()` dans un écouteur `BeforeSend` pour définir n'importe quel `CURLOPT_*` pour une requête.
Les options brutes l'emportent sur celles dérivées des réglages du client.

```php
$api = $api->on(Event::BeforeSend, fn (Request $r) => $r->withCurlOptions([
    CURLOPT_PROXY => 'http://proxy.local:3128',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    CURLOPT_ENCODING => '',                         // accepte gzip/deflate et décode
]));
```

Pour des options valables pour toutes les requêtes d'un transport, passez-les directement à `CurlTransport` :

```php
$client = new Client('https://api.example.com', new CurlTransport([
    CURLOPT_USERAGENT => 'my-app/1.0',
]));
```

(Les options dérivées du client, comme les délais et les drapeaux SSL, restent prioritaires sur ces valeurs par défaut.)

### Transport personnalisé

Implémentez `KnockKnock\Transport\Transport` (une méthode, `send(Request): Response`) pour utiliser une autre pile
HTTP, ajouter du cache ou enregistrer/rejouer le trafic. Levez `TransportException` pour les échecs réseau.

```php
final class LoggingTransport implements Transport
{
    public function __construct(private Transport $inner) {}
    public function send(Request $r): Response { error_log($r->url); return $this->inner->send($r); }
}
$client = new Client('https://api.example.com', new LoggingTransport(new CurlTransport()));
```

### Remarques

- Un seul handle cURL est réutilisé par transport et réinitialisé entre les requêtes ; les options ne « fuient » pas.
- Un client n'est pas conçu pour un usage concurrent d'un même handle par des fibers/threads.
- Les valeurs d'en-tête sont validées à la construction ; une valeur vide envoie un en-tête vide (`Name;`).

Structure du projet, comparaison avec l'original et exécution des tests : voir le [README anglais](../../README.md).

Licence : CC BY-SA 4.0 (comme l'original).
