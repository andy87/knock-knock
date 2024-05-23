# Вводная.

Репозиторий содержит 3 класса, применяющих паттерн Фасад,  
для реализации оправки запроса через php расширение cURL.

Цель: сделать простой и лёгкий в настройке пакет для реализации разных API на его основе.

Я знаю про существование таких библиотек как: [Guzzle](https://github.com/guzzle/guzzle), [Client](https://github.com/yiisoft/yii2-httpclient) _(в моём любимом Yii2)_, но хотелось попробовать создать свою реализацию.
Без "лишних" данных, вызовов и настроек - только то, что нужно. Разумеется, это не конкурент, а просто попытка создать что-то своё.

### Требования:
 - php 8.0
 - ext cURL
 - ext JSON

# Установка.

### Git

В файл вашего проекта `composer.json` добавьте:  
 в раздел `require`  строку `"andy87/knockknock": "dev-main"`  
 в раздел `repositories`  
```
{
    "type": "vcs",
    "url": "https://github.com/andy87/KnockKnock"
}
```
Выполните команду: `composer update`.  
Возможно придётся так же добавить в корень данных `composer.json`  
`"minimum-stability": "dev"`

### Composer.

Установка через [composer](https://getcomposer.org/download/)

Либо запустите

`composer require --prefer-dist andy87/KnockKnock`  
`php composer.phar require --prefer-dist andy87/KnockKnock`

Либо добавьте в раздел `require` вашего `composer.json` файла строку

`"yiisoft/yii2-httpclient": "~2.0.0"`

После выполните команду `php composer.phar update` либо `composer update`

___

# KnockKnock.

<p style="text-align: center"><img src="assets/docs/KnockKnockLogo_256.png" style="width:164px; height: auto" alt="KnockKnock php curl facade"/></p>

## Базовый класс: 
_use [andy87\knock_knock\core\KnockKnock](src/core/KnockKnock.php);_  

PHP Фасад\Адаптер для отправки запросов через ext cURL

Возможности/фичи:
 - Настройка параметров запросов
   - см. `Полный список констант`
 - Обработчики событий
   - см. `Список событий`
 - доступна возможность использовать Singleton
 - применяется инкапсуляция
 - защита данных от перезаписи

### ВАЖНЫЙ МОМЕНТ!
`CURL_OPTIONS` по умолчанию пустые! В большинстве случаев требуется задать необходимые настройки для получения валидных ответов.  
См. пример ниже.

В классах применяется инкапсуляция, поэтому для доступа к свойствам компонентов необходимо использовать сеттеры и геттеры.

## Получение объекта/экземпляра класса и его настройка

### Нативный
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'],[
     KnockRequestInterface::SETUP_CURL_OPTIONS => [
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true
    ]
]);
```

### Singleton
```php
$knockKnock = KnockKnock::getInstance( $_ENV['API_HOST'],[
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

 <p style="text-align:center"> - - - - - </p>

# Запрос.
_use [andy87\knock_knock\core\KnockRequest](src/core/KnockRequest.php);_  

Нативное создание объекта / экземпляра класса с данными для конкретного запроса
```php
$knockRequest = new KnockRequest( 'info/me', [
    KnockRequest::METHOD => LibKnockMethod::POST,
    KnockRequest::DATA => [ 'client_id' => 34 ],
    KnockRequest::HEADERS => [ 'api-secret-key' => $_ENV['API_SECRET_KEY'] ],
    KnockRequest::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    KnockRequest::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    KnockRequest::CONTENT_TYPE => LibKnockContentType::FORM_DATA,
]);
```

Доступно создание - через метод фасада (с вызовом callback функции)
```php
$knockRequest = $knockKnock->constructKnockRequest( 'info/me', [
    KnockRequest::METHOD => LibKnockMethod::POST,
    KnockRequest::DATA => [ 'client_id' => 45 ],
    KnockRequest::HEADERS => [ 'api-secret-key' => $_ENV['API_SECRET_KEY'] ],
    KnockRequest::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    KnockRequest::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    KnockRequest::CONTENT_TYPE => LibKnockContentType::FORM_DATA,
]);
```
`constructKnockRequest( string $url, array $knockRequestConfig = [] ): KnockRequest`

### Назначение/Изменение/Получение отдельных параметров запроса (set/get)

Таблица set/get методов для взаимодействия с отдельными свойствами запроса

| Параметр        | Сеттер                                | Геттер                   | Информация                                                                                                                                                                    |
|-----------------|---------------------------------------|--------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Протокол        | setProtocol( string $protocol )       | getProtocol(): string    | <a href="https://curl.se/docs/protdocs.html" target="_blank">протоколы</a>                                                                                                    |
| Хост            | setHost( string $host )               | getHost(): string        | ---                                                                                                                                                                           |
| Endpoint        | setEndpoint( string $url )            | getEndpoint(): string    | ---                                                                                                                                                                           |
| Метод           | setMethod( string $method )           | getMethod(): string      | <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods" target="_blank">методы</a>                                                                                |
| Заголовки       | setHeaders( array $headers )          | getHeaders(): array      | <a href="https://ru.wikipedia.org/wiki/%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA_%D0%B7%D0%B0%D0%B3%D0%BE%D0%BB%D0%BE%D0%B2%D0%BA%D0%BE%D0%B2_HTTP" target="_blank">заголовки</a>  |
| Тип контента    | setContentType( string $contentType ) | getContentType(): string | <a href="https://ru.wikipedia.org/wiki/%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA_MIME-%D1%82%D0%B8%D0%BF%D0%BE%D0%B2" target="_blank">Тип контента</a>                             |
| Данные          | setData( mixed $data )                | getData(): mixed         | ---                                                                                                                                                                           |
| Опции cURL      | setCurlOptions( array $curlOptions )  | getCurlOptions(): array  | <a href="https://www.php.net/manual/ru/function.curl-setopt.php" target="_blank">Опции cURL</a>                                                                               |
| Информация cURL | setCurlInfo( array $curlInfo )        | getCurlInfo(): array     | <a href="https://www.php.net/manual/ru/function.curl-getinfo.php" target="_blank">Информация cURL</a>                                                                         |

```php
$knockRequest = $knockKnock->constructKnockRequest('info/me');

$knockRequest->setMethod( LibKnockMethod::GET );
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
$knockRequest->setContentType( LibKnockContentType::JSON );

$protocol = $knockRequest->getPrococol(); // String
$host = $knockRequest->getHost(); // String
// ... аналогичным образом доступны и другие подобные методы для получения свойств запроса
```

### Микс параметров создаваемого запроса с данными переданными опционально

Можно создать запрос, на основе уже созданного объекта
и дополнительным аргументом передать уникальные собственные параметры.
```php
$knockKnock->setupRequest( $knockRequest, [
    KnockRequest::HOST => $_ENV['API_HOST'],
    KnockKnock::HEADERS => [
        'api-secret' => $_ENV['API_SECRET_KEY']
    ],
]);
```
`setupRequest( KnockRequest $knockRequest, array $options = [] ): self`

 <p style="text-align:center"> - - - - - </p>

# Ответ.
_use [andy87\knock_knock\core\KnockResponse](src/core/KnockResponse.php);_  

Конструктор `KnockResponse` с вызовом callback функции, если она установлена  
```php
$knockResponse = $knockKnock->constructKnockResponse([
    KnockResponse::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
    KnockResponse::HTTP_CODE => curl_getinfo( $ch, CURLINFO_HTTP_CODE ),
], $knockRequest );
```
`constructKnockResponse( array $KnockResponseParams, ?KnockRequest $knockRequest = null ): KnockResponse`  
ReadOnly свойства ответа:
 - content
 - httpCode
 - request
 - curlOptions
 - curlInfo

## KnockResponse: Отправка запроса и получение ответа

Отправить запрос, получить ответ и вызвать все callback функции, если они установлены
```php
$knockKnock->setupRequest( $knockRequest );
$knockResponse = $knockKnock->send();
```
`send( array $kafeResponse = [] ): KnockResponse`
возвращает объект/экземпляр класса `KnockResponse`

Пример получения ответа с отправкой запроса - цепочкой вызовов (субъективно - более красивый вариант)
```php
$knockResponse = $knockKnock->setRequest( $knockRequest )->send(); // return KnockResponse

$content = $knockResponse->content;
```

## Отправка запроса с фэйковым ответом

Цепочка вызовов, возвращает подготовленный ответ и вызывает callback функцию, если она установлена
```php
// параметры возвращаемого ответа
$fakeResponse = [
    KnockResponse::HTTP_CODE => 200,
    KnockResponse::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
];

$knockResponse = $knockKnock->setupRequest( $knockRequest )->send( $fakeResponse );
```
объект `$knockResponse` будет содержать данные переданные в аргументе `$fakeResponse`


## Данные в ответе

В созданный объект `KnockResponse` разрешено задавать данные.
```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

$knockResponse
    ->setHttpCode(200)
    ->setContent('{"id" => 8060345, "nickName" => "and_y87"}');
```
**Внимание!** Если данные в объекте уже существуют, повторно задать их нельзя выбрасывается `Exception`.  
В случае необходимости заменить данные, используется вызов метода `replace( string $key, mixed $value )` см. далее

### Подмена данных
```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

$knockResponse
    ->replace( KnockResponse::HTTP_CODE, 200 )
    ->replace( KnockResponse::CONTENT, '{"id" => 8060345, "nickName" => "and_y87"}' );
```

## Получение из ответа данных о запросе

Получение компонента запроса
```php
$knockRequest = $knockResponse->request
```
`request` - readOnly свойство

Получение отдельных свойств значений используя константы
```php
// Получение опций запроса (  KnockRequest::CURL_OPTIONS )
$curlOptions =  $knockResponse->get( KnockResponse::CURL_OPTIONS ); // return array

// Получение данных о запросе ( KnockRequest::CURL_INFO )
$curlInfo =  $knockResponse->get( KnockResponse::CURL_INFO ); // return array

```

## Функциональная часть

### SSL
В объектах `KnockKnock` & `KnockRequest` имеется функционал включения/отключения SSL верификации.  

В `curlOptions` добавляется ключ `CURLOPT_SSL_VERIFYPEER`.

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

# Расширения на основе базового класса

### [KnockKnockOctopus](docs/KnockKnock/KnockKnockOctopus.md)

<p style="text-align:center"><a href="docs/KnockKnock/KnockKnockOctopus.md"><img src="assets/docs/KnockKnockOctopus_320.png" style="width:200px; height: auto" alt="KnockKnock php curl facade"/></a></p>

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
 <p style="text-align:center"> - - - - - </p>

### [KnockKnockSecurity](docs/KnockKnock/KnockKnockSecurity.md)

<p style="text-align: center"><a href="docs/KnockKnock/KnockKnockSecurity.md"><img src="assets/docs/KnockKnockSecurity_280.png" style="width:auto; height: 128px" alt="KnockKnock php curl facade"/></a></p>

Класс с функционалом для быстрой настройки авторизации.

___

# Custom реализация

Custom реализация Базового класса, к примеру с добавлением логирования работающим "под капотом"
```php
class KnockKnockYandex extends KnockKnock
{
    private const LOGGER = 'logger';


    private string $host = 'https://api.yandex.ru/'

    private string $contentType = LibKnockContentType::JSON

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

$knockKnockYandex = KnockKnockYandex::getInstanсe( $_ENV['API_HOST'], [
    KnockKnockYandex::LOGGER => new YandexLogger(),
]);

$knockResponse = $knockKnockYandex->setupRequest( 'profile', [ 
    KnockRequest::METHOD => LibKnockMethod::PATCH,
    KnockRequest::DATA => [ 'city' => 'Moscow' ],
]); // Логирование `afterCreateRequest`

$knockResponse = $knockKnockYandex->send(); // Логирование `afterSend`

```

## Лицензия

https://github.com/andy87/KnockKnock под лицензией CC BY-SA 4.0  
Для получения дополнительной информации смотрите http://creativecommons.org/licenses/by-sa/4.0/  
Для не коммерческое использование - FREE  
Для коммерческого использования -  с указанием авторства  

---
> ## 🚧 Альфа версия
> Возможно наличие багов
---
