
<h1 align="center">KnockKnock</h1>

<p align="center"><img src="assets/logo/KnockKnockLogo_256.png" style="width:256px; height: auto" alt="KnockKnock php curl facade"/></p>

KnockKnock - это простая библиотека, реализующая Фасад и предоставляющая удобный интерфейс для выполнения запросов в PHP,
используя расширение cURL.  Она упрощает работу, предоставляя более высокоуровневый API и быстрый доступ к настройкам.

Цель: сделать простой и лёгкий в настройке компонента и запроса пакет для реализации разных API на его основе.

P.S. я знаю про существование таких библиотек как: [Guzzle](https://github.com/guzzle/guzzle), [Client](https://github.com/yiisoft/yii2-httpclient) _(в моём любимом Yii2)_, но хотелось попробовать создать свою реализацию.  
Без "лишних" данных, вызовов и настроек, nullWarningStyle - только то, что нужно: сухо, лаконично, минималистично.  
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
    <img src="assets/logicHandler.png" id="knockknock-logic-schema-img" width="640px" alt="логика схемы работы приложения">
</p>


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h2 align="center" id="knockknock-src-Handler">
    Базовый класс
</h2>

_use [andy87\knock_knock\core\Handler](src/core/KnockKnock.php);_  

PHP Фасад\Адаптер для отправки запросов через ext cURL

<h3 id="knockknock-src-Handler-readonly">
    ReadOnly свойства:
</h3>

- **commonRequest** 
  - _Объект содержащий параметры, назначаемые всем исходящим запросам_
- **realRequest** 
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



<h2 align="center" id="knockknock-src-Handler-construct">
    "Получение" объекта/экземпляра класса
</h2>

Передавая параметры напрямую в конструктор:
```php
$knockKnock = new Handler( $_ENV['API_HOST'], $knockKnockConfig );
``` 
Применяя, паттерн Singleton:
```php
$knockKnock = Handler::getInstance( $_ENV['API_HOST'], $knockKnockConfig );
```
Методы возвращают объект(экземпляр класса `Handler`), принимая на вход два аргумента:
- `string $host` - хост
- `array $knockKnockConfig` - массив с настройками для всех исходящих запросов.

При создании объекта `Handler` будет вызван метод `init()`, который запускает пользовательские инструкции.  
После выполнения `init()` запускается обработчик события привязанный к ключу `EVENT_AFTER_CONSTRUCT`

<h2 align="center" id="knockknock-src-Handler-params">
  Общие настройки запросов
</h2>
Что бы указать настройки применяемые ко всем исходящим запросам,  
при создании объекта `Handler` передаётся массив (ключ - значение), с необходимыми настройками.

Пример настройки:
```php
// настройки для последующих исходящих запросов
$knockKnockParams = [
    Request::SETUP_PROTOCO => $_ENV['API_PROTOCOL'],
    Request::SETUP_CONTENT_TYPE => Request::CONTENT_TYPE_JSON,
    Request::SETUP_CURL_OPTIONS => [
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true
    ]
];
// Получаем компонент для отправки запросов
$knockKnock = new Handler( $_ENV['API_HOST'], $knockKnockParams );

//Применяя, паттерн Singleton:
$knockKnock = Handler::getInstance( $_ENV['API_HOST'], $knockKnockParams );
```
Доступные ключи для настройки(константы класса `Request`):

- `SETUP_PROTOCOL`
- `SETUP_HOST`
- `SETUP_METHOD`
- `SETUP_HEADERS`
- `SETUP_CONTENT_TYPE`
- `SETUP_DATA`
- `SETUP_CURL_OPTIONS`
- `SETUP_CURL_INFO`


<h2 id="knockknock-src-Handler-eventHandlers">
    Обработчики событий
</h2>

<h3 id="knockknock-src-Handler-event-list">
    Список событий
</h3>

- `EVENT_AFTER_CONSTRUCT` после создания объекта knockKnock
- `EVENT_CREATE_REQUEST` после создания объекта запроса
- `EVENT_BEFORE_SEND` перед отправкой запроса
- `EVENT_CURL_HANDLER` перед отправкой curl запроса
- `EVENT_CREATE_RESPONSE` после создания объекта ответа
- `EVENT_AFTER_SEND` после получения ответа

<h5 id="knockknock-src-Handler-events-example">
    Пример установки обработчиков событий
</h5>

```php
$knockKnock->setupEventHandlers([
    Handler::EVENT_AFTER_CONSTRUCT => function( static $knockKnock ) => {
        // создание объекта knockKnock, для взаимодействия с $knockKnock
    },
    Handler::EVENT_CREATE_REQUEST => function( static $knockKnock, Request $request ) => {
        // создание объекта запроса, для взаимодействия с $request
    },
    Handler::EVENT_BEFORE_SEND => function(  static $knockKnock, Request $request ) => {
        // отправка запроса, для взаимодействия с $request
    },
    Handler::EVENT_CURL_HANDLER => function( static $knockKnock, resource $ch ) => {
        // перед отправкой curl запроса, для взаимодействия с $ch
    },
    Handler::EVENT_CREATE_RESPONSE => function( static $knockKnock, Response $response ) => {
        // создание объекта ответа, для взаимодействия с $response
    },
    Handler::EVENT_AFTER_SEND => function( static $knockKnock, Response $response ) => {
        // получение ответа, для взаимодействия с $response
    }
]);
```
Первый аргумент - ключ события, второй - callback функция.

Все callback функции принимают первым аргументом объект/экземпляр класса `Handler`.  
Вторым аргументом передаётся объект/экземпляр класса в зависимости от события:
- `Request` - для событий `EVENT_CREATE_REQUEST`, `EVENT_BEFORE_SEND`
- `Response` - для событий `EVENT_CREATE_RESPONSE`, `EVENT_AFTER_SEND`


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 align="center">Запрос</h1>

_use [andy87\knock_knock\core\Request](src/core/Request.php);_  

Объект запроса, содержащий данные для отправки запроса.

<h3 id="knockknock-src-Request-readonly">
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

<h3 align="center" id="knockknock-src-Request-construct">
    Создание объекта запроса
</h3>

Передавая параметры напрямую в конструктор:
```php
$request = new Request( 'info/me', [
    Request::METHOD => Method::POST,
    Request::DATA => [ 'client_id' => 34 ],
    Request::HEADERS => [ 'api-secret-key' => $_ENV['API_SECRET_KEY'] ],
    Request::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    Request::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    Request::CONTENT_TYPE => ContentType::FORM_DATA,
]);
```
Методом, который вызывает _callback_ функцию, привязанную к ключу `EVENT_CREATE_REQUEST`
```php
$request = $knockKnock->constructRequest( 'info/me', [
    Request::METHOD => Method::POST,
    Request::DATA => [ 'client_id' => 45 ],
    Request::HEADERS => [ 'api-secret-key' => $_ENV['API_SECRET_KEY'] ],
    Request::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    Request::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    Request::CONTENT_TYPE => ContentType::FORM_DATA,
]);
```
Клонируя существующий объект запроса:
```php
$request = $knockKnock->constructRequest( 'info/me' );

$response = $knockKnock->setupRequest( $request )->send();

//Клонирование объекта запроса (без статуса отправки)
$cloneRequest = $request->clone();

// Отправка клона запроса
$response = $knockKnock->setupRequest( $cloneRequest )->send();
```

<h3 id="knockknock-src-Request-setter-getter">
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
$request = $knockKnock->constructRequest('info/me');

$request->setMethod( Method::GET );
$request->setData(['client_id' => 67]);
$request->setHeaders(['api-secret-key' => 'secretKey67']);
$request->setCurlOptions([
    CURLOPT_TIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true
]);
$request->setCurlInfo([
    CURLINFO_CONTENT_TYPE,
    CURLINFO_HEADER_SIZE,
    CURLINFO_TOTAL_TIME
]);
$request->setContentType( ContentType::JSON );

$protocol = $request->getPrococol(); // String
$host = $request->getHost(); // String
// ... аналогичным образом доступны и другие подобные методы для получения свойств запроса
```
<h3 id="knockknock-src-Request-setupRequest">
    Назначение запроса с переназначением свойств
</h3>

```php
$knockKnock->setupRequest( $request, [
    Request::SETUP_HOST => $_ENV['API_HOST'],
    Request::SETUP_HEADERS => [
        'api-secret' => $_ENV['API_SECRET_KEY']
    ],
]);
```
`setupRequest( Request $request, array $options = [] ): self`


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h1 align="center">Ответ</h1>

_use [andy87\knock_knock\core\Response](src/core/Response.php);_  

Объект ответа, содержащий данные ответа на запрос.
<h3 id="knockknock-src-Response-readonly">
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

<h3 align="center" id="knockknock-src-Response-construct">
    Создание объекта ответа
</h3>

Передавая параметры напрямую в конструктор:
```php
$response = new Response('{"id" => 806034, "name" => "and_y87"}', 200 );
```
Методом, который вызывает _callback_ функцию, привязанную к ключу `EVENT_CREATE_RESPONSE`
```php
$response = $knockKnock->constructResponse([
    Response::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
    Response::HTTP_CODE => 400,
], $request );
```
`constructResponse( array $responseParams, ?Request $request = null ): Response`

<h2 id="knockknock-src-Handler-send">
    Отправка запроса
</h2>

`send( array $kafeResponse = [] ): Response`  
Метод требует наличие объекта запроса установленного методом `setupRequest( Request $request )`.  

Вызов метода `send()`, возвращает объект/экземпляр класса `Response`.  
Срабатывает callback функция, привязанная к ключу:
 - `EVENT_AFTER_SEND`
 - `EVENT_CREATE_RESPONSE`
 - `EVENT_BEFORE_SEND`
 - `EVENT_CURL_HANDLER`

```php
$knockKnock = new Handler( $_ENV['API_HOST'] );

$request = $knockKnock->constructRequest( 'info/me' );

$knockKnock->setupRequest( $request );

$response = $knockKnock->send();
```

Если запрос уже был отправлен, повторно отправить его нельзя, выбрасывается `Exception`.  
Для повторной отправки запроса, необходимо создать новый объект запроса:
```php
$knockKnock = new Handler( $_ENV['API_HOST'] );

$request = $knockKnock->constructRequest( 'info/me' );

$knockKnock->setupRequest( $request );

$response = $knockKnock->send();

// повторная отправка запроса
$response = $knockKnock->setupRequest( $request->clone() )->send();
```

<h4 id="knockknock-src-Handler-chain-call">
    Цепочка вызовов
</h4>

Субъективно - более красивый вариант. Пример получения ответа - цепочкой вызовов.  
```php
$knockKnock = new Handler( $_ENV['API_HOST'] );

$request = $knockKnock->constructRequest( 'info/me' );

//Цепочка вызовов
$response = $knockKnock->setRequest( $request )->send();

$content = json_decode($response->content, true);
```
_Разумеется можно миксовать codeStyle кому как больше нравиться_

<h2 id="knockknock-src-Handler-fakeResponse">
    Отправка запроса с фэйковым ответом
</h2>

Получение подготовленного(фэйкового) ответа
```php
// параметры возвращаемого ответа
$fakeResponse = [
    Response::HTTP_CODE => 200,
    Response::CONTENT => '{"id" => 8060345, "nickName" => "and_y87"}'
];

$response = $knockKnock->setupRequest( $request )->send( $fakeResponse );
```
объект `$response` будет содержать в свойствах `content`, `httpCode` данные переданные в аргументе `$fakeResponse`

<h2 id="knockknock-src-Response-setter">
    Данные в ответе
</h2>

В созданный объект `Response`, чей запрос не был отправлен, разрешено задавать данные, используя методы группы `set`.  
```php
$response = $knockKnock->setupRequest( $request )->send();

$response
    ->setHttpCode(200)
    ->setContent('{"id" => 8060345, "nickName" => "and_y87"}');
```
**Внимание!** Если данные в объекте уже существуют, повторно задать их нельзя выбрасывается `Exception`.  
В случае необходимости заменить данные, используется вызов метода `replace( string $key, mixed $value )` см. далее

<h3 id="knockknock-src-Response-replace">
    Подмена данных
</h3>

```php
$response = $knockKnock->setupRequest( $request )->send();

$response
    ->replace( Response::HTTP_CODE, 200 )
    ->replace( Response::CONTENT, '{"id" => 8060345, "nickName" => "and_y87"}' );
```

<h2 id="knockknock-src-Response-request">
    Данные запроса из ответа
</h2>

Для получения в объекте `Response` данных запроса, необходимо обратиться к свойству `request`  
и далее взаимодействовать с ним аналогично объекту `Request`  

Получение компонента запроса:
```php
$knockKnock = new Handler( $_ENV['API_HOST'] );
$response = $knockKnock->setRequest( $knockKnock->constructRequest( 'info/me' ) )->send();

$request = $response->request;

$method = $request->method;
```

Получения свойств cURL запроса 
```php
$knockKnock = new Handler( $_ENV['API_HOST'] );
$response = $knockKnock->setRequest( $knockKnock->constructRequest( 'info/me' ) )->send();

$response->request;

// Получение свойств через объект запроса
$curlOptions =  $response->request->curlOption;
$curlInfo =  $response->request->curlInfo;

//Вариант с использованием быстрого доступа
$curlOptions =  $response->curlOption;
$curlInfo =  $response->curlInfo;
```
<h3 id="knockknock-src-Response-asArray">
    asArray()
</h3>

Преобразование ответа в массив
```php
$response = $knockKnock->setupRequest( $request )->asArray()->send();
$array = $response->content; // Array
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

Функционал включения/отключения SSL верификации в объектах `Handler` & `Request`.  

В `curlOptions` добавляется ключ `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST`.

`->disableSSL( bool $verifyPeer = false, int $verifyHost = 0 );`  
`->enableSSL( bool $verifyPeer = true, int $verifyHost = 2 );`  

`Handler` - для всех запросов
```php
$knockKnock = new Handler( $_ENV['API_HOST'] );
$knockKnock->disableSSL();

$request = $knockKnock->constructRequest( 'info/me' );

$response = $knockKnock->setupRequest( $request )->send();
```

`Request` - для конкретного запроса  
```php
$knockKnock = new Handler( $_ENV['API_HOST'] )->disableSSL();

$request = $knockKnock->constructRequest( 'info/me' );
$request->enableSSL();

$response = $knockKnock->setupRequest( $request )->send();
```
<h3 id="knockknock-src-Cookie">
    Cookie
</h3>

В объекте `Handler` имеется функционал использования cookie.  
`Handler` - для всех запросов  
```php
$knockKnock = new Handler( $_ENV['API_HOST'] );

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
$knockKnock = new Handler( $_ENV['API_HOST'] );

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
    ->setupContentType( ContentType::JSON )
    ->on( Handler::EVENT_AFTER_SEND, function( Handler $handler, Response $response ) => 
    {
        $logFilePath = $_SERVER['DOCUMENT_ROOT'] . '/api_log.txt';

        file_put_contents( $logFilePath, $response->content, FILE_APPEND );
    });

// Получение ответа на запрос методом `patch`
$responsePatch = $knockKnockSecurity->patch( 'product', [
    'price' => 1000
]);

$product = $responsePatch->asArray();

$price = $product['price'];

// Изменение типа контента на `application/json`, для следующего запроса
$knockKnockSecurity->useContentType( ContentType::JSON );

// Отправка POST запроса и получение ответа
$responsePost = $knockKnockSecurity->post( 'category', [
    'name' => 'Фреймворки'
]);

$response = json_decode( $responsePost->content );

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
class KnockKnockYandex extends Handler
{
    private const LOGGER = 'logger';


    private string $host = 'https://api.yandex.ru/'

    private string $contentType = ContentType::JSON

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
        $this->on( self::AFTER_CREATE_REQUEST, function( Request $request ) => 
        {
            $logData = $this->getLogDataByRequest( $request );

            $this->addYandexLog( $logData );
        };

        $this->on(self::EVENT_AFTER_SEND, function( Response $response ) => 
        {
            $logData = $this->getLogDataByRequest( $response->request );

            $this->addYandexLog( $logData );
        };
    }

    /**
      * @param Request $request
      * 
      * @return array
      */
    private function getLogDataByRequest( Request $request ): array
    {
        return $request->getParams();
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

$response = $knockKnockYandex->setupRequest( 'profile', [ 
    Request::METHOD => Method::PATCH,
    Request::DATA => [ 'city' => 'Moscow' ],
]); // Логирование `afterCreateRequest`

$response = $knockKnockYandex->send(); // Логирование `afterSend`
```


<p align="center">- - - - -</p>

___

<p align="center">- - - - -</p>


<h2 id="knockknock-tests">
    Тесты
</h2>

 - tests: 106
 - assertions: 370

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
