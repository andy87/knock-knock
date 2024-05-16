# curl Facade

PHP Фасад\Адаптер для отправки запросов через ext cURL

![IN PROGRESS](http://www.bc-energy.it/wp-content/uploads/2013/08/work-in-progress.png)

# Knock: Получение объекта/экземпляра класса для использования

Нативный вариант
```php
$knockKnock = new KnockKnock([
    KnockKnock::HOST => 'https://api.url',
    KnockKnock::CONTENT_TYPE => KnockRequest::CONTENT_TYPE_JSON,
]);
```
вариант Singleton
```php
$knockKnock = KnockKnock::getInstance([
    KnockKnock::HOST => 'https://api.url',
    KnockKnock::CONTENT_TYPE => KnockRequest::CONTENT_TYPE_JSON,
]);
```
getInstance( array $knockKnockConfig = [] ); // return static

## Установка отдельных настроек конфигурации компонента
Доступны set/get методы для взаимодействия с отдельными свойствам,
которые в дальнейшем будут передаваться запросам исполняемым `$knockKnock` объектом
```php
$knockKnock->setConfigAuthorization('token', KnockKnock::AUTH_BEARER );
$knockKnock->setConfigHeaders([ 'api-secret' => 'secretKey12']);
$knockKnock->setConfigContentType(KnockRequest::CONTENT_TYPE_MULTIPART);
```
Доступна цепочка вызовов:
```php
$knockKnock
    ->setConfigAuthorization('token', KnockKnock::AUTH_BEARER );
    ->setConfigHeaders([ 'api-secret' => 'secretKey23']);
    ->setConfigContentType(KnockRequest::CONTENT_TYPE_MULTIPART);

$bearer = $knockKnock->getConfigAuthorization(); // string
```
setConfigAuthorization( string $token, string $method = self::AUTH_BASIC )

Все подобные методы возвращают `static` объект / экземпляр класса `KnockKnock`

# Обработчики событий

Установить обработчики событий
```php
$knockKnock->setupCallback([
    KnockKnock::EVENT_AFTER_CONSTRUCT => function( static $static ) {
        // Действия после создания объекта
    },
    KnockKnock::EVENT_AFTER_CREATE_REQUEST => function( static $static ) {
        // Действия после создания объекта запроса
    },
    KnockKnock::EVENT_BEFORE_SEND => function( KnockRequest $knockRequest ) {
        // Действия перед отправкой запроса
    },
    KnockKnock::EVENT_AFTER_SEND => function( KnockResponse $knockResponse ) {
        // Действия после отправки запроса и получения ответа
    }
]);
```
setupCallback( array $callbacks ); // return static

# KnockRequest: Создание запроса

Нативное создание объекта / экземпляра класса с данными конкретного запроса
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

Доступно создание объекта / экземпляра класса с данными конкретного запроса - через метод фасада
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
constructKnockRequest( string $url, array $paramsKnockRequest = [] ); // return KnockRequest

## Изменение общих настроек компонента на частные 
Если надо выполнить запрос, с другими параметрами
```php
$knockRequest = $knock
    ->setupTempAuthorization('token', KnockKnock::AUTH_BEARER )
    ->setupTempHeaders([ 'api-secret' => 'secretKey56'])
    ->constructKnockRequest('info/me',[
        KnockRequest::CONTENT_TYPE => KnockContentType::FORM_DATA,
    ]); // return KnockRequest
```

### Назначение/Изменение отдельных параметров запроса (set/get)

```php
$knockRequest = $knockKnock->constructKnockRequest('info/me');

// set/get методы для взаимодействия с отдельными свойствами
$knockRequest->setUrl('info/me');
$knockRequest->setMethod(KnockMethod::GET);
$knockRequest->setData(['client_id' => 67]);
$knockRequest->setHeaders(['api-secret-key' => 'secretKey67']);
$knockRequest->setCurlOptions([CURLOPT_TIMEOUT => 10]);
$knockRequest->setCurlInfo([
    CURLINFO_CONTENT_TYPE,
    CURLINFO_HEADER_SIZE,
    CURLINFO_TOTAL_TIME
]);
$knockRequest->setContentType(KnockContentType::JSON);

$knockKnock->setRequest( $knockRequest );

$method = $knockRequest->getMethod(); // string
// ... аналогичным образом доступны и другие подобные методы для получения свойств запроса
```

### Обновление свойств созданного запроса, данными переданными опционально

```php
$knockKnock->setRequest( $knockRequest, [
    KnockKnock::HOST => 'https://api.url',
    KnockKnock::BEARER => 'token-bearer-2',
    KnockKnock::HEADERS => [
        'api-secret' => 'secret-key-2'
    ],
]);
```

## KnockResponse: Ответ

Конструктор KnockResponse
```php
$knockResponse = $knockKnock->constructKnockResponse([
    'id' => 12345,
    'name' => 'Test'
]);
```
constructKnockResponse( array $paramsKnockResponse, int $httpCode = 200 ); // return KnockResponse

## KnockResponse: Получением ответа при отправке запроса

Получение ответа с отправкой запроса - отдельным вызовом 
```php
$knockKnock->setRequest( $knockRequest );
$knockResponse = $knockKnock->send(); // return KnockResponse
```
Получение ответа с отправкой запроса - цепочкой вызовов
```php
$knockResponse = $knockKnock->setRequest( $knockRequest )->send(); // return KnockResponse
```
send( array $prepareKnockResponseParams = [] ); // return KnockResponse
возвращает объект/экземпляр класса KnockResponse

## Отправка запроса с фэйковым ответом

Цепочка вызовов, возвращает подготовленный ответ.
```php
// параметры возвращаемого ответа
$prepareFakeKnockResponseParams = [
    KnockResponse::HTTP_CODE => 200,
    KnockResponse::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
];

$knockResponse = $knockKnock->setRequest( $knockRequest )->send( $prepareFakeKnockResponseParams ); // return KnockResponse
```
объект `KnockResponse` будет содержать данные переданные в аргументе `$prepareFakeKnockResponseParams`


## Подмена данных в ответе

```php
$knockResponse = $knockKnock->setRequest( $knockRequest )->send(); // return KnockResponse

$knockResponse
    ->replace(KnockResponse::HTTP_CODE, 200)
    ->replace(KnockResponse::CONTENT, '{"id" => 8060345, "nickName" => "and_y87"}');
```
replace( string $property, mixed $value )


## Извлечение полезных данных из запроса

```php
// Получение опций запроса (  KnockRequest::CURL_OPTIONS )
$curlOptions =  $knockResponse->get( KnockResponse::CURL_OPTIONS ); // return array

// Получение данных о запросе ( KnockRequest::CURL_INFO )
$curlInfo =  $knockResponse->get( KnockResponse::CURL_INFO ); // return array

```

# Custom реализация

Custom реализация Базового класса
```php
class KnockKnockYandex implements KnockKnockInterface
{
    private const AFTER_CREATE_REQUEST = 'afterCreateRequest';
    private const LOGGER = 'logger';



    private string $host = 'https://api.yandex.ru/'
    private string $contentType = KnockContentType::JSON

    private YandexLogger $logger;



    public function init()
    {
        $this->event[self::AFTER_CREATE_REQUEST] = fn( KnockRequest $knockRequest ) => 
        {
            $this->addYandexLog([
                'url' => $knockRequest->getUrl(),
                'method' => $knockRequest->getMethod(),
                'data' => $knockRequest->getData(),
                'headers' => $knockRequest->getHeaders(),
            ]);
        };

        $this->event[self::EVENT_AFTER_SEND] = fn( KnockResponse $knockResponse ) => 
        {
            $knockRequest = $knockResponse->getRequest();

            $this->addYandexLog([
                'url' => $knockRequest->getUrl(),
                'method' => $knockRequest->getMethod(),
                'data' => $knockRequest->getData(),
                'headers' => $knockRequest->getHeaders(),
            ]);
        };
    }

    public function createRequest( string $url, array $requestParams ): KnockRequest
    {
        $knockRequest = new KnockRequest( $url, $requestParams );

        $this->event( self::AFTER_CREATE_REQUEST, $knockRequest );

        return $knockRequest;
    }

    private function addYandexLog( array $params ) 
    {
        $logger->log($params);
    }

}

```
Пример использования custom реализации
```php

$knockKnockYandex = KnockKnockYandex::getInstanse([
    KnockKnock::LOGGER => new YandexLogger(),
]);

$knockResponse = $knockKnockYandex->setRequest('profile', [
    KnockRequest::METHOD => KnockMethod::PATCH,
    KnockRequest::DATA => [ 'city' => 'Moscow' ],
])->send();

```

# Расширения

Работают через "магию"

Реализация расширения
```php
/**
 * @method static checkHeader( KnockKnock $knockKnock )
 */
class CustomKnockKnock extends KnockKnock
{
    /** @var callable[] */
    private array $extensions = [];



    public function init()
    {
        $this->addExtension( 'checkHeader', fn( $this ) => {
            if ( str_contains($this->host,'yandex') ) {
                $this->headers[] = ['Host' => 'client.ru']
            }
        });
    }
}
```

Использование расширения
```php
$knockKnock = CustomKnockKnock::getInstance([
    KnockKnock::HOST => 'https://api.yandex.ru/',
]);

$knockKnock->checkHeader();

$knockResponse = $knockKnock->setRequest('profile', [
    KnockRequest::METHOD => KnockMethod::PATCH,
    KnockRequest::DATA => [ 'homepage' => 'www.andy87.ru' ],
])->send();

```
