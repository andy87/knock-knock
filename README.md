
<h1 align="center">KnockKnock</h1>

<p align="center"><img src="assets/logo/KnockKnockLogo_256.png" style="width:256px; height: auto" alt="KnockKnock php curl facade"/></p>

KnockKnock - это простая библиотека, реализующая Фасад и предоставляющая удобный интерфейс для выполнения запросов в PHP,
используя расширение cURL.  Она упрощает работу, предоставляя более высокоуровневый API и быстрый доступ к настройкам.

Цель: сделать простой и лёгкий в настройке компонента и запроса пакет для реализации разных API на его основе.

P.S. я знаю про существование таких библиотек как: [Guzzle](https://github.com/guzzle/guzzle), [Client](https://github.com/yiisoft/yii2-httpclient) _(в моём любимом Yii2)_, но хотелось попробовать создать свою реализацию.  
Без "лишних" данных, вызовов и настроек - только то, что нужно: сухо, лаконично, минималистично.  
_Разумеется, это не конкурент, а просто попытка создать что-то своё_

___

<h2 align="center" id="knockknock-setup">
    Установка
</h2>


<h3 id="knockknock-setup-require">
    Требования
</h3>

- php 8.0
- ext cURL
- ext JSON


<h3 id="knockknock-setup-composer">
    <a href="https://getcomposer.org/download/">Composer</a>
</h3>

## Добавление пакета в проект

<h3 id="knockknock-setup-composer-cli">
    Используя: консольные команды. <i>(Предпочтительней)</i>
</h3>

- при composer, установленном локально:
```bash
composer require andy87/KnockKnock
````  
- при использовании composer.phar:
```bash
php composer.phar require andy87/KnockKnock
```
Далее: **Обновление зависимостей Composer**



<h3 id="knockknock-setup-composer-composer-json">
    Используя: файл `composer.json`
</h3>

Открыть файла `composer.json`  
В раздел по ключу `require` добавить строку  
`"andy87/KnockKnock": ">=1.0.0"`  
Далее: <a href="#knockknock-setup-composer-composer-update">Обновление зависимостей Composer</a>



<h3 id="knockknock-setup-composer-composer-json">
    Подключение <a href="https://git-scm.com/andy87/KnockKnock">Git</a> репозитория
</h3>

В файл вашего проекта `composer.json`:
- добавьте в раздел `require` строку `"andy87/knockknock": ">=1.0.0"`
- добавьте в раздел `repositories` новый объект:
```
{
    "type": "vcs",
    "url": "https://github.com/andy87/KnockKnock"
}
```
Далее: <a href="#knockknock-setup-composer-composer-update">Обновление зависимостей Composer</a>



<h3 id="knockknock-setup-composer-composer-update">
    Обновление зависимостей Composer
</h3>

Выполните в консоли (находясь в корневом каталоге вашего проекта) одну из команд:
- при composer, установленном локально:
```bash
composer update
````  
- при использовании composer.phar:
```bash
php composer.phar update
```

<p align="center">- - - - -</p>


<h2 id="knockknock-setup-composer-autoload">
    Используя: подключение авто загрузчика
</h2>

В месте, где необходимо использовать библиотеку, подключите авто загрузчик:
```php
require_once 'путь/к/корню/проекта/autoload.php';

```
**Примечания:**
- Убедитесь, что путь к autoload.php правильный и соответствует структуре вашего проекта.


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h2 id="knockknock-logic-schema">
    Логика работы библиотеки (блок-схема)  
</h2>

<p align="center">
    <img src="assets/logicKnockKnock.png" id="knockknock-logic-schema-img" width="640px" alt="логика схемы работы приложения">
</p>


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h2 id="knockknock-src-KnockKnock">
    Базовый класс
</h2>

_use [andy87\knock_knock\core\KnockKnock](src/core/KnockKnock.php);_  

PHP Фасад\Адаптер для отправки запросов через ext cURL

<h3 id="knockknock-src-KnockKnock-readonly">
    ReadOnly свойства:
</h3>

- **commonKnockRequest** 
  - _Объект содержащий параметры, назначаемые всем исходящим запросам_
- **realKnockRequest** 
  - _Используемый запрос_
- **eventHandlers** 
  - _Список обработчиков событий_
- **host** 
  - _Хост, на который будет отправляться запросы_
- **logs** 
  - _Список логов_

Возможности/фичи:
 - Настройки параметров запросов
 - Защита данных от перезаписи
 - Обработчики событий
 - Инкапсуляция
 - Singleton
 - логирование
  
#### ВАЖНЫЙ МОМЕНТ!  
- В классах применяется инкапсуляция, поэтому для доступа к свойствам компонентов используются ReadOnly свойства.  
- `CURL_OPTIONS` по умолчанию пустые! В большинстве случаев, для получения валидных ответов, требуется задать необходимые настройки.   



<h2 align="center" id="knockknock-src-KnockKnock-construct">
    "Получение" объекта/экземпляра класса
</h2>

Передавая параметры напрямую в конструктор:
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'], $knockKnockConfig );
``` 
Применяя, паттерн Singleton:
```php
$knockKnock = KnockKnock::getInstance( $_ENV['API_HOST'], $knockKnockConfig );
```
Методы возвращают объект(экземпляр класса `KnockKnock`), принимая на вход два аргумента:
- `string $host` - хост
- `array $knockKnockConfig` - массив с настройками для всех исходящих запросов.

При создании объекта `KnockKnock` будет вызван метод `init()`, который запускает пользовательские инструкции.  
После выполнения `init()` запускается обработчик события привязанный к ключу `EVENT_AFTER_CONSTRUCT`

<h2 align="center" id="knockknock-src-KnockKnock-params">
  Общие настройки запросов
</h2>
Что бы указать настройки применяемые ко всем исходящим запросам,  
при создании объекта `KnockKnock` передаётся массив (ключ - значение), с необходимыми настройками.

Пример настройки:
```php
// настройки для последующих исходящих запросов
$knockKnockParams = [
    KnockRequest::SETUP_PROTOCO => $_ENV['API_PROTOCOL'],
    KnockRequest::SETUP_CONTENT_TYPE => KnockRequest::CONTENT_TYPE_JSON,
    KnockRequest::SETUP_CURL_OPTIONS => [
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true
    ]
];
// Получаем компонент для отправки запросов
$knockKnock = new KnockKnock( $_ENV['API_HOST'], $knockKnockParams );

//Применяя, паттерн Singleton:
$knockKnock = KnockKnock::getInstance( $_ENV['API_HOST'], $knockKnockParams );
```
Доступные ключи для настройки(константы класса `KnockRequest`):

- `SETUP_PROTOCOL`
- `SETUP_HOST`
- `SETUP_METHOD`
- `SETUP_HEADERS`
- `SETUP_CONTENT_TYPE`
- `SETUP_DATA`
- `SETUP_CURL_OPTIONS`
- `SETUP_CURL_INFO`


<h2 id="knockknock-src-KnockKnock-eventHandlers">
    Обработчики событий
</h2>

<h3 id="knockknock-src-KnockKnock-event-list">
    Список событий
</h3>

- `EVENT_AFTER_CONSTRUCT` после создания объекта knockKnock
- `EVENT_CREATE_REQUEST` после создания объекта запроса
- `EVENT_BEFORE_SEND` перед отправкой запроса
- `EVENT_CURL_HANDLER` перед отправкой curl запроса
- `EVENT_CREATE_RESPONSE` после создания объекта ответа
- `EVENT_AFTER_SEND` после получения ответа

<h5 id="knockknock-src-KnockKnock-events-example">
    Пример установки обработчиков событий
</h5>

```php
$knockKnock->setupEventHandlers([
    KnockKnock::EVENT_AFTER_CONSTRUCT => function( static $knockKnock ) => {
        // создание объекта knockKnock, для взаимодействия с $knockKnock
    },
    KnockKnock::EVENT_CREATE_REQUEST => function( static $knockKnock, KnockRequest $knockRequest ) => {
        // создание объекта запроса, для взаимодействия с $knockRequest
    },
    KnockKnock::EVENT_BEFORE_SEND => function(  static $knockKnock, KnockRequest $knockRequest ) => {
        // отправка запроса, для взаимодействия с $knockRequest
    },
    KnockKnock::EVENT_CURL_HANDLER => function( static $knockKnock, resource $ch ) => {
        // перед отправкой curl запроса, для взаимодействия с $ch
    },
    KnockKnock::EVENT_CREATE_RESPONSE => function( static $knockKnock, KnockResponse $knockResponse ) => {
        // создание объекта ответа, для взаимодействия с $knockResponse
    },
    KnockKnock::EVENT_AFTER_SEND => function( static $knockKnock, KnockResponse $knockResponse ) => {
        // получение ответа, для взаимодействия с $knockResponse
    }
]);
```
Первый аргумент - ключ события, второй - callback функция.

Все callback функции принимают первым аргументом объект/экземпляр класса `KnockKnock`.  
Вторым аргументом передаётся объект/экземпляр класса в зависимости от события:
- `KnockRequest` - для событий `EVENT_CREATE_REQUEST`, `EVENT_BEFORE_SEND`
- `KnockResponse` - для событий `EVENT_CREATE_RESPONSE`, `EVENT_AFTER_SEND`


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 align="center">Запрос</h1>

_use [andy87\knock_knock\core\KnockRequest](src/core/KnockRequest.php);_  

Объект запроса, содержащий данные для отправки запроса.

<h3 id="knockknock-src-KnockRequest-readonly">
    ReadOnly свойства:
</h3>

- **protocol** - _протокол_
- **host** - _хост_
- **endpoint** - _конечная точка_
- **method** - _метод_
- **headers** - _заголовки_
- **contentType** - _тип контента_
- **data** - _данные_
- **curlOptions** - _опции cURL_
- **curlInfo** - _информация cURL_
- **params** - _параметры запроса_
- **url** - _полный URL_
- **params** - _все свойства в виде массива_

<h3 align="center" id="knockknock-src-KnockRequest-construct">
    Создание объекта запроса
</h3>

Передавая параметры напрямую в конструктор:
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
Методом, который вызывает _callback_ функцию, привязанную к ключу `EVENT_CREATE_REQUEST`
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
Клонируя существующий объект запроса:
```php
$knockRequest = $knockKnock->constructKnockRequest( 'info/me' );

$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

//Клонирование объекта запроса (без статуса отправки)
$cloneKnockRequest = $knockRequest->clone();

// Отправка клона запроса
$knockResponse = $knockKnock->setupRequest( $cloneKnockRequest )->send();
```

<h3 id="knockknock-src-KnockRequest-setter-getter">
    Назначение/Изменение/Получение отдельных параметров запроса (set/get)
</h3>

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
<h3 id="knockknock-src-KnockRequest-setupRequest">
    Назначение запроса с переназначением свойств
</h3>

```php
$knockKnock->setupRequest( $knockRequest, [
    KnockRequest::HOST => $_ENV['API_HOST'],
    KnockKnock::HEADERS => [
        'api-secret' => $_ENV['API_SECRET_KEY']
    ],
]);
```
`setupRequest( KnockRequest $knockRequest, array $options = [] ): self`


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 align="center">Ответ</h1>

_use [andy87\knock_knock\core\KnockResponse](src/core/KnockResponse.php);_  

Объект ответа, содержащий данные ответа на запрос.
<h3 id="knockknock-src-KnockResponse-readonly">
    ReadOnly свойства
</h3>

- **content**
  - _данные ответа_
- **httpCode**
  - _код ответа_
- **request**
  - _объект запроса, содержащий данные о запросе_
- **curlOptions**
  - _быстрый доступ к request->curlOptions_
- **curlInfo**
  - _быстрый доступ к request->curlInfo_

<h3 align="center" id="knockknock-src-KnockResponse-construct">
    Создание объекта ответа
</h3>

Передавая параметры напрямую в конструктор:
```php
$knockResponse = new KnockResponse('{"id" => 806034, "name" => "and_y87"}', 200 );
```
Методом, который вызывает _callback_ функцию, привязанную к ключу `EVENT_CREATE_RESPONSE`
```php
$knockResponse = $knockKnock->constructKnockResponse([
    KnockResponse::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
    KnockResponse::HTTP_CODE => 400,
], $knockRequest );
```
`constructKnockResponse( array $KnockResponseParams, ?KnockRequest $knockRequest = null ): KnockResponse`

<h2 id="knockknock-src-KnockKnock-send">
    Отправка запроса
</h2>

`send( array $kafeResponse = [] ): KnockResponse`  
Метод требует наличие объекта запроса установленного методом `setupRequest( KnockRequest $knockRequest )`.  

Вызов метода `send()`, возвращает объект/экземпляр класса `KnockResponse`.  
Срабатывает callback функция, привязанная к ключу:
 - `EVENT_AFTER_SEND`
 - `EVENT_CREATE_RESPONSE`
 - `EVENT_BEFORE_SEND`
 - `EVENT_CURL_HANDLER`

```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );

$knockRequest = $knockKnock->constructKnockRequest( 'info/me' );

$knockKnock->setupRequest( $knockRequest );

$knockResponse = $knockKnock->send();
```

Если запрос уже был отправлен, повторно отправить его нельзя, выбрасывается `Exception`.  
Для повторной отправки запроса, необходимо создать новый объект запроса:
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );

$knockRequest = $knockKnock->constructKnockRequest( 'info/me' );

$knockKnock->setupRequest( $knockRequest );

$knockResponse = $knockKnock->send();

// повторная отправка запроса
$knockResponse = $knockKnock->setupRequest( $knockRequest->clone() )->send();
```

<h4 id="knockknock-src-KnockKnock-chain-call">
    Цепочка вызовов
</h4>

Субъективно - более красивый вариант. Пример получения ответа - цепочкой вызовов.  
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );

$knockRequest = $knockKnock->constructKnockRequest( 'info/me' );

//Цепочка вызовов
$knockResponse = $knockKnock->setRequest( $knockRequest )->send();

$content = json_decode($knockResponse->content, true);
```
_Разумеется можно миксовать codeStyle кому как больше нравиться_

<h2 id="knockknock-src-KnockKnock-fakeResponse">
    Отправка запроса с фэйковым ответом
</h2>

Получение подготовленного(фэйкового) ответа
```php
// параметры возвращаемого ответа
$fakeResponse = [
    KnockResponse::HTTP_CODE => 200,
    KnockResponse::CONTENT => '{"id" => 8060345, "nickName" => "and_y87"}'
];

$knockResponse = $knockKnock->setupRequest( $knockRequest )->send( $fakeResponse );
```
объект `$knockResponse` будет содержать в свойствах `content`, `httpCode` данные переданные в аргументе `$fakeResponse`

<h2 id="knockknock-src-KnockResponse-setter">
    Данные в ответе
</h2>

В созданный объект `KnockResponse`, чей запрос не был отправлен, разрешено задавать данные, используя методы группы `set`.  
```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

$knockResponse
    ->setHttpCode(200)
    ->setContent('{"id" => 8060345, "nickName" => "and_y87"}');
```
**Внимание!** Если данные в объекте уже существуют, повторно задать их нельзя выбрасывается `Exception`.  
В случае необходимости заменить данные, используется вызов метода `replace( string $key, mixed $value )` см. далее

<h3 id="knockknock-src-KnockResponse-replace">
    Подмена данных
</h3>

```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

$knockResponse
    ->replace( KnockResponse::HTTP_CODE, 200 )
    ->replace( KnockResponse::CONTENT, '{"id" => 8060345, "nickName" => "and_y87"}' );
```

<h2 id="knockknock-src-KnockResponse-request">
    Данные запроса из ответа
</h2>

Для получения в объекте `KnockResponse` данных запроса, необходимо обратиться к свойству `request`  
и далее взаимодействовать с ним аналогично объекту `KnockRequest`  

Получение компонента запроса:
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );
$knockResponse = $knockKnock->setRequest( $knockKnock->constructKnockRequest( 'info/me' ) )->send();

$request = $knockResponse->request;

$method = $request->method;
```

Получения свойств cURL запроса 
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );
$knockResponse = $knockKnock->setRequest( $knockKnock->constructKnockRequest( 'info/me' ) )->send();

$knockResponse->request;

// Получение свойств через объект запроса
$curlOptions =  $knockResponse->request->curlOption;
$curlInfo =  $knockResponse->request->curlInfo;

//Вариант с использованием быстрого доступа
$curlOptions =  $knockResponse->curlOption;
$curlInfo =  $knockResponse->curlInfo;
```
<h3 id="knockknock-src-KnockResponse-asArray">
    asArray()
</h3>

Преобразование ответа в массив
```php
$knockResponse = $knockKnock->setupRequest( $knockRequest )->asArray()->send();
$array = $knockResponse->content; // Array
```


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 align="center" id="knockknock-src-feature">
    Функциональная часть
</h1>

<h3 id="knockknock-src-ssl">
    SSL
</h3>

Функционал включения/отключения SSL верификации в объектах `KnockKnock` & `KnockRequest`.  

В `curlOptions` добавляется ключ `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST`.

`->disableSSL( bool $verifyPeer = false, int $verifyHost = 0 );`  
`->enableSSL( bool $verifyPeer = true, int $verifyHost = 2 );`  

`KnockKnock` - для всех запросов
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );
$knockKnock->disableSSL();

$knockRequest = $knockKnock->constructKnockRequest( 'info/me' );

$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();
```

`KnockRequest` - для конкретного запроса  
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] )->disableSSL();

$knockRequest = $knockKnock->constructKnockRequest( 'info/me' );
$knockRequest->enableSSL();

$knockResponse = $knockKnock->setupRequest( $knockRequest )->send();
```
<h3 id="knockknock-src-Cookie">
    Cookie
</h3>

В объекте `KnockKnock` имеется функционал использования cookie.  
`KnockKnock` - для всех запросов  
```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );

$cookie = $_ENV['COOKIE'];
$jar = $_ENV['COOKIE_JAR'];

$knockKnock->useCookie( $cookie, $jar );
```  
`$knockKnock->useCookie( string $cookie, string $jar, ?string $file = null )`  
по умолчанию `$file = null` и  `$file` приравнивается к `$jar`  

<h3 id="knockknock-src-logs">
    Логирование
</h3>

Добавление сообщений в свойство `->logs` 

```php
$knockKnock = new KnockKnock( $_ENV['API_HOST'] );

$$message = 'Какое то сообщение';

$knockKnock->addLog( $message );
```
`$knockKnock->addLog( string $message )`  


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 align="center" id="knockknock-extends">Расширения на основе базового класса</h1>

<h3 align="center" id="knockknock-Octopus">
    <a href="docs/KnockKnock/KnockKnockOctopus.md" target="_blank">
        KnockKnockOctopus
        <br>
        <img src="assets/logo/KnockKnockOctopus_320.png" style="width:200px; height: auto" alt="KnockKnockOctopus php curl facade"/>
    </a>
</h3>

Класс с функционалом простой реализации отправки запросов и минимальными настройками

<h4 id="knockknock-Octopus-methods">
    Доступные методы.
</h4>

| get() | post() | put() | patch() | delete() | head() | options() | trace() |
|-------|--------|-------|---------|----------|--------|-----------|---------|

<h4 id="knockknock-Octopus-methods-args">
    Каждый метод принимает два аргумента:
</h4>

| Аргумент  |   Тип   | Обязательный  | Описание                       |
|:----------|:-------:|:-------------:|:-------------------------------|
| $endpoint | string  |      Да       | URL запроса (без хоста)        |
| $params   |  array  |      Нет      | Данные запроса в виде массива  |
_P.S. host задаётся в конструкторе_

<h4 id="knockknock-Octopus-methods-example">
    Простой пример использования
</h4>

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

<h3 align="center" id="knockknock-security">
    <a href="docs/KnockKnock/KnockKnockSecurity.md" target="_blank">
        KnockKnockSecurity
        <br>
        <img src="assets/logo/KnockKnockSecurity_280.png" style="width:auto; height: 128px" alt="KnockKnockSecurity php curl facade"/>
    </a>
</h3>

Расширяет класс [KnockKnockOctopus](docs/KnockKnock/KnockKnockOctopus.md), предоставляя доступ к функционалу для простой и  
быстрой реализации авторизации, и настройки запросов.

```php
$knockKnockSecurity = new KnockKnockSecurity($_ENV['API_URL']);

// Настройка параметров запроса по умолчанию
$knockKnockSecurity
    ->disableSSL()
    ->setupAuthorization( KnockKnockSecurity::TOKEN_BEARER, 'token' )
    ->setupHeaders([ 'X-Api-Key' => $_ENV['X_API_KEY'] ])
    ->setupContentType( LibKnockContentType::JSON )
    ->on( KnockKnock::EVENT_AFTER_SEND, function( KnockKnock $knockKnock, KnockResponse $knockResponse ) => 
    {
        $logFilePath = $_SERVER['DOCUMENT_ROOT'] . '/api_log.txt';

        file_put_contents( $logFilePath, $knockResponse->content, FILE_APPEND );
    });

// Получение ответа на запрос методом `patch`
$KnockResponsePatch = $knockKnockSecurity->patch( 'product', [
    'price' => 1000
]);

$product = $KnockResponsePatch->asArray();

$price = $product['price'];

// Изменение типа контента на `application/json`, для следующего запроса
$knockKnockSecurity->useContentType( LibKnockContentType::JSON );

// Отправка POST запроса и получение ответа
$KnockResponsePost = $knockKnockSecurity->post( 'category', [
    'name' => 'Фреймворки'
]);

$response = json_decode( $KnockResponsePost->content );

$category_id = $response->id;

```


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 id="knockknock-Custom">
    Custom реализация
</h1>

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
        $this->on( self::AFTER_CREATE_REQUEST, function( KnockRequest $knockRequest ) => 
        {
            $logData = $this->getLogDataByRequest( $knockRequest );

            $this->addYandexLog( $logData );
        };

        $this->on(self::EVENT_AFTER_SEND, function( KnockResponse $knockResponse ) => 
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
<h3 id="knockknock-Custom-use">
    Пример использования custom реализации
</h3>

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


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h2 id="knockknock-tests">
    Тесты
</h2>

 - tests: 105
 - assertions: 367

<h3 id="knockknock-tests-run">
    Запуск тестов:
</h3>

Нативный  
```bash
vendor/bin/phpunit
```  
Информационный  
```bash
vendor/bin/phpunit --testdox
```  
С логированием  
```bash
vendor/bin/phpunit --log-junit "tests/logs/phpunit.xml"
```

<h2 id="knockknock-license">
    Лицензия
</h2>

https://github.com/andy87/KnockKnock под лицензией CC BY-SA 4.0  
Для получения дополнительной информации смотрите http://creativecommons.org/licenses/by-sa/4.0/  
Свободно для не коммерческого использования  
С указанием авторства для коммерческого использования  

<h2 id="knockknock-changelog">
    Изменения
</h2>

Для получения полной информации смотрите [CHANGELOG](docs/CHANGELOG.md)

<h3 id="knockknock-changes">
    Последние изменения
</h3>

24/05/2024 - 99b  
26/05/2024 - v1.0.0  
25/05/2024 - v1.0.1  
{today}/05/2024 - v1.0.2  

[Packagist](https://packagist.org/packages/andy87/knockknock)
