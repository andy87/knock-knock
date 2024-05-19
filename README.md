# KnockKnock

PHP Фасад\Адаптер для отправки запросов через ext cURL

Возможности:
- Настройка параметров запросов
- Обработчики событий

---

> [!NOTE]
> ![IN PROGRESS](http://www.bc-energy.it/wp-content/uploads/2013/08/work-in-progress.png)

---


# Расширения


## [KnockKnockOctopus](docs/KnockKnock/KnockKnockOctopus.md)
Предоставляет доступ к "простым" методам отправки запросов через ext cURL

Доступные методы:
- get()
- post()
- и т.д.

```php
$knockKnockOctopus->get( '/profile', [ 'id' => 806034 ] );

$knockKnockOctopus->post( '/new', [ 
    'name' => 'Новая новость',
    'content' => 'Текст новости' 
]);

```

## [KnockKnockSecurity](docs/KnockKnock/KnockKnockSecurity.md)

Класс предоставляет доступ к "функциональным" методам для простой реализации авторизации и отправки запросов через ext cURL


# KnockKnock
Получение объекта/экземпляра класса и его настройка
### Нативный
```php
$knockKnock = new KnockKnock([
    KnockRequest::HOST => 'some.domain',
    KnockRequest::CONTENT_TYPE => KnockRequest::CONTENT_TYPE_FORM,
]);
```

### Singleton
```php
$knockKnock = KnockKnock::getInstance([
    KnockRequest::HOST => 'domain.zone',
    KnockRequest::PROTOCOL => 'http',
    KnockRequest::HEADER => KnockRequest::CONTENT_TYPE_JSON,
])->useAuthorization( 'myToken', KnockKnock::TOKEN_BEARER );
```
`getInstance( array $knockKnockConfig = [] ): self`


## Настройка параметров запросов
Доступны несколько уникальных методов для настройки некоторых, свойств,
которые в дальнейшем будут передаваться всем запросам отправляемыми объектом `$knockKnock`

Все подобные методы возвращают `static` объект / экземпляр класса `KnockKnock`

Отдельными вызовами.
```php
$knockKnock->useAuthorization( 'myToken', KnockKnock::TOKEN_BEARER ); // задаёт/переустанавливает использование токена
$knockKnock->useHeaders(['api-secret' => 'secretKey12']); // задаёт/переустанавливает заголовки
$knockKnock->useContentType( KnockRequest::CONTENT_TYPE_MULTIPART ); // задаёт/переустанавливает тип контента
```

Цепочка вызовов:
```php
$knockKnock
    ->useAuthorization('token', KnockKnock::TOKEN_BASIC )
    ->useHeaders(['api-secret' => 'secretKey23'])
    ->useContentType( KnockRequest::CONTENT_TYPE_MULTIPART );

$bearer = $knockKnock->getAuthorization(); // string
```


# Обработчики событий
Задать обработчики событий
- после создания объекта knockKnock
- после создания объекта запроса
- перед отправкой запроса
- после создания объекта ответа
- после получения ответа

```php
$knockKnock->setupEventHandlers([
    KnockKnock::EVENT_AFTER_CONSTRUCT => fn( static $knockKnock ) => {
        // создание объекта knockKnock
    },
    KnockKnock::EVENT_CREATE_REQUEST => fn( static $knockKnock, KnockRequest $knockRequest ) => {
        // создание объекта запроса
    },
    KnockKnock::EVENT_BEFORE_SEND => fn(  static $knockKnock, KnockRequest $knockRequest ) => {
        // отправка запроса
    },
    KnockKnock::EVENT_CREATE_RESPONSE => fn( static $knockKnock, KnockResponse $knockResponse ) => {
        // создание объекта ответа
    },
    KnockKnock::EVENT_AFTER_SEND => fn( static $knockKnock, KnockResponse $knockResponse ) => {
        // получение ответа
    }
]);
```
`setupEventHandlers( array $callbacks ): self`


# KnockRequest, Запрос

Нативное создание объекта / экземпляра класса с данными для конкретного запроса
```php
$knockRequest = new KnockRequest( 'info/me', [
    KnockRequest::METHOD => KnockMethod::POST,
    KnockRequest::DATA => [ 'client_id' => 34 ],
    KnockRequest::HEADERS => [ 'api-secret-key' => 'secretKey34' ],
    KnockRequest::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    KnockRequest::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    KnockRequest::CONTENT_TYPE => KnockContentType::FORM_DATA,
]);
```

Доступно создание - через метод фасада (с вызовом callback функции )
```php
$knockRequest = $knockKnock->constructKnockRequest( 'info/me', [
    KnockRequest::METHOD => KnockMethod::POST,
    KnockRequest::DATA => [ 'client_id' => 45 ],
    KnockRequest::HEADERS => [ 'api-secret-key' => 'secretKey45' ],
    KnockRequest::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    KnockRequest::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    KnockRequest::CONTENT_TYPE => KnockContentType::FORM_DATA,
]);
```
`constructKnockRequest( string $url, array $knockRequestConfig = [] ): KnockRequest`

### Назначение/Изменение/Получение отдельных параметров запроса (set/get)

Таблица set/get методов для взаимодействия с отдельными свойствами запроса

| Параметр | Сеттер | Геттер | Информация |
| --- | --- | --- | --- |
| Протокол | setProtocol( string $protocol )       | getProtocol(): string | <a href="https://curl.se/docs/protdocs.html" target="_blank">протоколы</a> |
| Хост | setHost( string $host )               | getHost(): string | --- |
| URL | setUrl( string $url )                 | getUrl(): string | --- |
| Метод | setMethod( string $method )           | getMethod(): string |  |<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods" target="_blank">методы</a>
| Заголовки | setHeaders( array $headers )          | getHeaders(): array | <a href="https://ru.wikipedia.org/wiki/%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA_%D0%B7%D0%B0%D0%B3%D0%BE%D0%BB%D0%BE%D0%B2%D0%BA%D0%BE%D0%B2_HTTP" target="_blank">загловки</a> |
| Тип контента | setContentType( string $contentType ) | getContentType(): string | <a href="https://ru.wikipedia.org/wiki/%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA_MIME-%D1%82%D0%B8%D0%BF%D0%BE%D0%B2" target="_blank">Тип контента</a> |
| Данные | setData( mixed $data )                | getData(): mixed | --- |
| Опции cURL | setCurlOptions( array $curlOptions )  | getCurlOptions(): array | <a href="https://www.php.net/manual/ru/function.curl-setopt.php" target="_blank">Опции cURL</a> |
| Информация cURL | setCurlInfo( array $curlInfo )        | getCurlInfo(): array | <a href="https://www.php.net/manual/ru/function.curl-getinfo.php" target="_blank">Информация cURL</a> |

```php
$knockRequest = $knockKnock->constructKnockRequest('info/me');

$knockRequest->setMethod( KnockMethod::GET );
$knockRequest->setData(['client_id' => 67]);
$knockRequest->setHeaders(['api-secret-key' => 'secretKey67']);
$knockRequest->setCurlOptions([
    CURLOPT_TIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true
]);
$knockRequest->setCurlInfo([
    CURLINFO_CONTENT_TYPE,
    CURLINFO_HEADER_SIZE,
    CURLINFO_TOTAL_TIME
]);
$knockRequest->setContentType( KnockContentType::JSON );

$protocol = $knockRequest->getPrococol(); // string
$host = $knockRequest->getHost(); // string
// ... аналогичным образом доступны и другие подобные методы для получения свойств запроса
```

### Микс параметров создаваемого запроса с данными переданными опционально

Можно создать запрос, на основе уже созданного объекта
и дополнительным аргументом передать уникальные собственные параметры.
```php
$knockKnock->setupRequest( $knockRequest, [
    KnockRequest::HOST => 'domain.zone',
    KnockKnock::BEARER => 'token-bearer-2',
    KnockKnock::HEADERS => [
        'api-secret' => 'secretKey78'
    ],
]);
```
`setupRequest( KnockRequest $knockRequest, array $options = [] ): self`


## KnockResponse: Ответ

Конструктор KnockResponse с вызовом callback функции, если она установлена
```php
$knockResponse = $knockKnock->constructKnockResponse([
    'id' => 806034,
    'name' => 'and_y87'
], $knockRequest );
```
`constructKnockResponse( array $KnockResponseParams, ?KnockRequest $knockRequest = null ): KnockResponse`

## KnockResponse: Отправка запроса и получение ответа

Получение ответа отправленного запроса и вызов callback функции, если она установлена
```php
$knockKnock->setupRequest( $knockRequest );
$knockResponse = $knockKnock->send();
```
`send( array $prepareKnockResponseParams = [] ): KnockResponse`
возвращает объект/экземпляр класса KnockResponse

Получение ответа с отправкой запроса - цепочкой вызовов
```php
$knockResponse = $knockKnock->setRequest( $knockRequest )->send(); // return KnockResponse
```


## Отправка запроса с фэйковым ответом

Цепочка вызовов, возвращает подготовленный ответ и вызывает callback функцию, если она установлена
```php
// параметры возвращаемого ответа
$prepareFakeKnockResponseParams = [
    KnockResponse::HTTP_CODE => 200,
    KnockResponse::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
];

$knockResponse = $knockKnock->setupRequest( $knockRequest )->send( $prepareFakeKnockResponseParams );
```
объект `$knockResponse` будет содержать данные переданные в аргументе `$prepareFakeKnockResponseParams`


## Данные в ответе

Задаются данные
```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

$knockResponse
    ->setHttpCode(200)
    ->setContent('{"id" => 8060345, "nickName" => "and_y87"}');
```
Если данные уже установлены, выбрасывается `Exception`, для замены используется `replace`

Подменяются данные
```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

$knockResponse
    ->replace( KnockResponse::HTTP_CODE, 200 )
    ->replace( KnockResponse::CONTENT, '{"id" => 8060345, "nickName" => "and_y87"}' );
```

## Данные запроса из ответа

Получение массива с данными ответа
```php
// Получение опций запроса (  KnockRequest::CURL_OPTIONS )
$curlOptions =  $knockResponse->get( KnockResponse::CURL_OPTIONS ); // return array

// Получение данных о запросе ( KnockRequest::CURL_INFO )
$curlInfo =  $knockResponse->get( KnockResponse::CURL_INFO ); // return array

```

# Custom реализация

Custom реализация Базового класса, к примеру с добавлением логирования работающим "под капотом"
```php
class KnockKnockYandex extends KnockKnock
{
    private const LOGGER = 'logger';


    private string $host = 'https://api.yandex.ru/'

    private string $contentType = KnockContentType::JSON

    private YandexLogger $logger;



    /**
     * @return void
     */
    public function init(): void
    {
        $this->event[self::AFTER_CREATE_REQUEST] = fn( KnockRequest $knockRequest ) => 
        {
            $this->addYandexLog( $this->getLogDataByRequest( $knockRequest ) );
        };

        $this->event[self::EVENT_AFTER_SEND] = fn( KnockResponse $knockResponse ) => 
        {
            $knockRequest = $knockResponse->getRequest();

            $this->addYandexLog( $this->getLogDataByRequest( $knockRequest ) );
        };
    }
    
    /**
      * @param KnockRequest $knockRequest
      * 
      * @return array
      */
    private function getLogDataByRequest( KnockRequest $knockRequest ): array
    {
        return [
            'url' => $knockRequest->getUrl(),
            'method' => $knockRequest->getMethod(),
            'data' => $knockRequest->getData(),
            'headers' => $knockRequest->getHeaders(),
        ];
    }

    /**
     * @param array $params
     * 
     * @return void
     */
    private function addYandexLog( array $params ): bool
    {
        return $logger->log($params);
    }
}

```
Пример использования custom реализации
```php

$knockKnockYandex = KnockKnockYandex::getInstanse([
    KnockKnockYandex::LOGGER => new YandexLogger(),
]);

$knockResponse = $knockKnockYandex->setupRequest('profile', [ 
    KnockRequest::METHOD => KnockMethod::PATCH,
    KnockRequest::DATA => [ 'city' => 'Moscow' ],
]); // Логирование `afterCreateRequest`

$knockResponse = $knockKnockYandex->send(); // Логирование `afterSend`

```

# Расширение функционала

TODO:ПЕРЕПИМСЫВАЮ НА ПРИМЕР С ПОДСТАВНОВКОЙ token исходя из хоста

 Расширения работают через "магию", поэтому лучше описывать их в анотациях класса

Реализация расширений
```php
/**
 * @method static setupCorrectAuth( KnockKnock $knockKnock )
 */
class VkontakteKnockKnock extends KnockKnock
{
    public function init()
    {
        $this->addExtension( 'setupCorrectAuth', fn( KnockKnock $knockKnock ) => 
        {
            $this->setupCorrectHostHandler($knockKnock);
        });
    }
    
    
    private function setupCorrectAuth(KnockKnock $knockKnock)
    {
         switch ($knockKnock->host)
            {
                case 'vk.com':
                    $knockKnock->useHeaders(['Host' => 'client.ru']);
                    break;

                case 'api.vk.com':
                    $knockKnock->useAuthorization( 'myToken', KnockKnock::TOKEN_BEARER );
                    break;
            }
    }
}
```

Использование расширения
```php
$vkontakteKnockKnock = VkontakteKnockKnock::getInstance([
    KnockRequest::HOST => 'api.vk.com',
]);

$vkontakteKnockKnock->setupCorrectHost();

$knockResponse = $vkontakteKnockKnock->setRequest('profile', [
    KnockRequest::METHOD => KnockMethod::PATCH,
    KnockRequest::DATA => [ 'homepage' => 'www.andy87.ru' ],
])->send();

```
