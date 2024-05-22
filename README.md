
## Расширения на основе базового класа

### [KnockKnockOctopus](docs/KnockKnock/KnockKnockOctopus.md)

<p align="center"><a href="docs/KnockKnock/KnockKnockOctopus.md"><img src="assets/docs/KnockKnockOctopus_320.png" height="200" width="auto" alt="KnockKnock php curl facade"/></a></p>

Класс с функционалом простой реализации отправки запросов и минимальными настройками

#### Доступные методы.

| get() | post() | put() | patch() | delete() | head() | options() | trace() |
|-------|--------|-------|---------|----------|--------|-----------|---------|

#### Каждый метод принимает два аргумента:
| Аргумент  |   Тип   | Обязательный  | Описание                       |
|:----------|:-------:|:-------------:|:-------------------------------|
| $endpoint | string  |      Да       | URL запроса (без хоста)        |
| $params   |  array  |      Нет      | Данные запроса в виде массива  |
_P.S. host задаётся в конструкторе_

#### Простой пример использования
```php
//GET запрос
$knockKnockOctopus->get( '/profile', [ 'id' => 806034 ] );

//POST запрос
$knockKnockOctopus->post( '/new', [ 
    'name' => 'Новая новость',
    'content' => 'Текст новости' 
]);
```
 <p align="center"> - - - - - </p>

### [KnockKnockSecurity](docs/KnockKnock/KnockKnockSecurity.md)

<p align="center"><a href="docs/KnockKnock/KnockKnockSecurity.md"><img src="assets/docs/KnockKnockSecurity_280.png" height="128" width="auto" alt="KnockKnock php curl facade"/></a></p>

Класс с функционалом для быстрой настройки авторизации.

___

# KnockKnock

<p align="center"><img src="assets/docs/KnockKnockLogo_256.png" width="164" height="auto" alt="KnockKnock php curl facade"/></p>

## Базовый класс: _KnockKnock_

PHP Фасад\Адаптер для отправки запросов через ext cURL

Возможности:
 - Настройка параметров запросов
   - см. `Полный список констант`
 - Обработчики событий
   - см. `Список событий`

## ВАЖНЫЙ МОМЕНТ!
`CURL_OPTIONS` по умолчанию пустые! В большинстве случаев требуется задать необходимые настройки для получения валидных ответов.  
см. пример ниже.


Получение объекта/экземпляра класса и его настройка
### Нативный
```php
$knockKnock = new KnockKnock('host',[
     KnockRequestInterface::SETUP_CURL_OPTIONS => [
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true
    ]
]);
```

### Singleton
```php
$knockKnock = KnockKnock::getInstance('host',[
        KnockRequest::SETUP_PROTOCO => KnockRequest::PROTOCOL_HTTP,
        KnockRequest::SETUP_CONTENT_TYPE => KnockRequest::CONTENT_TYPE_JSON,
    ])
    ->disableSSL();
```
Оба вызова вернут объект/экземпляр класса `KnockKnock` и принимают на вход два аргумента:
- `string $host` - хост
- `array $knockKnockConfig` - массив с настройками, ключами которого являются константы класса `KnockRequest` имеющие префикс `SETUP_`.  
#### Полный список констант:
- `SETUP_PROTOCOL`
- `SETUP_HOST`
- `SETUP_URL`
- `SETUP_METHOD`
- `SETUP_HEADERS`
- `SETUP_CONTENT_TYPE`
- `SETUP_DATA`
- `SETUP_CURL_OPTIONS`
- `SETUP_CURL_INFO`


## Обработчики событий

### Список событий
- после создания объекта knockKnock
- после создания объекта запроса
- перед отправкой запроса
- после создания объекта ответа
- после получения ответа

##### Пример установки обработчиков событий
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
Первый аргумент - ключ события, второй - callback функция.

Все callback функции принимают первым аргументом объект/экземпляр класса `KnockKnock`.  
Вторым аргументом передаётся объект/экземпляр класса в зависимости от события:
- `KnockRequest` - для событий `EVENT_CREATE_REQUEST`, `EVENT_BEFORE_SEND`
- `KnockResponse` - для событий `EVENT_CREATE_RESPONSE`, `EVENT_AFTER_SEND`

 <p align="center"> - - - - - </p>

# Запрос: _KnockRequest_

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

| Параметр        | Сеттер                                | Геттер                   | Информация                                                                                                                                                                  |
|-----------------|---------------------------------------|--------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Протокол        | setProtocol( string $protocol )       | getProtocol(): string    | <a href="https://curl.se/docs/protdocs.html" target="_blank">протоколы</a>                                                                                                  |
| Хост            | setHost( string $host )               | getHost(): string        | ---                                                                                                                                                                         |
| URL             | setUrl( string $url )                 | getUrl(): string         | ---                                                                                                                                                                         |
| Метод           | setMethod( string $method )           | getMethod(): string      | <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods" target="_blank">методы</a>                                                                              |
| Заголовки       | setHeaders( array $headers )          | getHeaders(): array      | <a href="https://ru.wikipedia.org/wiki/%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA_%D0%B7%D0%B0%D0%B3%D0%BE%D0%BB%D0%BE%D0%B2%D0%BA%D0%BE%D0%B2_HTTP" target="_blank">загловки</a> |
| Тип контента    | setContentType( string $contentType ) | getContentType(): string | <a href="https://ru.wikipedia.org/wiki/%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA_MIME-%D1%82%D0%B8%D0%BF%D0%BE%D0%B2" target="_blank">Тип контента</a>                           |
| Данные          | setData( mixed $data )                | getData(): mixed         | ---                                                                                                                                                                         |
| Опции cURL      | setCurlOptions( array $curlOptions )  | getCurlOptions(): array  | <a href="https://www.php.net/manual/ru/function.curl-setopt.php" target="_blank">Опции cURL</a>                                                                             |
| Информация cURL | setCurlInfo( array $curlInfo )        | getCurlInfo(): array     | <a href="https://www.php.net/manual/ru/function.curl-getinfo.php" target="_blank">Информация cURL</a>                                                                       |

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

 <p align="center"> - - - - - </p>

## Ответ: _KnockResponse_ 

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

## Функциональная часть

### SSL
В объектах `KnockKnock` & `KnockRequest` имеется функционал включения/отключения SSL верификации.  

`KnockKnock` - для всех запросов
```php
$knockKnock->disableSSL();
$knockKnock->enableSSL();
```

`KnockRequest` - для конкретного запроса
```php
$knockRequest->disableSSL();
$knockRequest->enableSSL();

```



___

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
        $this->setupYandexLoggerEventHandlers();
    }
    
    /**
     * @param array $callbacks
     * 
     * @return self
     */
    private function setupYandexLoggerEventHandlers( array $callbacks ): self
    {
        $this->on( self::AFTER_CREATE_REQUEST, fn( KnockRequest $knockRequest ) => 
        {
            $logData = $this->getLogDataByRequest( $knockRequest );

            $this->addYandexLog( $logData );
        };

        $this->on(self::EVENT_AFTER_SEND, fn( KnockResponse $knockResponse ) => 
        {
            $logData = $this->getLogDataByRequest( $knockResponse->request );

            $this->addYandexLog( $logData );
        };
    }

    /**
      * @param KnockRequest $knockRequest
      * 
      * @return array
      */
    private function getLogDataByRequest( KnockRequest $knockRequest ): array
    {
        return $knockRequest->getParams();
    }

    /**
     * @param array $logData
     * 
     * @return void
     */
    private function addYandexLog( array $logData ): bool
    {
        return $logger->log( $logData );
    }
}

```
### Пример использования custom реализации
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

---
> [!Альфа версия]  
> Возможно наличие багов
---